<?php

require_once 'db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['u_id'])) {
    header('Location: login.php');
    exit;
}

// 2. ดึงข้อมูลผู้ใช้จาก Session
$user_id = $_SESSION['u_id']; 
$user_role = $_SESSION['u_role'];
$user_username = $_SESSION['u_username'];

// 3. ดึงชื่อเต็มของผู้ใช้ (สำหรับแสดงผล)
try {
    $sql_user = "SELECT u_full_name FROM users WHERE u_id = $user_id";
    $result_user = $conn->query($sql_user);
    $user_info = $result_user->fetch_assoc();
    $user_full_name = $user_info['u_full_name'] ?? $user_username;
    $_SESSION['u_full_name'] = $user_full_name; // (เก็บไว้ให้หน้าอื่นใช้)
} catch (Exception $e) {
    die("Error fetching user data: " + $e->getMessage());
}

// --- กำหนดตัวแปรสำหรับข้อความแจ้งเตือน ---
$error_message = '';
$success_message = '';

// --- (ย้ายมา) ตรวจสอบข้อความแจ้งเตือนพิเศษจากแคมเปญ ---
if (isset($_SESSION['campaign_success_message'])) {
    $success_message = $_SESSION['campaign_success_message'];
    unset($_SESSION['campaign_success_message']);
}
if (isset($_SESSION['campaign_fail_message'])) {
    $error_message = $_SESSION['campaign_fail_message'];
    unset($_SESSION['campaign_fail_message']);
}

$kwh_rate = 4.00; 
$co2_rate = 0.5; 

// --- START: ส่วนการประมวลผล POST (คงเดิม) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $action = $_POST['action'] ?? '';

    try {
        // --- ACTION 1: บันทึกการใช้พลังงาน (คงเดิม) ---
        if ($action === 'log_energy') {
            $kwh_usage = $_POST['kwh_usage'] ?? 0;
            $water_usage = $_POST['water_usage'] ?? 0; 
            $log_date = $_POST['log_date'] ?? date('Y-m-d');

            // (ส่วนตรวจสอบข้อมูลเบื้องต้น และการอัปโหลดไฟล์ คงเดิม)
            if (empty($kwh_usage) || empty($log_date)) {
                throw new Exception("ข้อมูล kWh และวันที่ ห้ามว่าง");
            }
            if (empty($_FILES['bill_proof_file']['name'])) {
                throw new Exception("กรุณาแนบไฟล์หลักฐาน (บิลค่าไฟ)");
            }
            
            $calculated_cost = $kwh_usage * $kwh_rate;
            $period_start = date('Y-m-01', strtotime($log_date));
            $month_year = date('Y-m', strtotime($log_date));
            
            // (ส่วนประมวลผลไฟล์ คงเดิม)
            $upload_dir = 'uploads/bills/'; 
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); 
            }
            $file = $_FILES['bill_proof_file'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($file_ext, $allowed_exts)) {
                throw new Exception("อนุญาตเฉพาะไฟล์นามสกุล: JPG, JPEG, PNG, PDF เท่านั้น");
            }
            if ($file['size'] > 2 * 1024 * 1024) { 
                throw new Exception("ขนาดไฟล์ต้องไม่เกิน 2MB");
            }
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์");
            }
            $unique_filename = $user_id . '_' . $month_year . '_' . uniqid() . '.' . $file_ext;
            $destination = $upload_dir . $unique_filename;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception("ไม่สามารถย้ายไฟล์ไปยัง " . $destination);
            }

            // ตรวจสอบการบันทึกซ้ำ (คงเดิม)
            $sql_check = "SELECT el_id FROM energy_log WHERE u_id = $user_id AND DATE_FORMAT(el_date, '%Y-%m') = '$month_year'";
            $result_check = $conn->query($sql_check);
            if ($result_check->num_rows > 0) {
                throw new Exception('คุณได้บันทึกข้อมูลสำหรับเดือนนี้ไปแล้ว');
            }

        
