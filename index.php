<?php

require_once 'db_connect.php'; 


if (isset($_SESSION['u_id'])) {
    header('Location: dashboard.php'); 
    exit;
}


$total_users = 0;
$total_kwh_saved = 0;
$total_cost_saved = 0;
$partners_list = []; 

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
    

    $sql_partners = "SELECT p_name, p_website_url, p_logo FROM partners WHERE p_is_active = 1 LIMIT 5";
    $result_partners = $conn->query($sql_partners);
    if ($result_partners) {
        while ($row = $result_partners->fetch_assoc()) {
            $partners_list[] = $row;
        }
        $result_partners->free();
    }


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
    <link href="assets/bootstrap/bootstrap-5.3.8/dist/css/bootstrap.css" rel="stylesheet">


    <style>
    
.profile-icon {
  width: 10px; 
  height: auto; 
}


.profile-icon-small {
  width: 24px;
  height: auto;
}
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
            color: #198754; 
            font-size: 1.25rem;
            margin-right: 0.5rem;
        }
        .summary-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529; 
            margin-top: 0.5rem;
        }
        .summary-card h3 small {
            font-size: 1.5rem;
            font-weight: 500;
            color: #6c757d; 
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
        .feature-card .icon-track { color: #0dcaf0; } 
        .feature-card .icon-goal { color: #ffc107; } 
        .feature-card .icon-tips { color: #dc3545; } 
        
        .feature-card h4 {
            font-weight: 700;
            color: #333;

        }
     

        .partner-section {
            background-color: #e9ecef;
            padding: 4rem 0;
        }
        
      
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
      
            color: #6c757d;
            font-weight: 500;
        }
   
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar (แบบง่าย) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                Green Digital Tracker
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

    
    <header class="hero-section">
        <div class="container">
            <h1 class="display-4">
            
            
            <svg viewBox="0 0 348 210" width="70" style="fill : #FFF" xmlns="" xmlns:xlink="" xml:space="preserve" overflow="hidden"><g transform="translate(-16 -47)"><path d="M77.5222 123.027C105.04 119.16 130.741 135.452 139.698 160.653L139.894 161.456 140.793 163.057C143.799 169.895 145.514 177.435 145.626 185.374L146.167 223.783 144.373 221.695C140.756 217.73 137.133 213.991 133.815 210.803 120.543 198.053 92.4445 178.172 88.7159 178.135 84.9873 178.099 100.813 195.637 111.443 210.586 116.758 218.06 124.638 230.577 132.381 242.121L133.043 243.059 88.9392 243.68C65.1234 244.015 44.4857 229.823 35.4671 209.309L34.0974 205.108 31.3522 199.286C30.0729 195.685 29.1351 191.903 28.5825 187.972L20.5798 131.03ZM248.693 51.2852C254.189 50.8008 259.804 50.7923 265.491 51.2987L359.642 59.6821 351.258 153.833C350.679 160.333 349.456 166.621 347.656 172.638L343.635 182.425 341.738 189.438C328.644 223.868 295.948 248.891 256.822 250.329L184.364 252.992 185.372 251.398C197.12 231.798 209.012 210.591 217.114 197.877 233.316 172.448 257.833 142.334 251.715 142.705 245.597 143.076 201.128 178.061 180.403 200.102 175.222 205.613 169.586 212.054 163.98 218.865L161.209 222.443 158.89 159.341C158.41 146.299 160.597 133.777 164.961 122.3L166.303 119.596 166.558 118.262C177.583 81.4057 210.225 54.6776 248.693 51.2852Z" stroke="" stroke-width="6.875" stroke-miterlimit="8" fill-rule="evenodd"/></g></svg>
            Green Digital Tracker

            </h1>
            <p class="lead">
                เปลี่ยนการใช้พลังงานให้เป็นพลังบวก! ติดตาม, ลดการใช้, และสร้างผลกระทบเชิงบวกต่อสิ่งแวดล้อม
                เริ่มต้นบันทึกการใช้พลังงานเพื่อสะสมแต้มและรับสิทธิประโยชน์มากมาย
            </p>
            <a href="register.php" class="btn btn-light btn-lg fw-bold me-2">
                 เริ่มต้นใช้งาน (ฟรี)
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                เข้าสู่ระบบ
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="py-5">
        <div class="container">

            <section class="mb-5 text-start">
                <h2 class="section-title">
                
                ผลกระทบเชิงบวกต่อสิ่งแวดล้อม (โดยรวม)
            
            </h2>
                <div class="row g-4">
                    <!-- ผู้ใช้งานรวม -->
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-title-icon">ผู้ใช้งานรวม</div>
                            <h3><?php echo number_format($total_users); ?> <small>บัญชี</small></h3>
                            <p>ชุมชนผู้ใช้ที่ใส่ใจสิ่งแวดล้อมที่เติบโตอย่างต่อเนื่อง</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-title-icon">พลังงานที่ลดได้</div>
                            <h3><?php echo number_format($total_kwh_saved, 0); ?> <small>kWh</small></h3>
                            <p>เทียบเท่าการลดการปล่อย CO2 จำนวนมาก</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-title-icon">ค่าใช้จ่ายที่ประหยัด</div>
                            <h3><?php echo number_format($total_cost_saved, 0); ?> <small>บาท</small></h3>
                            <p>ประหยัดค่าใช้จ่ายสะสมคืนกลับสู่ผู้ใช้งาน</p>
                        </div>
                    </div>
                </div>
            </section>


            <section class="mb-5">
                <h2 class="section-title text-center">คุณสมบัติหลักของระบบ</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="feature-card">
                           
                            <h4 class="fw-bold ">ติดตามการใช้แบบเรียลไทม์</h4>
                            <p>บันทึกข้อมูลมิเตอร์ด้วยตนเอง และดูกราฟเปรียบเทียบการใช้พลังงานในแต่ละเดือน</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            
                            <h4 class="fw-bold">กำหนดเป้าหมายการประหยัด</h4>
                            <p>ตั้งเป้าหมายการใช้พลังงาน (kWh) และติดตามความคืบหน้า เพื่อรับ Badge จูงใจ</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            
                            <h4 class="fw-bold">เคล็ดลับส่วนบุคคล</h4>
                            <p>รับคำแนะนำในการประหยัดพลังงานที่เหมาะสมกับรูปแบบการใช้งานของคุณโดยเฉพาะ</p>
                        </div>
                    </div>
                </div>
            </section>


            <section class="partner-section">
                <div class="container text-center">
                    <h2 class="fw-bold mb-4">พันธมิตรและผู้สนับสนุน </h2>
                    <p class="lead mb-4">เราร่วมมือกับแบรนด์ที่ใส่ใจสิ่งแวดล้อมเพื่อมอบสิทธิประโยชน์ (คูปองส่วนลด) ให้กับคุณ</p>
                    

                    <div class="row g-3 justify-content-center align-items-stretch">
                        <?php if (empty($partners_list)): ?>
                            <p class="text-muted">(ยังไม่มีข้อมูลพันธมิตรในขณะนี้)</p>
                        <?php else: ?>
                            <?php foreach ($partners_list as $partner): ?>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="<?php echo htmlspecialchars($partner['p_website_url']); ?>" target="_blank" class="text-decoration-none d-block h-100">
                                        <div class="partner-logo-item shadow-sm h-100 p-3">
                                            <?php if (!empty($partner['p_logo'])): ?>
                                                
                                                <img src="img/partners/<?php echo htmlspecialchars($partner['p_logo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($partner['p_name']); ?>" 
                                                     class="img-fluid">
                                            <?php else: ?>
                                                <
                                                <span class="text-muted small"><?php echo htmlspecialchars($partner['p_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    
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


</body>
</html>

