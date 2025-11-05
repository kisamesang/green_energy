<?php
// -----------------------------------------------------------------------------
// หน้า Campaigns (หน้าใหม่)
// -----------------------------------------------------------------------------
require_once 'db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['u_id'])) {
    header('Location: login.php');
    exit;
}

// 2. ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['u_id'];
$user_role = $_SESSION['u_role'];
$user_full_name = $_SESSION['u_full_name'] ?? $_SESSION['u_username']; // (ดึงจาก Session ที่ตั้งไว้ตอน Login)

$error_message = '';
$success_message = '';

// 3. (ย้ายมา) ประมวลผลการ "รับข้อเสนอ" (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'accept_campaign') {
            $c_id = $_POST['c_id'] ?? 0;
            $reduction_target = (float)($_POST['c_reduction_target'] ?? 0);

            if (empty($c_id) || empty($reduction_target)) {
                throw new Exception("ข้อมูลแคมเปญไม่ถูกต้อง");
            }

            // 1. ตรวจสอบว่าเคยรับแคมเปญนี้หรือยัง
            $sql_check_uc = "SELECT uc_id FROM user_campaigns WHERE u_id = $user_id AND c_id = $c_id";
            $result_check_uc = $conn->query($sql_check_uc);
            if ($result_check_uc->num_rows > 0) {
                throw new Exception("คุณได้เข้าร่วมแคมเปญนี้แล้ว");
            }

            // 2. ค้นหา "ค่าไฟอ้างอิง" (Baseline)
            $sql_find_baseline = "SELECT el_kwh_usage FROM energy_log 
                                  WHERE u_id = $user_id AND el_verification_status = 'verified' 
                                  ORDER BY el_date DESC LIMIT 1";
            $baseline_result = $conn->query($sql_find_baseline);
            if ($baseline_result->num_rows === 0) {
                throw new Exception("คุณต้องมีข้อมูลการใช้ไฟที่ตรวจสอบแล้วอย่างน้อย 1 เดือนก่อนรับแคมเปญ");
            }
            
            $baseline_kwh = (float)$baseline_result->fetch_assoc()['el_kwh_usage'];
            
            // 3. คำนวณ "เป้าหมาย" (Target)
            $target_kwh = $baseline_kwh * (1.0 - $reduction_target);

            // 4. บันทึกการเข้าร่วม
            $sql_accept = "INSERT INTO user_campaigns (u_id, c_id, uc_baseline_kwh, uc_target_kwh, uc_status) 
                           VALUES ($user_id, $c_id, $baseline_kwh, $target_kwh, 'accepted')";
            
            if ($conn->query($sql_accept) === TRUE) {
                $success_message = "รับแคมเปญสำเร็จ! เป้าหมายของคุณคือใช้ไฟไม่เกิน " . number_format($target_kwh, 2) . " kWh";
            } else {
                throw new Exception("ไม่สามารถรับแคมเปญได้: " . $conn->error);
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
} // สิ้นสุด POST

// 4. (ย้ายมา) ดึงข้อมูลแคมเปญ (GET)
$available_campaigns = [];
$active_user_campaigns = [];

// 4.1 ดึงแคมเปญที่ "มีให้เลือก" (คือแคมเปญที่ยังไม่ได้รับ)
$sql_campaigns = "SELECT * FROM campaigns c
                  WHERE c.c_is_active = 1 
                  AND c.c_id NOT IN (
                      SELECT uc.c_id FROM user_campaigns uc WHERE uc.u_id = $user_id
                  )";
$campaigns_result = $conn->query($sql_campaigns);
while ($row = $campaigns_result->fetch_assoc()) {
    $available_campaigns[] = $row;
}

// 4.2 ดึงแคมเปญที่ "รับไปแล้ว" (Active)
$sql_active_campaigns = "SELECT * FROM user_campaigns uc
                         JOIN campaigns c ON uc.c_id = c.c_id
                         WHERE uc.u_id = $user_id AND uc.uc_status = 'accepted'";
