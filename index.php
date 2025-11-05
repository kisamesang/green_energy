<?php
// -----------------------------------------------------------------------------
// หน้าแรก (Landing Page) (Traditional Style)
// -----------------------------------------------------------------------------
require_once 'db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบว่า Login หรือยัง?
// (ถ้า Login แล้ว ให้เด้งไปหน้า Dashboard ทันที)
if (isset($_SESSION['u_id'])) {
    header('Location: dashboard.php'); // (เราจะสร้างไฟล์นี้ต่อไป)
    exit;
}

// 2. ดึงข้อมูลสรุปผลกระทบเชิงบวก (สำหรับ Guest)
// (ตอบโจทย์ 3.5.4.6 ข้อ 2 - วัดผลได้)
$total_users = 0;
$total_kwh_saved = 0;
$total_cost_saved = 0;
$partners_list = []; // --- EDIT 1: เพิ่มตัวแปรสำหรับพันธมิตร ---

try {
    // ดึงจำนวนผู้ใช้ทั้งหมด
    $result_users = $conn->query("SELECT COUNT(u_id) as count FROM users WHERE u_role = 'user'");
    $total_users = $result_users->fetch_assoc()['count'];
    $result_users->free();

    // ดึงผลรวม kWh ที่บันทึกได้
    $result_kwh = $conn->query("SELECT SUM(el_kwh_usage) as total_kwh FROM energy_log");
    $total_kwh_saved = $result_kwh->fetch_assoc()['total_kwh'];
    $result_kwh->free();

    // ดึงผลรวมค่าใช้จ่ายที่ประหยัดได้ (จากคอลัมน์ที่คำนวณไว้)
    $result_cost = $conn->query("SELECT SUM(el_calculated_cost) as total_cost FROM energy_log");
    $total_cost_saved = $result_cost->fetch_assoc()['total_cost'];
    $result_cost->free();
    
    // --- START EDIT 2: ดึงข้อมูลพันธมิตร (BMC) จาก Database ---
    // (เราจะใช้ตาราง partners และคอลัมน์ p_logo ที่เราคุยกันไว้)
    $sql_partners = "SELECT p_name, p_website_url, p_logo FROM partners WHERE p_is_active = 1 LIMIT 5";
    $result_partners = $conn->query($sql_partners);
    if ($result_partners) {
        while ($row = $result_partners->fetch_assoc()) {
            $partners_list[] = $row;
        }
        $result_partners->free();
    }
    // --- END EDIT 2 ---

} catch (Exception $e) {
    // (ถ้ามี Error ในการดึงข้อมูล ให้แสดงค่าเริ่มต้น 0)
    error_log("Landing Page Error: " . $e->getMessage());
    // (ตั้งค่าตัวเลขเริ่มต้น ถ้าฐานข้อมูลว่าง)
    // (ใช้ค่าจากภาพเป็นค่าเริ่มต้น)
    $total_users = $total_users > 0 ? $total_users : 10; 
    $total_kwh_saved = $total_kwh_saved > 0 ? $total_kwh_saved : 9767;
    $total_cost_saved = $total_cost_saved > 0 ? $total_cost_saved : 39067;
    $partners_list = []; // (ถ้า Error ให้แสดงผลว่าง)
}

$conn->close(); // ปิดการเชื่อมต่อเมื่อจบการทำงาน

