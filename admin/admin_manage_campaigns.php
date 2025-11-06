<?php
// -----------------------------------------------------------------------------
// Admin: Manage Campaigns (จัดการแคมเปญ)
// -----------------------------------------------------------------------------
$page_title = 'จัดการแคมเปญ';
$current_page = 'campaigns';
require_once 'admin_header.php'; // (Header และ Auth)

// --- START: ดึงข้อมูล (GET) ---
$action = $_GET['action'] ?? 'list'; // (list, create, edit)
$edit_id = (int)($_GET['id'] ?? 0);

$campaign_data = null; // (สำหรับฟอร์ม Edit)
$campaigns_list = []; // (สำหรับตาราง List)

try {
    // 1. ถ้าเป็นการ "แก้ไข" (Edit)
    if ($action === 'edit' && $edit_id > 0) {
        $result = $conn->query("SELECT * FROM campaigns WHERE c_id = $edit_id");
        $campaign_data = $result->fetch_assoc();
        if (!$campaign_data) {
            $admin_error = "ไม่พบ Campaign ID: $edit_id";
            $action = 'list'; // กลับไปหน้า List
        }
    }
    // 2. ถ้าเป็นการ "สร้าง" (Create)
    elseif ($action === 'create') {
        // (สร้าง array ว่างๆ เพื่อให้ฟอร์มทำงานได้)
        $campaign_data = ['c_id' => '', 'c_title' => '', 'c_description' => '', 'c_partner_name' => '', 'c_reward_value' => '', 'c_reduction_target' => '', 'c_is_active' => 1, 'c_expires_at' => ''];
    }
    
    // 3. ถ้าเป็นหน้า "รายการ" (List)
    if ($action === 'list') {
        $result = $conn->query("SELECT * FROM campaigns ORDER BY c_id DESC");
        while ($row = $result->fetch_assoc()) {
            $campaigns_list[] = $row;
        }
    }

} catch (Exception $e) {
    $admin_error = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
// --- END: ดึงข้อมูล (GET) ---
?>

<!-- (HTML เริ่มต้นใน admin_header.php) -->

<!-- Main Content: Manage Campaigns -->
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
                <h2 class="h4 mb-0">จัดการแคมเปญ</h2>
                <a href="admin_manage_campaigns.php?action=create" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>สร้างแคมเปญใหม่
                </a>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <!-- (ใหม่) เพิ่ม id="campaignsTable" -->
                        <table class="table table-hover align-middle" id="campaignsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อแคมเปญ</th>
                                    <th>Partner</th>
                                    <th class="text-center">เป้าลด (%)</th>
                                    <th class="text-center">รางวัล (บาท)</th>
                                    <th class="text-center">สถานะ</th>
                                    <th>หมดเขต</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($campaigns_list)): ?>
                                    <tr><td colspan="8" class="text-center text-muted">ยังไม่มีแคมเปญ</td></tr>
                                <?php endif; ?>
                                
                                <?php foreach ($campaigns_list as $campaign): ?>
                                <tr>
                                    <td><?php echo $campaign['c_id']; ?></td>
                                    <td><?php echo htmlspecialchars($campaign['c_title']); ?></td>
                                    <td><?php echo htmlspecialchars($campaign['c_partner_name']); ?></td>
                                    <td class="text-center"><?php echo (float)$campaign['c_reduction_target'] * 100; ?>%</td>
                                    <td class="text-center"><?php echo number_format($campaign['c_reward_value']); ?></td>
                                    <td class="text-center">
                                        <?php echo $campaign['c_is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Disabled</span>'; ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($campaign['c_expires_at']) ? date('d/m/Y', strtotime($campaign['c_expires_at'])) : '<span class="text-muted">(ไม่มี)</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <!-- ปุ่ม Edit (สีฟ้า) -->
                                        <a href="admin_manage_campaigns.php?action=edit&id=<?php echo $campaign['c_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>
                                        <!-- ปุ่ม Delete (สีแดง) -->
                                        <form method="POST" action="admin_actions.php" class="d-inline-block" onsubmit="return confirm('คุณต้องการลบแคมเปญนี้ใช่หรือไม่?');">
                                            <input type="hidden" name="action" value="delete_campaign">
                                            <input type="hidden" name="c_id" value="<?php echo $campaign['c_id']; ?>">
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
                <?php echo ($action === 'create') ? 'สร้างแคมเปญใหม่' : 'แก้ไขแคมเปญ (ID: ' . $campaign_data['c_id'] . ')'; ?>
            </h2>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="admin_actions.php">
                        <!-- (action นี้จะถูกรับโดย admin_actions.php) -->
                        <input type="hidden" name="action" value="save_campaign">
                        <input type="hidden" name="c_id" value="<?php echo $campaign_data['c_id']; ?>">

                        <div class="mb-3">
                            <label for="c_title" class="form-label">ชื่อแคมเปญ (Title)</label>
                            <input type="text" class="form-control" id="c_title" name="c_title" value="<?php echo htmlspecialchars($campaign_data['c_title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="c_description" class="form-label">คำอธิบาย (Description)</label>
                            <textarea class="form-control" id="c_description" name="c_description" rows="3" required><?php echo htmlspecialchars($campaign_data['c_description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="c_partner_name" class="form-label">ชื่อ Partner (ผู้สนับสนุน)</label>
                                <input type="text" class="form-control" id="c_partner_name" name="c_partner_name" value="<?php echo htmlspecialchars($campaign_data['c_partner_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="c_reward_value" class="form-label">มูลค่ารางวัล (บาท)</label>
                                <input type="number" class="form-control" id="c_reward_value" name="c_reward_value" value="<?php echo htmlspecialchars($campaign_data['c_reward_value']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="c_reduction_target" class="form-label">เป้าหมายการลด (เช่น 0.20 สำหรับ 20%)</label>
                                <input type="number" step="0.01" min="0.01" max="1.00" class="form-control" id="c_reduction_target" name="c_reduction_target" value="<?php echo htmlspecialchars($campaign_data['c_reduction_target']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="c_expires_at" class="form-label">หมดเขตรับข้อเสนอ (เว้นว่าง = ตลอดไป)</label>
                                <input type="date" class="form-control" id="c_expires_at" name="c_expires_at" value="<?php echo htmlspecialchars($campaign_data['c_expires_at']); ?>">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-center">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="c_is_active" name="c_is_active" value="1" <?php echo ($campaign_data['c_is_active'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="c_is_active">เปิดใช้งาน (Active)</label>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <button type="submit" class="btn btn-success me-2"><i class="fas fa-save me-2"></i>บันทึกข้อมูล</button>
                        <a href="admin_manage_campaigns.php" class="btn btn-outline-secondary">ยกเลิก</a>
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
        $('#campaignsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/2.0.8/i18n/th.json"
            },
            "columnDefs": [
                { "orderable": false, "targets": 7 } // ปิดเรียงคอลัมน์ "จัดการ"
            ],
            "order": [[ 0, "desc" ]] // เรียงตาม ID
        });
        <?php endif; ?>
    });
</script>

</body>
</html>