$active_campaigns_result = $conn->query($sql_active_campaigns);
while ($row = $active_campaigns_result->fetch_assoc()) {
    $active_user_campaigns[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แคมเปญ - Green Digital Tracker</title>
    <!-- CSS (Bootstrap, Font Awesome, Google Font) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
        .main-content { flex-grow: 1; padding: 2rem 0; }
        
        /* CSS สำหรับการ์ดแคมเปญ */
        .campaign-card {
            border: 2px dashed #198754;
            background-color: #f0fff8;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar (โค้ดเต็ม) -->
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
                            <a class="nav-link" href="dashboard.php">Dashboard (Admin)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">จัดการผู้ใช้</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_content.php">จัดการเนื้อหา</a>
                        </li>
                    <?php else: ?>
                        <!-- เมนู User -->
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="campaigns.php">แคมเปญ</a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link" href="rewards.php">รางวัล</a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- เมนู Logout (แสดงชื่อ) -->
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

    <!-- Main Content: Campaigns -->
    <main class="main-content">
        <div class="container">

            <h2 class="h4 mb-4">แคมเปญและข้อเสนอ</h2>

            <!-- (ส่วนแสดงข้อความ Error/Success) -->
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

            <div class="row g-4">
                <!-- คอลัมน์ซ้าย: แคมเปญที่กำลังทำ -->
                <div class="col-lg-6">
                    <h4 class="h5 mb-3"><i class="fas fa-tasks me-2 text-primary"></i>แคมเปญที่กำลังดำเนินอยู่</h4>
                    <?php if (empty($active_user_campaigns)): ?>
                        <div class="alert alert-light text-center">คุณยังไม่มีแคมเปญที่กำลังทำอยู่</div>
                    <?php else: ?>
                        <?php foreach($active_user_campaigns as $active_campaign): ?>
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-star me-2"></i>
                                <?php echo htmlspecialchars($active_campaign['c_title']); ?>
                            </div>
                            <div class="card-body">
                                <p><?php echo htmlspecialchars($active_campaign['c_description']); ?></p>
                                <hr>
                                <p class="mb-0">
                                    <i class="fas fa-bullseye me-1"></i> 
                                    เป้าหมายของคุณ: <strong><?php echo number_format($active_campaign['uc_target_kwh'], 2); ?> kWh</strong>
                                </p>
                                <p class="text-muted small">
                                    (คำนวณจากค่าไฟอ้างอิง: <?php echo number_format($active_campaign['uc_baseline_kwh'], 2); ?> kWh)
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- คอลัมน์ขวา: แคมเปญใหม่ -->
                <div class="col-lg-6">
                    <h4 class="h5 mb-3"><i class="fas fa-gift me-2 text-success"></i>แคมเปญใหม่สำหรับคุณ!</h4>
                     <?php if (empty($available_campaigns)): ?>
                        <div class="alert alert-light text-center">ไม่มีแคมเปญใหม่ในขณะนี้</div>
                    <?php else: ?>
                        <?php foreach($available_campaigns as $campaign): ?>
                        <div class="card shadow-sm campaign-card mb-3">
                            <div class="card-body">
                                <h5 class="card-title text-success"><?php echo htmlspecialchars($campaign['c_title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($campaign['c_description']); ?></p>
                                <ul class="list-group list-group-flush mb-3">
                                    <li class="list-group-item bg-transparent">
                                        <i class="fas fa-percentage me-2"></i>
                                        เป้าหมาย: ลดการใช้ไฟ <?php echo (float)$campaign['c_reduction_target'] * 100; ?>%
                                    </li>
                                    <li class="list-group-item bg-transparent">
                                        <i class="fas fa-trophy me-2"></i>
                                        รางวัล: ส่วนลด <?php echo number_format($campaign['c_reward_value']); ?> บาท
                                    </li>
                                </ul>
                                <form method="POST" action="campaigns.php" onsubmit="return confirm('คุณต้องมีบิลค่าไฟที่ตรวจสอบแล้วอย่างน้อย 1 เดือนเพื่อใช้เป็นฐานอ้างอิง คุณต้องการรับข้อเสนอนี้หรือไม่?');">
                                    <input type="hidden" name="action" value="accept_campaign">
                                    <input type="hidden" name="c_id" value="<?php echo $campaign['c_id']; ?>">
                                    <input type="hidden" name="c_reduction_target" value="<?php echo $campaign['c_reduction_target']; ?>">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check me-2"></i>รับข้อเสนอนี้
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-dark text-white-50">
        <div class="container text-center">
            <small>© 2025 Green Digital Tracker: Developed by SARABURI TECHNICAL COLLEGE</small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JS สำหรับ Auto-Fading Alert
        try {
            const autoFadeAlerts = document.querySelectorAll('.alert-dismissible');
            autoFadeAlerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                setTimeout(function() {
                    bsAlert.close();
                }, 5000); 
            });
        } catch (e) {}
    </script>
</body>
</html>