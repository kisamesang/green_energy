<?php
// -----------------------------------------------------------------------------
// Admin: Manage Users (จัดการผู้ใช้) - (เวอร์ชันอัปเกรด: Add/Edit/Ban)
// -----------------------------------------------------------------------------
$page_title = 'จัดการผู้ใช้';
$current_page = 'users'; // (สำหรับ Active Menu)
require_once 'admin_header.php'; // (เรียก Header และ ตรวจสอบสิทธิ์ Admin)

// --- START: ดึงข้อมูล (GET) ---
$action = $_GET['action'] ?? 'list'; // (list, create, edit)
$edit_id = (int)($_GET['id'] ?? 0);

$user_data = null; // (สำหรับฟอร์ม Edit)
$users_list = []; // (สำหรับตาราง List)

try {
    // 1. ถ้าเป็นการ "แก้ไข" (Edit)
    if ($action === 'edit' && $edit_id > 0) {
        $result = $conn->query("SELECT u_id, u_full_name, u_username, u_email, u_role, u_status FROM users WHERE u_id = $edit_id AND u_role != 'admin'");
        $user_data = $result->fetch_assoc();
        if (!$user_data) {
            $admin_error = "ไม่พบ User ID: $edit_id หรือเป็นบัญชี Admin";
            $action = 'list'; // กลับไปหน้า List
        }
    }
    // 2. ถ้าเป็นการ "สร้าง" (Create)
    elseif ($action === 'create') {
        // (สร้าง array ว่างๆ เพื่อให้ฟอร์มทำงานได้)
        $user_data = ['u_id' => '', 'u_full_name' => '', 'u_username' => '', 'u_email' => '', 'u_role' => 'user', 'u_status' => 'active'];
    }
    
    // 3. ถ้าเป็นหน้า "รายการ" (List)
    if ($action === 'list') {
        $sql_users = "SELECT u_id, u_full_name, u_username, u_email, u_status, u_created_at 
                      FROM users 
                      WHERE u_role = 'user'
                      ORDER BY u_id DESC";
                      
        $result_users = $conn->query($sql_users);
        while ($row = $result_users->fetch_assoc()) {
            $users_list[] = $row;
        }
    }

} catch (Exception $e) {
    $admin_error = "เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้: " . $e->getMessage();
}
// --- END: ดึงข้อมูล (GET) ---
?>

<!-- (HTML เริ่มต้นใน admin_header.php) -->

<!-- Main Content: Manage Users -->
<main class="main-content">
    <div class="container">
        
        <!-- แสดงข้อความ Error/Success (ถ้ามี) -->
        <?php if (!empty($admin_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($admin_error); ?></div>
        <?php endif; ?>
        <?php if (!empty($admin_success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($admin_success); ?></div>
        <?php endif; ?>


        <?php if ($action === 'list'): ?>
            <!-- --------------------------------- -->
            <!-- 1. หน้า List (แสดงตาราง)           -->
            <!-- --------------------------------- -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">จัดการผู้ใช้ (<?php echo count($users_list); ?> คน)</h2>
                <a href="admin_manage_users.php?action=create" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>เพิ่มผู้ใช้ใหม่
                </a>
            </div>

            <!-- ตารางแสดงผลผู้ใช้ -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <!-- (เพิ่ม id="usersTable") -->
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
                                        <!-- (ใหม่) ปุ่ม Edit (สีฟ้า) -->
                                        <a href="admin_manage_users.php?action=edit&id=<?php echo $user['u_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>

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

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <!-- --------------------------------- -->
            <!-- 2. หน้า Form (สร้าง/แก้ไข)        -->
            <!-- --------------------------------- -->
            <h2 class="h4 mb-4">
                <?php echo ($action === 'create') ? 'เพิ่มผู้ใช้ใหม่' : 'แก้ไขผู้ใช้ (ID: ' . $user_data['u_id'] . ')'; ?>
            </h2>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="admin_actions.php">
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="u_id" value="<?php echo $user_data['u_id']; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="u_full_name" class="form-label">ชื่อ-นามสกุล</label>
                                <input type="text" class="form-control" id="u_full_name" name="u_full_name" value="<?php echo htmlspecialchars($user_data['u_full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="u_email" class="form-label">อีเมล</label>
                                <input type="email" class="form-control" id="u_email" name="u_email" value="<?php echo htmlspecialchars($user_data['u_email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="u_username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="u_username" name="u_username" value="<?php echo htmlspecialchars($user_data['u_username']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="u_password" class="form-label">รหัสผ่าน</label>
                                <input type="password" class="form-control" id="u_password" name="u_password" <?php echo ($action === 'create') ? 'required' : ''; ?>>
                                <div class="form-text">
                                    <?php echo ($action === 'create') ? 'กรุณาตั้งรหัสผ่าน (อย่างน้อย 6 ตัวอักษร)' : 'เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน'; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="u_role" class="form-label">บทบาท (Role)</label>
                                <select class="form-select" id="u_role" name="u_role" required>
                                    <option value="user" <?php echo ($user_data['u_role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo ($user_data['u_role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="u_status" class="form-label">สถานะ (Status)</label>
                                <select class="form-select" id="u_status" name="u_status" required>
                                    <option value="active" <?php echo ($user_data['u_status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="banned" <?php echo ($user_data['u_status'] == 'banned') ? 'selected' : ''; ?>>Banned</option>
                                </select>
                            </div>
                        </div>
                        
                        <hr>
                        <button type="submit" class="btn btn-success me-2"><i class="fas fa-save me-2"></i>บันทึกข้อมูล</button>
                        <a href="admin_manage_users.php" class="btn btn-outline-secondary">ยกเลิก</a>
                    </form>
                </div>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php $conn->close(); // (ปิด $conn ที่นี่) ?>

<!-- (Footer เริ่มต้นที่นี่) -->
<footer class="footer mt-auto py-3 bg-dark text-white-50">
    <div class="container text-center">
        <small>© 2025 Green Digital Tracker (Admin Panel)</small>
    </div>
</footer>

<!-- JS Libraries สำหรับ DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>

<!-- เรียกใช้งาน DataTables -->
<script>
    $(document).ready(function() {
        // (เราจะเรียกใช้ DataTables เฉพาะเมื่อ action='list' เท่านั้น)
        <?php if ($action === 'list'): ?>
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
        <?php endif; ?>
    });
</script>

</body>
</html>