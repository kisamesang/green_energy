<?php
// -----------------------------------------------------------------------------
// Admin: Manage Content (จัดการเนื้อหา - Tips)
// -----------------------------------------------------------------------------
$page_title = 'จัดการเนื้อหา';
$current_page = 'content';
require_once 'admin_header.php'; // (Header และ Auth)

// --- START: ดึงข้อมูล (GET) ---
$action = $_GET['action'] ?? 'list'; // (list, create, edit)
$edit_id = (int)($_GET['id'] ?? 0);

$tip_data = null; // (สำหรับฟอร์ม Edit)
$tips_list = []; // (สำหรับตาราง List)

try {
    // 1. ถ้าเป็นการ "แก้ไข" (Edit)
    if ($action === 'edit' && $edit_id > 0) {
        $result = $conn->query("SELECT * FROM tips WHERE t_id = $edit_id");
        $tip_data = $result->fetch_assoc();
        if (!$tip_data) {
            $admin_error = "ไม่พบ Tip ID: $edit_id";
            $action = 'list'; // กลับไปหน้า List
        }
    }
    // 2. ถ้าเป็นการ "สร้าง" (Create)
    elseif ($action === 'create') {
        // (สร้าง array ว่างๆ เพื่อให้ฟอร์มทำงานได้)
        $tip_data = ['t_id' => '', 't_title' => '', 't_content' => '', 't_category' => 'ทั่วไป', 't_is_active' => 1, 't_display_until' => ''];
    }
    
    // 3. ถ้าเป็นหน้า "รายการ" (List)
    if ($action === 'list') {
        $result = $conn->query("SELECT * FROM tips ORDER BY t_id DESC");
        while ($row = $result->fetch_assoc()) {
            $tips_list[] = $row;
        }
    }

} catch (Exception $e) {
    $admin_error = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
// --- END: ดึงข้อมูล (GET) ---
// (ปิด $conn ใน Footer)
?>

<!-- (HTML เริ่มต้นใน admin_header.php) -->

<!-- Main Content: Manage Content -->
<main class="main-content">
    <div class="container">

        <!-- แสดงข้อความ Error (ถ้ามี) -->
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
                <h2 class="h4 mb-0">จัดการเนื้อหา (เคล็ดลับ)</h2>
                <a href="admin_manage_content.php?action=create" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>เพิ่มเคล็ดลับใหม่
                </a>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <!-- (ใหม่) เพิ่ม id="contentTable" -->
                        <table class="table table-hover align-middle" id="contentTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>หัวข้อ</th>
                                    <th>หมวดหมู่</th>
                                    <th class="text-center">สถานะ</th>
                                    <th>หมดอายุ</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tips_list)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">ยังไม่มีเคล็ดลับ</td></tr>
                                <?php endif; ?>
                                
                                <?php foreach ($tips_list as $tip): ?>
                                <tr>
                                    <td><?php echo $tip['t_id']; ?></td>
                                    <td><?php echo htmlspecialchars($tip['t_title']); ?></td>
                                    <td><?php echo htmlspecialchars($tip['t_category']); ?></td>
                                    <td class="text-center">
                                        <?php echo $tip['t_is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Hidden</span>'; ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($tip['t_display_until']) ? date('d/m/Y', strtotime($tip['t_display_until'])) : '<span class="text-muted">(ไม่มี)</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <!-- ปุ่ม Edit (สีฟ้า) -->
                                        <a href="admin_manage_content.php?action=edit&id=<?php echo $tip['t_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>
                                        <!-- ปุ่ม Delete (สีแดง) -->
                                        <form method="POST" action="admin_actions.php" class="d-inline-block" onsubmit="return confirm('คุณต้องการลบเคล็ดลับนี้ใช่หรือไม่?');">
                                            <input type="hidden" name="action" value="delete_tip">
                                            <input type="hidden" name="t_id" value="<?php echo $tip['t_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> ลบ
                                            </button>
                                        </form>
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
                <?php echo ($action === 'create') ? 'เพิ่มเคล็ดลับใหม่' : 'แก้ไขเคล็ดลับ (ID: ' . $tip_data['t_id'] . ')'; ?>
            </h2>            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="admin_actions.php">
                        <!-- (action นี้จะถูกรับโดย admin_actions.php) -->
                        <input type="hidden" name="action" value="save_tip">
                        <input type="hidden" name="t_id" value="<?php echo $tip_data['t_id']; ?>">

                        <div class="mb-3">
                            <label for="t_title" class="form-label">หัวข้อ (Title)</label>
                            <input type="text" class="form-control" id="t_title" name="t_title" value="<?php echo htmlspecialchars($tip_data['t_title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="t_content" class="form-label">เนื้อหา (Content)</label>
                            <textarea class="form-control" id="t_content" name="t_content" rows="5" required><?php echo htmlspecialchars($tip_data['t_content']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="t_category" class="form-label">หมวดหมู่</label>
                                <input type="text" class="form-control" id="t_category" name="t_category" value="<?php echo htmlspecialchars($tip_data['t_category']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="t_display_until" class="form-label">แสดงผลจนถึงวันที่ (เว้นว่าง = ตลอดไป)</label>
                                <input type="date" class="form-control" id="t_display_until" name="t_display_until" value="<?php echo htmlspecialchars($tip_data['t_display_until']); ?>">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-center">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="t_is_active" name="t_is_active" value="1" <?php echo ($tip_data['t_is_active'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="t_is_active">เปิดใช้งาน (Active)</label>
                                </div>
                            </div>
                        </div>                        
                        <hr>
                        <button type="submit" class="btn btn-success me-2"><i class="fas fa-save me-2"></i>บันทึกข้อมูล</button>
                        <a href="admin_manage_content.php" class="btn btn-outline-secondary">ยกเลิก</a>
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

<!-- (ใหม่) JS Libraries สำหรับ DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>

<!-- (ใหม่) เรียกใช้งาน DataTables -->
<script>
    $(document).ready(function() {
        // (เราจะเรียกใช้ DataTables เฉพาะเมื่อ action='list' เท่านั้น)
        <?php if ($action === 'list'): ?>
        $('#contentTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/2.0.8/i18n/th.json"
            },
            "columnDefs": [
                { "orderable": false, "targets": 5 } // ปิดเรียงคอลัมน์ "จัดการ"
            ],
            "order": [[ 0, "desc" ]] // เรียงตาม ID
        });
        <?php endif; ?>
    });
</script>
</body>
</html>