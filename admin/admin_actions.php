<?php
// -----------------------------------------------------------------------------
// Admin Actions (ไฟล์ประมวลผล POST)
// -----------------------------------------------------------------------------
require_once 'db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบสิทธิ์ (Security)
// ต้องเป็น Admin เท่านั้น
if (!isset($_SESSION['u_role']) || $_SESSION['u_role'] !== 'admin') {
    // (ส่งกลับไปหน้าแรก ถ้าไม่ใช่ Admin)
    header('Location: index.php');
    exit;
}

// 2. ตรวจสอบว่าเป็น POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? '';

    try {
        
        // --- ACTION 1: อนุมัติบิล (Approve Bill) ---
        if ($action === 'approve_bill') {
            $log_id = $_POST['log_id'] ?? 0;
            
            if (empty($log_id)) {
                throw new Exception("Log ID ห้ามว่าง");
            }
            
            $sql_approve = "UPDATE energy_log 
                            SET el_verification_status = 'verified' 
                            WHERE el_id = $log_id AND el_verification_status = 'pending'"; // (อัปเดตเฉพาะอันที่ pending)
            
            if ($conn->query($sql_approve) === TRUE) {
                // (ถ้าสำเร็จ สามารถตั้งค่า Flash Message ได้)
                // $_SESSION['admin_success'] = "อนุมัติบิล $log_id เรียบร้อย";
            } else {
                throw new Exception("ไม่สามารถอนุมัติบิลได้: " . $conn->error);
            }
        
        // --- ACTION 2: ปฏิเสธบิล (Reject Bill) ---
        } elseif ($action === 'reject_bill') {
            $log_id = $_POST['log_id'] ?? 0;
            
            if (empty($log_id)) {
                throw new Exception("Log ID ห้ามว่าง");
            }

            // (ในระบบจริง เราอาจจะต้องลบไฟล์ใน uploads/bills/ ด้วย)
            // (แต่ในการแข่ง แค่อัปเดต DB ก็เพียงพอ)
            
            $sql_reject = "UPDATE energy_log 
                           SET el_verification_status = 'rejected' 
                           WHERE el_id = $log_id AND el_verification_status = 'pending'";
            
            if ($conn->query($sql_reject) === TRUE) {
                // $_SESSION['admin_success'] = "ปฏิเสธบิล $log_id เรียบร้อย";
            } else {
                throw new Exception("ไม่สามารถปฏิเสธบิลได้: " . $conn->error);
            }
        
        // --- (ในอนาคต เพิ่ม Action อื่นๆ ที่นี่) ---
        /*
        } elseif ($action === 'ban_user') {
            // ...
        } elseif ($action === 'create_campaign') {
            // ...
        */
            
        } else {
            throw new Exception("ไม่รู้จัก Action นี้");
        }

    } catch (Exception $e) {
        // (ในระบบจริง ควรตั้งค่า Error Message)
        // $_SESSION['admin_error'] = $e->getMessage();
        die($e->getMessage()); // (สำหรับ Debug)
    }

    $conn->close();
    
    // 3. เมื่อประมวลผลเสร็จ ให้เด้งกลับไปหน้า Dashboard ของ Admin
    header('Location: admin_dashboard.php');
    exit;
    
} else {
    // ถ้าไม่ใช่ POST ให้เด้งกลับ
    header('Location: admin_dashboard.php');
    exit;
}
?>