// ... ส่วนของโค้ดที่ต้องมีก่อนหน้า เช่น การเชื่อมต่อฐานข้อมูล ($conn)
// และการกำหนดค่าตัวแปรสำหรับ INSERT เช่น $user_id, $kwh_usage ฯลฯ

// ... ส่วนของโค้ดที่เตรียมตัวแปรและการเชื่อมต่อฐานข้อมูล

// SQL INSERT (คงเดิม)
// บันทึกข้อมูลเข้า energy_log ด้วยสถานะเริ่มต้นเป็น 'pending'
$sql_insert = "INSERT INTO energy_log (u_id, el_date, el_kwh_usage, el_water_usage, el_period_start, el_calculated_cost, el_bill_proof_file, el_verification_status) 
               VALUES ($user_id, '$log_date', $kwh_usage, $water_usage, '$period_start', $calculated_cost, '$unique_filename', 'pending')";

if ($conn->query($sql_insert) === TRUE) {
    
    // ดึง ID ของรายการที่เพิ่งบันทึกสำเร็จ (เพื่อใช้ในอนาคต หากต้องการ)
    $last_el_id = $conn->insert_id; 
    
    // แสดงข้อความแจ้งผู้ใช้ว่าต้องรอการตรวจสอบ
    $success_message = 'บันทึกข้อมูลสำเร็จ! (รอการตรวจสอบจากผู้ดูแลระบบ)';
    
    // *** ลบโค้ดตรวจสอบแคมเปญเดิมทั้งหมดออก ***
    
} else {
    // การจัดการข้อผิดพลาดในการบันทึกข้อมูลหลัก
    throw new Exception("ไม่สามารถบันทึกข้อมูลได้: " . $conn->error);
}

// ... ส่วนของโค้ดที่เหลือ

        
        // --- ACTION 2: ตั้งเป้าหมายการประหยัด (คงเดิม) ---
        } elseif ($action === 'set_goal') {
            $goal_month = $_POST['goal_month'] ?? ''; 
            $kwh_goal = $_POST['kwh_goal'] ?? 0;
            if (empty($goal_month) || empty($kwh_goal)) {
                throw new Exception("ข้อมูลเดือนและเป้าหมาย (kWh) ห้ามว่าง");
            }
            $sg_start_date = $goal_month . '-01';
            $sg_end_date = date("Y-m-t", strtotime($sg_start_date));
            $sg_type = 'kwh';
            $sql_goal = "REPLACE INTO saving_goals (u_id, sg_type, sg_target_value, sg_start_date, sg_end_date) 
                         VALUES ($user_id, '$sg_type', $kwh_goal, '$sg_start_date', '$sg_end_date')";
            if ($conn->query($sql_goal) === TRUE) {
                $success_message = 'บันทึกเป้าหมายเรียบร้อยแล้ว';
                header('Location: dashboard.php?view_month=' . $goal_month);
                exit;
            } else {
                throw new Exception("ไม่สามารถบันทึกเป้าหมายได้: " . $conn->error);
            }

        // --- (ลบ) ACTION 3: รับข้อเสนอแคมเปญ (ย้ายไป campaigns.php) ---

        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
} // สิ้นสุดการตรวจสอบ POST

// 5. ดึงข้อมูล (GET) มาแสดงผล (คงเดิม)

// 5.1 ดึง "ค่าใช้จ่ายทั้งหมด" และ "ลด CO2" (สะสม) (คงเดิม)
$total_kwh = 0;
$total_cost = 0;
$sql_summary = "SELECT SUM(el_kwh_usage) as total_kwh, SUM(el_calculated_cost) as total_cost 
                FROM energy_log 
                WHERE u_id = $user_id AND el_verification_status = 'verified'";
$summary_result = $conn->query($sql_summary)->fetch_assoc();
$total_kwh = $summary_result['total_kwh'] ?? 0;
$total_cost = $summary_result['total_cost'] ?? 0;
$total_co2_saved = $total_kwh * $co2_rate;

