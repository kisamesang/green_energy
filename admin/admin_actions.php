<?php
// -----------------------------------------------------------------------------
// Admin Actions (ไฟล์ประมวลผล POST)
// (ไฟล์นี้อยู่ใน /admin/ แล้ว)
// -----------------------------------------------------------------------------

// --- (แก้ไข) ต้องถอยกลับ 1 ระดับเพื่อหา db_connect.php ---
require_once '../db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['u_role']) || $_SESSION['u_role'] !== 'admin') {
    // --- (แก้ไข) ต้องถอยกลับ 1 ระดับเพื่อไป index.php ---
    header('Location: ../index.php');
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
                            WHERE el_id = $log_id AND el_verification_status = 'pending'"; 
            
            if ($conn->query($sql_approve) === TRUE) {
                // (สำเร็จ)
            } else {
                throw new Exception("ไม่สามารถอนุมัติบิลได้: " . $conn->error);
            }
        
        // --- ACTION 2: ปฏิเสธบิล (Reject Bill) ---
        } elseif ($action === 'reject_bill') {
            $log_id = $_POST['log_id'] ?? 0;
            
            if (empty($log_id)) {
                throw new Exception("Log ID ห้ามว่าง");
            }

            $sql_reject = "UPDATE energy_log 
                           SET el_verification_status = 'rejected' 
                           WHERE el_id = $log_id AND el_verification_status = 'pending'";
            
            if ($conn->query($sql_reject) === TRUE) {
                // (สำเร็จ)
            } else {
                throw new Exception("ไม่สามารถปฏิเสธบิลได้: " . $conn->error);
            }
        
        // --- (Action อื่นๆ) ---
            
        } else {
            throw new Exception("ไม่รู้จัก Action นี้");
        }

    } catch (Exception $e) {
        die($e->getMessage()); // (สำหรับ Debug)
    }

    $conn->close();
    
    // 3. (ลิงก์นี้ถูกต้องแล้ว) เด้งกลับไปหน้า Dashboard ของ Admin
    header('Location: admin_dashboard.php');
    exit;
    
} else {
    // (ลิงก์นี้ถูกต้องแล้ว)
    header('Location: admin_dashboard.php');
    exit;
}
?>