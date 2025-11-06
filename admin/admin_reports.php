<?php
// -----------------------------------------------------------------------------
// Admin: Reports (ระบบรายงาน)
// -----------------------------------------------------------------------------
$page_title = 'ระบบรายงาน';
$current_page = 'reports';
require_once 'admin_header.php'; // (Header และ Auth)

// (ไฟล์นี้ยังไม่มี Logic PHP ด้านบน)

?>

<!-- (HTML เริ่มต้นใน admin_header.php) -->

<!-- Main Content: Reports -->
<main class="main-content">
    <div class="container">
        
        <h2 class="h4 mb-4">ระบบรายงาน (Export)</h2>

        <!-- แสดงข้อความ Error (ถ้ามี) -->
        <?php if (!empty($admin_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($admin_error); ?></div>
        <?php endif; ?>
        <?php if (!empty($admin_success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($admin_success); ?></div>
        <?php endif; ?>
        
        <div class="row g-4">
            
            <!-- 1. Export PDF -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-file-pdf fa-3x text-danger me-3"></i>
                            <div>
                                <h5 class="card-title mb-0">รายงานข้อมูลผู้ใช้ (PDF)</h5>
                                <p class="card-text text-muted mb-0">ส่งออกรายชื่อ, อีเมล, และสถานะผู้ใช้ทั้งหมด</p>
                            </div>
                        </div>
                        
                        <!-- (ในอนาคต: ลบ class 'disabled' และชี้ไปยังไฟล์ export_users_pdf.php) -->
                        <a href="#" class="btn btn-danger disabled" aria-disabled="true">
                            <i class="fas fa-download me-2"></i>Export as PDF
                        </a>
                        <p class="form-text mt-2">
                            *ฟีเจอร์นี้ต้องมีการติดตั้ง Library <strong>mPDF</strong> (ตามเกณฑ์ 3.3.9.4) บนเซิร์ฟเวอร์ก่อน
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. Export Excel -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-file-excel fa-3x text-success me-3"></i>
                            <div>
                                <h5 class="card-title mb-0">รายงาน Energy Log (Excel)</h5>
                                <p class="card-text text-muted mb-0">ส่งออกข้อมูลการบันทึกพลังงานทั้งหมด (kWh, ค่าน้ำ, สถานะ)</p>
                            </div>
                        </div>
                        
                        <!-- (ในอนาคต: ลบ class 'disabled' และชี้ไปยังไฟล์ export_logs_excel.php) -->
                        <a href="#" class="btn btn-success disabled" aria-disabled="true">
                            <i class="fas fa-download me-2"></i>Export as Excel (XLSX)
                        </a>
                        <p class="form-text mt-2">
                            *ฟีเจอร์นี้ต้องมีการติดตั้ง Library <strong>PhpSpreadsheet</strong> (ตามเกณฑ์ 3.3.9.4) บนเซิร์ฟเวอร์ก่อน
                        </p>
                    </div>
                </div>
            </div>

        </div> <!-- end .row -->
        
    </div>
</main>

<?php $conn->close(); // (ปิด $conn ที่นี่) ?>

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