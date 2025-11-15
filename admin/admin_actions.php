<?php
// -----------------------------------------------------------------------------
// Admin Actions (ไฟล์ประมวลผล POST)
// -----------------------------------------------------------------------------

// --- (แก้ไข) ต้องถอยกลับ 1 ระดับเพื่อหา db_connect.php ---
require_once '../db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['u_role']) || $_SESSION['u_role'] !== 'admin') {
    // --- (แก้ไข) ต้องถอยกลับ 1 ระดับเพื่อไป index.php ---
    header('Location: ../index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$redirect_to = 'admin_dashboard.php'; // (ค่าเริ่มต้น ถ้าไม่ระบุ)

try {
    // 2. ตรวจสอบว่าเป็น POST Request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }
    
    // --- ACTION 1: อนุมัติบิล (Approve Bill) ---
    if ($action === 'approve_bill') {
        $log_id = (int)($_POST['log_id'] ?? 0);
        if (empty($log_id)) throw new Exception("Log ID ห้ามว่าง");
        
        $sql_approve = "UPDATE energy_log SET el_verification_status = 'verified' WHERE el_id = $log_id AND el_verification_status = 'pending'";
        
        // *** 💡 เริ่ม Transaction เพื่อให้การอนุมัติบิลและการให้รางวัลสำเร็จไปพร้อมกัน ***
        $conn->begin_transaction();
        
        if (!$conn->query($sql_approve)) {
            $conn->rollback();
            throw new Exception("Approve Bill Error: " . $conn->error);
        }

        // 1. ตรวจสอบว่ามีการอัปเดตจริงหรือไม่ (ป้องกันการรันโค้ดต่อหากบิลไม่ได้อยู่ในสถานะ pending)
        if ($conn->affected_rows === 0) {
             $conn->rollback();
             throw new Exception("ไม่พบ Log ID: $log_id หรือสถานะไม่เป็น 'pending'");
        }
        
        $campaigns_awarded_count = 0;
        
        // 2. ดึงข้อมูล KWH และ User ID จากรายการที่เพิ่ง Verified
        $sql_fetch_data = "SELECT u_id, el_kwh_usage FROM energy_log WHERE el_id = $log_id";
        $log_result = $conn->query($sql_fetch_data);
        
        if ($log_result->num_rows > 0) {
            $log_row = $log_result->fetch_assoc();
            $user_id = $log_row['u_id'];
            $current_kwh = (float)$log_row['el_kwh_usage'];

            // 3. ค้นหาแคมเปญที่ผู้ใช้เข้าร่วมและมีสถานะ 'accepted'
            $sql_find_campaign = "SELECT uc.*, c.* FROM user_campaigns uc 
                                  JOIN campaigns c ON uc.c_id = c.c_id 
                                  WHERE uc.u_id = $user_id AND uc.uc_status = 'accepted'";
            $campaign_result = $conn->query($sql_find_campaign);

            if ($campaign_result->num_rows > 0) {
                
                // 4. วนซ้ำเพื่อตรวจสอบและให้รางวัลทุกแคมเปญ
                while ($campaign_row = $campaign_result->fetch_assoc()) {
                    $target_kwh = (float)$campaign_row['uc_target_kwh'];
                    
                    // เงื่อนไขความสำเร็จ
                    if ($current_kwh <= $target_kwh) {
                        
                        // ให้รางวัล (ทำสำเร็จ)
                        $uc_id = $campaign_row['uc_id'];
                        $c_id = $campaign_row['c_id'];
                        $reward_value = $campaign_row['c_reward_value'];
                        $partner_name = $campaign_row['c_partner_name'];
                        $reward_code = 'COUPON-' . strtoupper(uniqid());
                        $reward_expiry_date = date('Y-m-d', strtotime('+30 days'));

                        // อัปเดตสถานะแคมเปญ
                        if (!$conn->query("UPDATE user_campaigns SET uc_status = 'completed' WHERE uc_id = $uc_id")) {
                            $conn->rollback();
                            throw new Exception("Update Campaign Error for uc_id $uc_id: " . $conn->error);
                        }
                        
                        // บันทึกรางวัล
                        if (!$conn->query("INSERT INTO user_rewards (u_id, c_id, ur_code, ur_value, ur_partner_name, ur_expires_at) 
                                          VALUES ($user_id, $c_id, '$reward_code', $reward_value, '$partner_name', '$reward_expiry_date')")) {
                            $conn->rollback();
                            throw new Exception("Insert Reward Error for c_id $c_id: " . $conn->error);
                        }
                        
                        $campaigns_awarded_count++;
                    }
                }
            }
        }
        
        // 5. Commit Transaction เมื่อทุกอย่างสำเร็จ
        $conn->commit();
        
        $campaign_msg = "";
        if ($campaigns_awarded_count > 0) {
            $campaign_msg = " และให้รางวัลแคมเปญสำเร็จ $campaigns_awarded_count รายการ";
        } elseif (isset($user_id) && $user_id > 0) {
             $campaign_msg = " (ตรวจสอบแคมเปญแล้ว: ไม่พบแคมเปญที่สำเร็จ)";
        } else {
             $campaign_msg = " (ไม่สามารถตรวจสอบแคมเปญได้: ไม่พบข้อมูลบิล)";
        }

        $_SESSION['admin_success'] = "อนุมัติบิล ID: $log_id เรียบร้อย" . $campaign_msg;
        $redirect_to = 'admin_dashboard.php'; 
    
    // ... โค้ด ACTION อื่น ๆ ต่อจากตรงนี้
        
    // --- ACTION 6: ลบเนื้อหา (Delete Tip) ---
    } elseif ($action === 'delete_tip') {
        $t_id = (int)($_POST['t_id'] ?? 0);
        if (empty($t_id)) throw new Exception("Tip ID ห้ามว่าง");

        $sql_delete = "DELETE FROM tips WHERE t_id = $t_id";
        if (!$conn->query($sql_delete)) throw new Exception("Delete Tip Error: " . $conn->error);
        
        $_SESSION['admin_success'] = "ลบเคล็ดลับ ID: $t_id เรียบร้อย";
        $redirect_to = 'admin_manage_content.php';
            
    // --- ACTION 7: บันทึกแคมเปญ (Save Campaign) ---
    } elseif ($action === 'save_campaign') {
        $c_id = (int)($_POST['c_id'] ?? 0);
        $c_title = $conn->real_escape_string($_POST['c_title'] ?? '');
        $c_description = $conn->real_escape_string($_POST['c_description'] ?? '');
        $c_partner_name = $conn->real_escape_string($_POST['c_partner_name'] ?? '');
        $c_reward_value = (int)($_POST['c_reward_value'] ?? 0);
        $c_reduction_target = (float)($_POST['c_reduction_target'] ?? 0);
        $c_is_active = isset($_POST['c_is_active']) ? 1 : 0;
        $c_expires_at = !empty($_POST['c_expires_at']) ? "'" . $conn->real_escape_string($_POST['c_expires_at']) . "'" : "NULL";

        if (empty($c_id)) {
            $sql_save = "INSERT INTO campaigns (c_title, c_description, c_partner_name, c_reward_value, c_reduction_target, c_is_active, c_expires_at) 
                         VALUES ('$c_title', '$c_description', '$c_partner_name', $c_reward_value, $c_reduction_target, $c_is_active, $c_expires_at)";
            $_SESSION['admin_success'] = "สร้างแคมเปญใหม่เรียบร้อย";
        } else {
            $sql_save = "UPDATE campaigns SET 
                            c_title = '$c_title',
                            c_description = '$c_description',
                            c_partner_name = '$c_partner_name',
                            c_reward_value = $c_reward_value,
                            c_reduction_target = $c_reduction_target,
                            c_is_active = $c_is_active,
                            c_expires_at = $c_expires_at
                         WHERE c_id = $c_id";
            $_SESSION['admin_success'] = "อัปเดตแคมเปญ ID: $c_id เรียบร้อย";
        }
        
        if (!$conn->query($sql_save)) throw new Exception("Save Campaign Error: " . $conn->error);
        
        $redirect_to = 'admin_manage_campaigns.php';

    // --- ACTION 8: ลบแคมเปญ (Delete Campaign) ---
    } elseif ($action === 'delete_campaign') {
        $c_id = (int)($_POST['c_id'] ?? 0);
        if (empty($c_id)) throw new Exception("Campaign ID ห้ามว่าง");

        $sql_delete = "DELETE FROM campaigns WHERE c_id = $c_id";
        if (!$conn->query($sql_delete)) throw new Exception("Delete Campaign Error: " . $conn->error . " (อาจมีผู้ใช้รับแคมเปญนี้แล้ว)");
        
        $_SESSION['admin_success'] = "ลบแคมเปญ ID: $c_id เรียบร้อย";
        $redirect_to = 'admin_manage_campaigns.php';

    } else {
        throw new Exception("ไม่รู้จัก Action นี้: $action");
    }

} catch (Exception $e) {
    // (ตั้งค่า Error Message ใน Session)
    $_SESSION['admin_error'] = $e->getMessage();
    // (ถ้าเกิด Error ให้เด้งกลับไปหน้าเดิมที่ส่งมา)
    $redirect_to = $_SERVER['HTTP_REFERER'] ?? $redirect_to;
}

$conn->close();

// 3. เมื่อประมวลผลเสร็จ ให้เด้งกลับไปหน้าปลายทางที่กำหนด
header('Location: ' . $redirect_to);
exit;
?>