// 5.2 (ฟีเจอร์) ดึง "ค่าใช้จ่ายที่ประหยัด" (สะสม) (คงเดิม)
$total_saved_cost = 0;
$sql_saved = "SELECT SUM((sg.sg_target_value - el.el_kwh_usage) * $kwh_rate) as total_saved
              FROM energy_log el
              JOIN saving_goals sg ON el.u_id = sg.u_id AND DATE_FORMAT(el.el_date, '%Y-%m-01') = sg.sg_start_date
              WHERE el.u_id = $user_id
              AND el.el_verification_status = 'verified' 
              AND sg.sg_target_value > el.el_kwh_usage"; 
$saved_result = $conn->query($sql_saved);
if ($saved_result) {
    $total_saved_cost = $saved_result->fetch_assoc()['total_saved'] ?? 0;
}

// 5.3 ดึงข้อมูลสำหรับกราฟ (6 เดือนล่าสุด) (คงเดิม)
$chart_labels = [];
$chart_usage_data = [];
$chart_goal_data = [];
$sql_chart_logs = "SELECT DATE_FORMAT(el_date, '%Y-%m') as month_year, el_kwh_usage 
                                   FROM energy_log 
                                   WHERE u_id = $user_id AND el_verification_status = 'verified' 
                                   ORDER BY el_date DESC LIMIT 6";
$logs_result = $conn->query($sql_chart_logs);
$usage_logs_map = [];
while ($row = $logs_result->fetch_assoc()) {
    $chart_labels[] = $row['month_year'];
    $usage_logs_map[$row['month_year']] = $row['el_kwh_usage'];
}
$chart_labels = array_reverse($chart_labels); 
$sql_chart_goals = "SELECT DATE_FORMAT(sg_start_date, '%Y-%m') as month_year, sg_target_value 
                                    FROM saving_goals 
                                    WHERE u_id = $user_id AND sg_type = 'kwh'
                                    ORDER BY sg_start_date DESC LIMIT 6";
$goals_result = $conn->query($sql_chart_goals);
$goals_map = [];
while ($row = $goals_result->fetch_assoc()) {
    $goals_map[$row['month_year']] = $row['sg_target_value'];
}
foreach ($chart_labels as $label) {
    $chart_usage_data[] = $usage_logs_map[$label] ?? 0;
    $chart_goal_data[] = $goals_map[$label] ?? null; 
}

// 5.4 ดึงข้อมูลเป้าหมาย (Progress Bar) (คงเดิม)
$view_month_year = $_GET['view_month'] ?? date('Y-m'); 
$view_month_start = $view_month_year . '-01'; 
$current_goal_value = 0;
$current_usage_value = 0;
$current_progress_percent = 0;
$sql_current_goal = "SELECT sg_target_value FROM saving_goals WHERE u_id = $user_id AND sg_start_date = '$view_month_start' AND sg_type = 'kwh'";
$current_goal_result = $conn->query($sql_current_goal);
if ($current_goal_result && $current_goal_result->num_rows > 0) {
    $current_goal_value = $current_goal_result->fetch_assoc()['sg_target_value'];
}
$sql_current_log = "SELECT el_kwh_usage FROM energy_log 
                    WHERE u_id = $user_id AND DATE_FORMAT(el_date, '%Y-%m') = '$view_month_year' 
                    AND el_verification_status = 'verified'";
$current_log_result = $conn->query($sql_current_log);
if ($current_log_result && $current_log_result->num_rows > 0) {
    $current_usage_value = $current_log_result->fetch_assoc()['el_kwh_usage'];
}
if ($current_goal_value > 0) {
    $current_progress_percent = ($current_usage_value / $current_goal_value) * 100;
    if ($current_progress_percent > 100) $current_progress_percent = 100;
}

// --- (อัปเดต) 5.5 ดึงข้อมูลแจ้งเตือนแคมเปญ (กรองที่หมดอายุ) ---
$new_campaign_count = 0;
$sql_campaigns_count = "SELECT COUNT(c_id) as count FROM campaigns c
                  WHERE c.c_is_active = 1 
                  AND (c.c_expires_at IS NULL OR c.c_expires_at >= CURDATE())
                  AND c.c_id NOT IN (
                      SELECT uc.c_id FROM user_campaigns uc WHERE uc.u_id = $user_id
                  )";