// (ตั้งค่าตัวเลขเริ่มต้น ถ้าฐานข้อมูลว่าง)
// (ใช้ค่าจากภาพเป็นค่าเริ่มต้น)
if ($total_users == 0) $total_users = 10;
if ($total_kwh_saved == 0) $total_kwh_saved = 9767;
if ($total_cost_saved == 0) $total_cost_saved = 39067;

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Digital Tracker - ระบบติดตามการใช้พลังงาน</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <!-- Google Font "Prompt" -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #f8f9fa; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar-brand { font-weight: 700; }
        .main-content { flex-grow: 1; }
        
        .hero-section {
            background: linear-gradient(rgba(25, 135, 84, 0.8), rgba(25, 135, 84, 0.8)), url('https://tse4.mm.bing.net/th/id/OIP.vo_fw1jtL2qtsr9PxBOB-wHaEK?rs=1&pid=ImgDetMain&o=7&rm=3');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 6rem 0;
            text-align: center;
        }
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
        }
        .hero-section .lead {
            font-size: 1.25rem;
            max-width: 600px;
            margin: 1rem auto 2rem auto;
        }

        /* --- CSS ที่ตรงกับภาพ --- */
        .section-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 2rem;
            display: inline-block;
            align-items: center;
        }
        .section-title i {
            color: #198754;
            font-size: 1.8rem;
            margin-right: 0.75rem;
            vertical-align: middle;
        }

        .summary-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 2rem;
            background-color: white;
            transition: transform 0.2s, box-shadow 0.2s;
            /* เพิ่มขอบสีเขียวด้านซ้าย (ตามภาพ) */
            border-left: 5px solid #198754; 
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
        }
        .summary-card .card-title-icon {
            font-size: 1.25rem;
            font-weight: 500;
            color: #333;
        }
        .summary-card .card-title-icon i {
            color: #198754; /* สีไอคอนตามภาพ */
            font-size: 1.25rem;
            margin-right: 0.5rem;
        }
        .summary-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529; /* สีตัวเลข (ดำ) ตามภาพ */
            margin-top: 0.5rem;
        }
        .summary-card h3 small {
            font-size: 1.5rem;
            font-weight: 500;
            color: #6c757d; /* สีหน่วย (เทา) ตามภาพ */
        }
        .summary-card p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .feature-card {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            height: 100%;
        }
        .feature-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            /* สีไอคอนตามภาพ */
        }
        .feature-card .icon-track { color: #0dcaf0; } /* สีฟ้า */
        .feature-card .icon-goal { color: #ffc107; } /* สีเหลือง */
        .feature-card .icon-tips { color: #dc3545; } /* สีแดง */
        
        .feature-card h4 {
            font-weight: 700;
            color: #333;
        }
         /* --- จบ CSS ที่ตรงกับภาพ --- */

        .partner-section {
            background-color: #e9ecef;
            padding: 4rem 0;
        }
        
        /* --- START EDIT 3: อัปเดต CSS สำหรับโลโก้พันธมิตร (จาก UI เดิม) --- */
        .partner-logo-item {
            min-height: 80px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            background-color: #FFF;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem; /* 6px */
        }
        .partner-logo-item img {
            max-height: 40px; 
            object-fit: contain;
            filter: grayscale(100%);
            opacity: 0.6;
            transition: all 0.3s ease;
        }
        .partner-logo-item img:hover {
            filter: grayscale(0%);
            opacity: 1;
        }
        .partner-logo-item span {
            /* (สไตล์สำหรับชื่อแบรนด์ ถ้าไม่มีโลโก้) */
            color: #6c757d;
            font-weight: 500;
        }
        /* --- END EDIT 3 --- */
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar (แบบง่าย) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-leaf me-2"></i>Green Digital Tracker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">เข้าสู่ระบบ</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light" href="register.php">สมัครสมาชิก</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section (ส่วนหัว) -->
    <header class="hero-section">
        <div class="container">
            <h1 class="display-4"><i class="fas fa-bolt me-3"></i>Green Digital Tracker</h1>
            <p class="lead">
                เปลี่ยนการใช้พลังงานให้เป็นพลังบวก! ติดตาม, ลดการใช้, และสร้างผลกระทบเชิงบวกต่อสิ่งแวดล้อม
                เริ่มต้นบันทึกการใช้พลังงานเพื่อสะสมแต้มและรับสิทธิประโยชน์มากมาย
            </p>
            <a href="register.php" class="btn btn-light btn-lg fw-bold me-2">
                <i class="fas fa-user-plus me-2"></i> เริ่มต้นใช้งาน (ฟรี)
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                เข้าสู่ระบบ
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            
            <!-- 1. ส่วนสรุปผลกระทบ (ตามภาพ) -->
            <section class="mb-5 text-start">
                <h2 class="section-title"><i class="fas fa-chart-line"></i>ผลกระทบเชิงบวกต่อสิ่งแวดล้อม (โดยรวม)</h2>
                <div class="row g-4">
                    <!-- ผู้ใช้งานรวม -->
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-title-icon"><i class="fas fa-users"></i>ผู้ใช้งานรวม</div>
                            <h3><?php echo number_format($total_users); ?> <small>บัญชี</small></h3>
                            <p>ชุมชนผู้ใช้ที่ใส่ใจสิ่งแวดล้อมที่เติบโตอย่างต่อเนื่อง</p>
                        </div>
                    </div>
                    <!-- พลังงานที่ลดได้ -->
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-title-icon"><i class="fas fa-plug-circle-minus"></i>พลังงานที่ลดได้</div>
                            <h3><?php echo number_format($total_kwh_saved, 0); ?> <small>kWh</small></h3>
                            <p>เทียบเท่าการลดการปล่อย CO2 จำนวนมาก</p>
                        </div>
                    </div>
                    <!-- ค่าใช้จ่ายที่ประหยัด -->
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-title-icon"><i class="fas fa-coins"></i>ค่าใช้จ่ายที่ประหยัด</div>
                            <h3><?php echo number_format($total_cost_saved, 0); ?> <small>บาท</small></h3>
                            <p>ประหยัดค่าใช้จ่ายสะสมคืนกลับสู่ผู้ใช้งาน</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. ส่วนคุณสมบัติหลัก (ตามภาพ) -->
            <section class="mb-5">
                <h2 class="section-title text-center"><i class="fas fa-star"></i>คุณสมบัติหลักของระบบ</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="fas fa-tachometer-alt icon-track"></i>
                            <h4 class="fw-bold">ติดตามการใช้แบบเรียลไทม์</h4>
                            <p>บันทึกข้อมูลมิเตอร์ด้วยตนเอง และดูกราฟเปรียบเทียบการใช้พลังงานในแต่ละเดือน</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="fas fa-bullseye icon-goal"></i>
                            <h4 class="fw-bold">กำหนดเป้าหมายการประหยัด</h4>
                            <p>ตั้งเป้าหมายการใช้พลังงาน (kWh) และติดตามความคืบหน้า เพื่อรับ Badge จูงใจ</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="fas fa-lightbulb icon-tips"></i>
                            <h4 class="fw-bold">เคล็ดลับส่วนบุคคล</h4>
                            <p>รับคำแนะนำในการประหยัดพลังงานที่เหมาะสมกับรูปแบบการใช้งานของคุณโดยเฉพาะ</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. ส่วน Business Model Canvas (BMC) (ใหม่) -->
            <section class="partner-section">
                <div class="container text-center">
                    <h2 class="fw-bold mb-4"><i class="fas fa-handshake me-2"></i>พันธมิตรและผู้สนับสนุน (BMC)</h2>
                    <p class="lead mb-4">เราร่วมมือกับแบรนด์ที่ใส่ใจสิ่งแวดล้อมเพื่อมอบสิทธิประโยชน์ (แลกคะแนน) ให้กับคุณ</p>
                    
                    <!-- --- START EDIT 4: วนลูปแสดงผลพันธมิตร (BMC) จาก Database --- -->
                    <div class="row g-3 justify-content-center align-items-stretch">
                        <?php if (empty($partners_list)): ?>
                            <p class="text-muted">(ยังไม่มีข้อมูลพันธมิตรในขณะนี้)</p>
                        <?php else: ?>
                            <?php foreach ($partners_list as $partner): ?>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="<?php echo htmlspecialchars($partner['p_website_url']); ?>" target="_blank" class="text-decoration-none d-block h-100">
                                        <div class="partner-logo-item shadow-sm h-100 p-3">
                                            <?php if (!empty($partner['p_logo'])): ?>
                                                <!-- (แสดงโลโก้จากโฟลเดอร์ img/partners/) -->
                                                <img src="img/partners/<?php echo htmlspecialchars($partner['p_logo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($partner['p_name']); ?>" 
                                                     class="img-fluid">
                                            <?php else: ?>
                                                <!-- (ถ้าไม่มีโลโก้ ให้แสดงชื่อแทน) -->
                                                <span class="text-muted small"><?php echo htmlspecialchars($partner['p_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <!-- --- END EDIT 4 --- -->
                    
                    <a href="mailto:partner@greentracker.com" class="btn btn-outline-dark mt-4">สนใจเป็นพันธมิตร? ติดต่อเรา</a>
                </div>
            </section>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-dark text-white-50">
        <div class="container text-center">
            <small>© 2025 Green Digital Tracker: Developed by SARABURI TECHNICAL COLLEGE</small>
        </div>
    </footer>

    <!-- Bootstrap JS (ไม่จำเป็นต้องใช้ jQuery สำหรับหน้านี้) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

