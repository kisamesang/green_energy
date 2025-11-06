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
        if (!$conn->query($sql_approve)) throw new Exception("Approve Bill Error: " . $conn->error);
        
        $_SESSION['admin_success'] = "อนุมัติบิล ID: $log_id เรียบร้อย";
        $redirect_to = 'admin_dashboard.php'; // (ระบุปลายทาง)
    
    // --- ACTION 2: ปฏิเสธบิล (Reject Bill) ---
    } elseif ($action === 'reject_bill') {
        $log_id = (int)($_POST['log_id'] ?? 0);
        if (empty($log_id)) throw new Exception("Log ID ห้ามว่าง");

        $sql_reject = "UPDATE energy_log SET el_verification_status = 'rejected' WHERE el_id = $log_id AND el_verification_status = 'pending'";
        if (!$conn->query($sql_reject)) throw new Exception("Reject Bill Error: " . $conn->error);

        $_SESSION['admin_success'] = "ปฏิเสธบิล ID: $log_id เรียบร้อย";
        $redirect_to = 'admin_dashboard.php';

    // --- ACTION 3: ระงับผู้ใช้ (Ban User) ---
    } elseif ($action === 'ban_user') {
        $user_id = (int)($_POST['u_id'] ?? 0);
        if (empty($user_id)) throw new Exception("User ID ห้ามว่าง");

        $sql_ban = "UPDATE users SET u_status = 'banned' WHERE u_id = $user_id AND u_role = 'user'";
        if (!$conn->query($sql_ban)) throw new Exception("Ban User Error: " . $conn->error);

        $_SESSION['admin_success'] = "ระงับผู้ใช้ ID: $user_id เรียบร้อย";
        $redirect_to = 'admin_manage_users.php'; // (ปลายทาง)

    // --- ACTION 4: ยกเลิกระงับ (Unban User) ---
    } elseif ($action === 'unban_user') {
        $user_id = (int)($_POST['u_id'] ?? 0);
        if (empty($user_id)) throw new Exception("User ID ห้ามว่าง");

        $sql_unban = "UPDATE users SET u_status = 'active' WHERE u_id = $user_id AND u_role = 'user'";
        if (!$conn->query($sql_unban)) throw new Exception("Unban User Error: " . $conn->error);

        $_SESSION['admin_success'] = "ยกเลิกการระงับผู้ใช้ ID: $user_id เรียบร้อย";
        $redirect_to = 'admin_manage_users.php';

    // --- ACTION 9: บันทึกผู้ใช้ (Save User) ---
    } elseif ($action === 'save_user') {
        // 1. ดึงข้อมูลจากฟอร์ม
        $u_id = (int)($_POST['u_id'] ?? 0);
        $u_full_name = $conn->real_escape_string($_POST['u_full_name'] ?? '');
        $u_email = $conn->real_escape_string($_POST['u_email'] ?? '');
        $u_username = $conn->real_escape_string($_POST['u_username'] ?? '');
        $u_password = $_POST['u_password'] ?? ''; // (ยังไม่ Hash)
        $u_role = $conn->real_escape_string($_POST['u_role'] ?? 'user');
        $u_status = $conn->real_escape_string($_POST['u_status'] ?? 'active');

        // 2. ตรวจสอบ Username/Email ซ้ำ
        $sql_check_dupe = "SELECT u_id FROM users WHERE (u_username = '$u_username' OR u_email = '$u_email') AND u_id != $u_id";
        $dupe_result = $conn->query($sql_check_dupe);
        if ($dupe_result->num_rows > 0) {
            throw new Exception("Username หรือ Email นี้ถูกใช้งานแล้ว");
        }

        // 3. จัดการรหัสผ่าน
        $password_sql_part = "";
        if (!empty($u_password)) {
            // (ถ้ามีการกรอกรหัสผ่านใหม่)
            if (strlen($u_password) < 6) {
                throw new Exception("รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร");
            }
            $hashed_password = password_hash($u_password, PASSWORD_DEFAULT);
            $password_sql_part = ", u_password = '$hashed_password'";
        }
        
        // 4. ตรวจสอบว่า "สร้างใหม่" (INSERT) หรือ "แก้ไข" (UPDATE)
        if (empty($u_id)) {
            // สร้างใหม่ (INSERT)
            if (empty($u_password)) {
                throw new Exception("กรุณากรอกรหัสผ่านสำหรับผู้ใช้ใหม่");
            }
            $sql_save = "INSERT INTO users (u_full_name, u_email, u_username, u_password, u_role, u_status) 
                         VALUES ('$u_full_name', '$u_email', '$u_username', '$hashed_password', '$u_role', '$u_status')";
            $_SESSION['admin_success'] = "สร้างผู้ใช้ '$u_username' เรียบร้อย";
        } else {
            // แก้ไข (UPDATE)
            $sql_save = "UPDATE users SET 
                            u_full_name = '$u_full_name',
                            u_email = '$u_email',
                            u_username = '$u_username',
                            u_role = '$u_role',
                            u_status = '$u_status'
                            $password_sql_part 
                         WHERE u_id = $u_id";
            $_SESSION['admin_success'] = "อัปเดตผู้ใช้ '$u_username' เรียบร้อย";
        }
        
        if (!$conn->query($sql_save)) throw new Exception("Save User Error: " . $conn->error);
        
        $redirect_to = 'admin_manage_users.php';
        
    // --- (ใหม่) ACTION 10: ลบผู้ใช้ถาวร (Hard Delete User) ---
    } elseif ($action === 'delete_user') {
        $u_id = (int)($_POST['u_id'] ?? 0);
        if (empty($u_id)) throw new Exception("User ID ห้ามว่าง");
        
        // --- (สำคัญ) เริ่ม Transaction ---
        $conn->begin_transaction();
        
        try {
            // 1. ลบข้อมูลลูก (Child records) ก่อน
            $conn->query("DELETE FROM energy_log WHERE u_id = $u_id");
            $conn->query("DELETE FROM saving_goals WHERE u_id = $u_id");
            $conn->query("DELETE FROM user_rewards WHERE u_id = $u_id");
            $conn->query("DELETE FROM user_campaigns WHERE u_id = $u_id");
            
            // 2. ลบข้อมูลแม่ (Parent record)
            $sql_delete_user = "DELETE FROM users WHERE u_id = $u_id AND u_role = 'user'";
            $conn->query($sql_delete_user);
            
            // 3. ยืนยัน Transaction
            $conn->commit();
            
            $_SESSION['admin_success'] = "ลบผู้ใช้ ID: $u_id และข้อมูลที่เกี่ยวข้องทั้งหมดเรียบร้อย";
            
        } catch (Exception $e) {
            // 4. ถ้ามี Error ให้ Rollback
            $conn->rollback();
            throw new Exception("Delete User Error: " . $e->getMessage());
        }
        
        $redirect_to = 'admin_manage_users.php';

    // --- (โค้ดของ Content และ Campaigns ยังคงอยู่) ---
        
    // --- ACTION 5: บันทึกเนื้อหา (Save Tip) ---
    } elseif ($action === 'save_tip') {
        $t_id = (int)($_POST['t_id'] ?? 0);
        $t_title = $conn->real_escape_string($_POST['t_title'] ?? '');
        $t_content = $conn->real_escape_string($_POST['t_content'] ?? '');
        $t_category = $conn->real_escape_string($_POST['t_category'] ?? 'ทั่วไป');
        $t_is_active = isset($_POST['t_is_active']) ? 1 : 0;
        $t_display_until = !empty($_POST['t_display_until']) ? "'" . $conn->real_escape_string($_POST['t_display_until']) . "'" : "NULL";

        if (empty($t_id)) {
            $sql_save = "INSERT INTO tips (t_title, t_content, t_category, t_is_active, t_display_until) 
                         VALUES ('$t_title', '$t_content', '$t_category', $t_is_active, $t_display_until)";
            $_SESSION['admin_success'] = "สร้างเคล็ดลับใหม่เรียบร้อย";
        } else {
            $sql_save = "UPDATE tips SET 
                            t_title = '$t_title',
                            t_content = '$t_content',
                            t_category = '$t_category',
                            t_is_active = $t_is_active,
                            t_display_until = $t_display_until
                         WHERE t_id = $t_id";
            $_SESSION['admin_success'] = "อัปเดตเคล็ดลับ ID: $t_id เรียบร้อย";
        }
        
        if (!$conn->query($sql_save)) throw new Exception("Save Tip Error: " . $conn->error);
        
        $redirect_to = 'admin_manage_content.php';
        
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