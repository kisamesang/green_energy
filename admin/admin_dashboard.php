<?php
// -----------------------------------------------------------------------------
// Admin Dashboard (หน้าแรก Admin)
// (ไฟล์นี้อยู่ใน /admin/ แล้ว)
// -----------------------------------------------------------------------------
$page_title = 'Admin Dashboard'; // (สำหรับ <title>)
$current_page = 'dashboard'; // (สำหรับ Active Menu)

// (ลิงก์นี้ถูกต้องแล้ว เพราะ admin_header.php อยู่ในโฟลเดอร์เดียวกัน)
require_once 'admin_header.php'; // (เรียก Header และ ตรวจสอบสิทธิ์ Admin)

// --- START: ดึงข้อมูล (GET) ---

// 1. ดึงสถิติสรุป (Admin Stats)
try {
    // (โค้ดส่วนนี้ไม่ต้องแก้ไข เพราะ $conn มาจาก admin_header.php)
    $result_users = $conn->query("SELECT COUNT(u_id) as count FROM users WHERE u_role = 'user'");
    $total_users = $result_users->fetch_assoc()['count'];
    
    $result_pending = $conn->query("SELECT COUNT(el_id) as count FROM energy_log WHERE el_verification_status = 'pending'");
    $total_pending_logs = $result_pending->fetch_assoc()['count'];
    
    $result_campaigns = $conn->query("SELECT COUNT(c_id) as count FROM campaigns WHERE c_is_active = 1");
    $total_active_campaigns = $result_campaigns->fetch_assoc()['count'];

} catch (Exception $e) {
    $admin_error = "เกิดข้อผิดพลาดในการดึงสถิติ: " . $e->getMessage();
}

// 2. ดึงคิวรอตรวจสอบ (Verification Queue)
$pending_logs = [];
try {
    // (โค้ดส่วนนี้ไม่ต้องแก้ไข)
    $sql_queue = "SELECT el.el_id, el.el_kwh_usage, el.el_date, el.el_bill_proof_file, u.u_full_name, u.u_username
                  FROM energy_log el
                  JOIN users u ON el.u_id = u.u_id
                  WHERE el.el_verification_status = 'pending'
                  ORDER BY el.el_date ASC"; 
                  
    $result_queue = $conn->query($sql_queue);
    while ($row = $result_queue->fetch_assoc()) {
        $pending_logs[] = $row;
    }

} catch (Exception $e) {
    $admin_error = "เกิดข้อผิดพลาดในการดึงคิว: " . $e->getMessage();
}
// --- END: ดึงข้อมูล (GET) ---

$conn->close();
?>

<!-- (HTML เริ่มต้นใน admin_header.php) -->

<!-- Main Content: Admin Dashboard -->
<main class="main-content">
    <div class="container">
        
        <h2 class="h4 mb-4">Dashboard ผู้ดูแลระบบ</h2>

        <!-- แสดงข้อความ Error (ถ้ามี) -->
        <?php if (!empty($admin_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($admin_error); ?></div>
        <?php endif; ?>
        
        <!-- แถวสถิติสรุป (Admin Stats) -->
        <div class="row g-4 mb-4">
            <!-- (โค้ดส่วนสถิติ 3 การ์ด ไม่ต้องแก้ไข) -->
            <div class="col-md-4">
                <div class="card shadow-sm stat-card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase small">ผู้ใช้ทั้งหมด</h6>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_users ?? 0); ?></h3>
                            </div>
                            <i class="fas fa-users fa-3x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm stat-card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase small">รออนุมัติ (บิล)</h6>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_pending_logs ?? 0); ?></h3>
                            </div>
                            <i class="fas fa-hourglass-half fa-3x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm stat-card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase small">แคมเปญ (Active)</h6>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_active_campaigns ?? 0); ?></h3>
                            </div>
                            <i class="fas fa-gift fa-3x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end row stats -->

        <!-- คิวรอตรวจสอบ (Verification Queue) -->
        <h3 class="h5 mb-3">คิวรอการตรวจสอบ (<?php echo count($pending_logs); ?>)</h3>
        
        <div class="card shadow-sm verification-queue">
            <div class="card-body">
                
                <?php if (empty($pending_logs)): ?>
                    <div class="alert alert-success text-center mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        ยอดเยี่ยม! ไม่มีบิลรอการตรวจสอบ
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ผู้ใช้งาน</th>
                                    <th>วันที่ (บิล)</th>
                                    <th>kWh</th>
                                    <th>หลักฐาน (ไฟล์)</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['u_full_name'] ?? $log['u_username']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($log['el_date'])); ?></td>
                                    <td><span class="badge bg-primary"><?php echo number_format($log['el_kwh_usage'], 2); ?></span></td>
                                    <td>
                                        <!-- (แก้ไข) ต้องถอยกลับ 1 ระดับเพื่อไปโฟลเดอร์ uploads/ -->
                                        <a href="../uploads/bills/<?php echo htmlspecialchars($log['el_bill_proof_file']); ?>" target="_blank" class="bill-link">
                                            <i class="fas fa-file-invoice me-1"></i> ดูไฟล์บิล
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <!-- (ลิงก์นี้ถูกต้องแล้ว เพราะ admin_actions.php อยู่ในโฟลเดอร์เดียวกัน) -->
                                        <form method="POST" action="admin_actions.php" class="d-inline-block" onsubmit="return confirm('คุณต้องการอนุมัติบิลนี้ใช่หรือไม่?');">
                                            <input type="hidden" name="action" value="approve_bill">
                                            <input type="hidden" name="log_id" value="<?php echo $log['el_id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> อนุมัติ
                                            </button>
                                        </form>
                                        
                                        <!-- (ลิงก์นี้ถูกต้องแล้ว) -->
                                        <form method="POST" action="admin_actions.php" class="d-inline-block" onsubmit="return confirm('คุณต้องการปฏิเสธบิลนี้ใช่หรือไม่?');">
                                            <input type="hidden" name="action" value="reject_bill">
                                            <input type="hidden" name="log_id" value="<?php echo $log['el_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> ปฏิเสธ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</main>

<!-- (Footer เริ่มต้นที่นี่) -->
<footer class="footer mt-auto py-3 bg-dark text-white-50">
    <div class="container text-center">
        <small>© 2025 Green Digital Tracker (Admin Panel)</small>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>