// --- จบส่วนอัปเดต ---
$campaigns_count_result = $conn->query($sql_campaigns_count);
if ($campaigns_count_result) {
    $new_campaign_count = (int)$campaigns_count_result->fetch_assoc()['count'];
}
// --- จบส่วนดึงข้อมูลใหม่ ---

$conn->close(); // ปิดการเชื่อมต่อ
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Green Digital Tracker</title>
    <!-- CSS (คงเดิม) -->
    <link href="assets/bootstrap/bootstrap-5.3.8/dist/css/bootstrap.css" rel="stylesheet">
    
    <style>
        /* (CSS Styles ทั้งหมดคงเดิม) */
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #f8f9fa; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar-brand { font-weight: 700; }
        .main-content { flex-grow: 1; padding: 2rem 0; }
        .card-header-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
        .summary-card h4 {
            font-size: 2.8rem;
            font-weight: 700;
        }
        .summary-card h6 {
            font-size: 1.1rem;
        }
        .progress-view-wrapper {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .progress-view-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .progress-view-form h5 {
            margin-bottom: 0;
            font-weight: 500;
            color: #198754;
        }
        .progress-view-form .input-group {
            width: auto;
        }
        .progress-bar-lg {
             height: 35px;
             font-size: 1.1rem;
             font-weight: 500;
        }
        
        /* (ลบ CSS แคมเปญออก) */
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar (อัปเดตใหม่) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-leaf me-2"></i>Green Digital Tracker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    
                    <?php if ($user_role === 'admin'): ?>
                        <!-- เมนู Admin -->
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">Dashboard (Admin)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/admin_manage_users.php">จัดการผู้ใช้</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/admin_manage_content.php">จัดการเนื้อหา</a>
                        </li>
                    <?php else: ?>
                        <!-- เมนู User (อัปเดต) -->
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="campaigns.php">
                                แคมเปญ
                                <?php if ($new_campaign_count > 0): ?>
                                    <span class="badge bg-danger"><?php echo $new_campaign_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link" href="rewards.php">รางวัล</a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- เมนู Logout (แสดงชื่อ) (อัปเดต) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($user_full_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            <li><a class="dropdown-item" href="profile.php">โปรไฟล์</a></li>
                            <li><a class="dropdown-item" href="tips.php">เคล็ดลับ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content: Dashboard -->
    <main class="main-content">
        <div class="container">
            
            <!-- ส่วน Summary Cards (โค้ดเต็ม) -->
            <h2 class="h4 mb-3">สรุปผลกระทบ (Overall Impact)</h2>
            <div class="row g-4 mb-4">
                
                <!-- Card 1: ค่าใช้จ่ายที่ "ประหยัด" -->
                <div class="col-lg-4 col-md-6">
                    <div class="card summary-card shadow-sm border-0 border-start border-5 border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-piggy-bank fa-3x text-success me-3"></i>
                                <div>
                                    <h6 class="card-title text-muted mb-1">ค่าใช้จ่ายที่ประหยัด (สะสม)</h6>
                                    <h4 class="mb-0 text-success"><?php echo number_format($total_saved_cost, 2); ?> <span class="h6">บาท</span></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: ค่าใช้จ่าย "ทั้งหมด" -->
                <div class="col-lg-4 col-md-6">
                    <div class="card summary-card shadow-sm border-0 border-start border-5 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-dollar-sign fa-3x text-primary me-3"></i>
                                <div>
                                    <h6 class="card-title text-muted mb-1">ค่าใช้จ่ายทั้งหมด (สะสม)</h6>
                                    <h4 class="mb-0"><?php echo number_format($total_cost, 2); ?> <span class="h6">บาท</span></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Card 3: ลด CO2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card summary-card shadow-sm border-0 border-start border-5 border-secondary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-smog fa-3x text-secondary me-3"></i>
                                <div>
                                    <h6 class="card-title text-muted mb-1">ลด CO2 (สะสม)</h6>
                                    <h4 class="mb-0"><?php echo number_format($total_co2_saved, 2); ?> <span class="h6">kg</span></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- (ส่วนแสดงข้อความ Error/Success - โค้ดเต็ม) -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- --- (ใหม่) ส่วนแจ้งเตือนแคมเปญ --- -->
            <?php if ($new_campaign_count > 0): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-gift me-2"></i>
                    <strong>คุณมีแคมเปญใหม่ <?php echo $new_campaign_count; ?> รายการ!</strong>
                    <a href="campaigns.php" class="alert-link">คลิกที่นี่เพื่อดูข้อเสนอ</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <!-- --- จบส่วนแจ้งเตือนแคมเปญ --- -->
            <!-- (แถว กราฟ และ ฟอร์ม - โค้ดเต็ม) -->
            <div class="row g-4">
                
                <!-- (คอลัมน์ซ้าย: กราฟ และ Progress Bar) -->
                <div class="col-lg-7">
                    <!-- Card 1 (ซ้าย): ความคืบหน้าเป้าหมาย -->
                    <div class="card shadow-sm border-0 mb-4 progress-view-wrapper">
                        <div class="card-body">
                            <!-- (ฟอร์มเลือกเดือน) -->
                            <form method="GET" action="dashboard.php" class="progress-view-form">
                                <h5 class="mb-0">ความคืบหน้าเป้าหมายเดือน:</h5>
                                <div class="input-group">
                                    <input type="month" class="form-control" name="view_month" value="<?php echo htmlspecialchars($view_month_year); ?>" style="max-width: 150px;">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-eye me-2"></i>ดู</button>
                                </div>
                            </form>

                            <!-- (Progress Bar) -->
                            <?php if ($current_goal_value > 0): ?>
                            <div class="progress progress-bar-lg" style="height: 35px; font-size: 1.1rem;">
                                <div class="progress-bar <?php echo ($current_progress_percent >= 100) ? 'bg-danger' : 'bg-success'; ?> progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: <?php echo $current_progress_percent; ?>%;" 
                                     aria-valuenow="<?php echo $current_progress_percent; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                     <?php echo number_format($current_usage_value, 0); ?> / <?php echo number_format($current_goal_value, 0); ?> kWh
                                </div>
                            </div>
                            <?php else: ?>
                                <div class="alert alert-light text-center small p-2 mt-2">
                                    ยังไม่ได้ตั้งเป้าหมายสำหรับเดือน <strong><?php echo date("F Y", strtotime($view_month_start)); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card 2 (ซ้าย): กราฟ (โค้ดเดิม) -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chart-bar card-header-icon"></i>
                            กราฟเปรียบเทียบการใช้ไฟฟ้า 6 เดือนล่าสุด
                        </div>
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="energyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- คอลัมน์ขวา: ฟอร์ม -->
                <div class="col-lg-5">
                    
                    <!-- ฟอร์ม 1: บันทึกการใช้พลังงาน (โค้ดเต็ม) -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-edit card-header-icon"></i>
                            บันทึกการใช้พลังงานปัจจุบัน
                        </div>
                        <div class="card-body">
                            <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="log_energy">
                                <div class="mb-3">
                                    <label for="kwh_usage" class="form-label">พลังงานไฟฟ้าที่ใช้ (kWh) / เดือน</label>
                                    <input type="number" step="0.01" class="form-control" id="kwh_usage" name="kwh_usage" required>
                                </div>
                                <div class="mb-3">
                                    <label for="water_usage" class="form-label">การใช้น้ำ (ลูกบาศก์เมตร) / เดือน</label>
                                    <input type="number" step="0.01" class="form-control" id="water_usage" name="water_usage">
                                </div>
                                <div class="mb-3">
                                    <label for="log_date" class="form-label">สำหรับรอบบิล/วันที่บันทึก</label>
                                    <input type="date" class="form-control" id="log_date" name="log_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="bill_proof_file" class="form-label">แนบหลักฐาน (บิล/มิเตอร์)</label>
                                    <input class="form-control" type="file" id="bill_proof_file" name="bill_proof_file" accept=".jpg, .jpeg, .png, .pdf" required>
                                    <div class="form-text">ไฟล์ JPG, PNG, PDF ขนาดไม่เกิน 2MB</div>
                                </div>
                                <button type="submit" class="btn btn-success w-100"><i class="fas fa-save me-2"></i>บันทึกข้อมูล</button>
                            </form>
                        </div>
                    </div>

                    <!-- ฟอร์ม 2: ตั้งเป้าหมาย (โค้ดเต็ม) -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-bullseye card-header-icon"></i>
                            ตั้งเป้าหมายการประหยัด (3.5.4.6 ข้อ 4)
                        </div>
                        <div class="card-body">
                            <form method="POST" action="dashboard.php">
                                <input type="hidden" name="action" value="set_goal">
                                <div class="mb-3">
                                    <label for="goal_month" class="form-label">เลือกเดือนเป้าหมาย</h6lass=>
                                    <input type="month" class="form-control" id="goal_month" name="goal_month" value="<?php echo htmlspecialchars($view_month_year); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="kwh_goal" class="form-label">เป้าหมายการใช้ไฟฟ้า (kWh)</label>
                                    <input type="number" step="1" class="form-control" id="kwh_goal" name="kwh_goal" value="<?php echo $current_goal_value > 0 ? $current_goal_value : ''; ?>" placeholder="เช่น 300" required>
                                </div>
                                <button type="submit" class="btn btn-warning w-100"><i class="fas fa-flag-checkered me-2"></i>ตั้งเป้าหมาย</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- Footer (โค้ดเต็ม) -->
    <footer class="footer mt-auto py-3 bg-dark text-white-50">
        <div class="container text-center">
            <small>© 2025 Green Digital Tracker: Developed by SARABURI TECHNICAL COLLEGE</small>
        </div>
    </footer>

    <!-- (JS Libraries คงเดิม) -->
    <script src="assets/bootstrap/bootstrap-5.3.8/dist/js/bootstrap.bundle.js"></script>
    <script src="assets/Chart/chart.js"></script>
    
    <!-- (JS หลัก โค้ดเต็ม) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // (JS สำหรับ Auto-Fading Alert)
            try {
                const autoFadeAlerts = document.querySelectorAll('.alert-dismissible');
                autoFadeAlerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    setTimeout(function() {
                        bsAlert.close();
                    }, 5000); 
                });
            } catch (e) {
                console.warn("Could not auto-hide alerts:", e);
            }

            // (JS สำหรับตั้งค่า default date)
            try {
                const today = new Date().toISOString().split('T')[0];
                const dateInput = document.getElementById('log_date');
                if (!dateInput.value) { 
                    dateInput.value = today;
                }
            } catch(e) {
                console.warn("Could not set default date for log_date:", e);
            }


            // (ส่วน Chart.js โค้ดเต็ม)
            const ctx = document.getElementById('energyChart').getContext('2d');
            const chartLabels = <?php echo json_encode($chart_labels); ?>;
            const chartUsageData = <?php echo json_encode($chart_usage_data); ?>;
            const chartGoalData = <?php echo json_encode($chart_goal_data); ?>;

            new Chart(ctx, {
                type: 'bar', 
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'การใช้ไฟฟ้า (kWh)',
                            data: chartUsageData,
                            backgroundColor: 'rgba(25, 135, 84, 0.8)', 
                            borderColor: 'rgba(25, 135, 84, 1)',
                            borderWidth: 1,
                            order: 2 
                        },
                        {
                            label: 'เป้าหมาย (kWh)',
                            data: chartGoalData,
                            type: 'line', 
                            borderColor: 'rgba(255, 193, 7, 1)', 
                            backgroundColor: 'rgba(255, 193, 7, 0.2)',
                            fill: false,
                            tension: 0.1,
                            spanGaps: true, 
                            order: 1 
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'ปริมาณ (kWh)'
                            }
                        }
                    }
                }
            });

        });
    </script>
</body>
</html>