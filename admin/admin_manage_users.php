<?php
// -----------------------------------------------------------------------------
// Admin: Manage Users (จัดการผู้ใช้)
// -----------------------------------------------------------------------------
$page_title = 'จัดการผู้ใช้';
$current_page = 'users'; // (สำหรับ Active Menu)
require_once 'admin_header.php'; // (เรียก Header และ ตรวจสอบสิทธิ์ Admin)

// --- START: ดึงข้อมูล (GET) ---

// (ลบ PHP Search ออก เพราะ DataTables จะจัดการเอง)
$users_list = [];

try {
    // 2. ดึงรายชื่อผู้ใช้ทั้งหมด (ที่เป็น user)
    $sql_users = "SELECT u_id, u_full_name, u_username, u_email, u_status, u_created_at 
                  FROM users 
                  WHERE u_role = 'user'
                  ORDER BY u_id DESC";
                  
    $result_users = $conn->query($sql_users);
    while ($row = $result_users->fetch_assoc()) {
        $users_list[] = $row;
    }

} catch (Exception $e) {
    $admin_error = "เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้: " . $e->getMessage();
}
// --- END: ดึงข้อมูล (GET) ---

$conn->close();
?>

<!-- (HTML เริ่มต้นใน admin_header.php) -->

<!-- Main Content: Manage Users -->
<main class="main-content">
    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">จัดการผู้ใช้ (<?php echo count($users_list); ?> คน)</h2>
        </div>

        <!-- แสดงข้อความ Error (ถ้ามี) -->
        <?php if (!empty($admin_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($admin_error); ?></div>
        <?php endif; ?>
        
        <!-- (ลบแถบค้นหาของ PHP ออก) -->

        <!-- ตารางแสดงผลผู้ใช้ -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <!-- (ใหม่) เพิ่ม id="usersTable" -->
                    <table class="table table-hover align-middle" id="usersTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>Username</th>
                                <th>อีเมล</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users_list)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">ไม่พบข้อมูลผู้ใช้</td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php foreach ($users_list as $user): ?>
                            <tr>
                                <td><?php echo $user['u_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['u_full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['u_username']); ?></td>
                                <td><?php echo htmlspecialchars($user['u_email']); ?></td>
                                <td class="text-center">
                                    <?php if ($user['u_status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Banned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($user['u_status'] == 'active'): ?>
                                        <!-- ปุ่ม Ban (สีแดง) -->
                                        <form method="POST" action="admin_actions.php" class="d-inline-block" onsubmit="return confirm('คุณต้องการระงับผู้ใช้นี้ใช่หรือไม่?');">
                                            <input type="hidden" name="action" value="ban_user">
                                            <input type="hidden" name="u_id" value="<?php echo $user['u_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-user-slash"></i> ระงับ
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- ปุ่ม Unban (สีเขียว) -->
                                        <form method="POST" action="admin_actions.php" class="d-inline-block" onsubmit="return confirm('คุณต้องการยกเลิกการระงับผู้ใช้นี้ใช่หรือไม่?');">
                                            <input type="hidden" name="action" value="unban_user">
                                            <input type="hidden" name="u_id" value="<?php echo $user['u_id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-user-check"></i> ยกเลิก
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

<!-- (ใหม่) JS Libraries สำหรับ DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>

<!-- (ใหม่) เรียกใช้งาน DataTables -->
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/2.0.8/i18n/th.json"
            },
            // (ปิดการเรียงลำดับคอลัมน์ "จัดการ")
            "columnDefs": [
                { "orderable": false, "targets": 5 }
            ],
            // (เรียงตาม ID (คอลัมน์ 0) จากมากไปน้อย)
            "order": [[ 0, "desc" ]]
        });
    });
</script>

</body>
</html>