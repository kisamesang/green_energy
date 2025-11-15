<?php
// -----------------------------------------------------------------------------
// หน้า Rewards (หน้าใหม่)
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
$user_full_name = $_SESSION['u_full_name'] ?? $_SESSION['u_username'];

// --- (ใหม่) 3. ดึงข้อมูล "รางวัล" และ "วันที่ปัจจุบัน" ---
$user_rewards = [];
$current_date_obj = new DateTime(); // วันที่ปัจจุบัน
$current_date_str = $current_date_obj->format('Y-m-d');

$sql_rewards = "SELECT * FROM user_rewards WHERE u_id = $user_id ORDER BY ur_claimed_at DESC";
$rewards_result = $conn->query($sql_rewards);
while ($row = $rewards_result->fetch_assoc()) {
    $user_rewards[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รางวัลของฉัน - Green Digital Tracker</title>
    <link href="assets/bootstrap/bootstrap-5.3.8/dist/css/bootstrap.css" rel="stylesheet">
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
        
        /* CSS สำหรับการ์ดรางวัล */
        .reward-card {
            border-left: 5px solid #0dcaf0; /* สีฟ้า */
        }
        .reward-code {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            color: #d63384; /* สีชมพู */
            background-color: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            border: 1px dashed #ced4da;
        }

        /* (ใหม่) CSS สำหรับคูปองหมดอายุ */
        .reward-card-expired {
            border-left: 5px solid #6c757d; /* สีเทา */
            background-color: #f8f9fa;
            opacity: 0.7;
        }
        .reward-card-expired .card-title {
            color: #6c757d !important;
        }
        .reward-card-expired .reward-code {
            color: #6c757d;
            background-color: #e9ecef;
            text-decoration: line-through;
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
                            <a class="nav-link" href="campaigns.php">แคมเปญ</a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link active" href="rewards.php">รางวัล</a>
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

    <!-- Main Content: Rewards -->
    <main class="main-content">
        <div class="container">
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="h4 mb-4"><i class="fas fa-medal me-2 text-info"></i>รางวัลของฉัน</h2>
            
                    <?php if (empty($user_rewards)): ?>
                        <div class="alert alert-light text-center">ยังไม่มีรางวัล</div>
                    <?php else: ?>
                        <?php foreach($user_rewards as $reward): ?>
                        
                        <?php
                            // (ใหม่) ตรวจสอบวันหมดอายุ
                            $is_expired = false;
                            $expiry_text = "ไม่มีวันหมดอายุ";
                            if (!empty($reward['ur_expires_at'])) {
                                $expiry_date_obj = new DateTime($reward['ur_expires_at']);
                                if ($expiry_date_obj->format('Y-m-d') < $current_date_str) {
                                    $is_expired = true;
                                }
                                $expiry_text = "หมดอายุ: " . $expiry_date_obj->format('d/m/Y');
                            }
                        ?>

                        <!-- (อัปเดต) เพิ่ม class ถ้าหมดอายุ -->
                        <div class="card shadow-sm reward-card mb-3 <?php echo $is_expired ? 'reward-card-expired' : ''; ?>">
                            <div class="card-body">
                                
                                <?php if ($is_expired): ?>
                                    <span class="badge bg-danger float-end">หมดอายุ</span>
                                <?php endif; ?>

                                <h5 class="card-title text-info">คูปอง <?php echo number_format($reward['ur_value']); ?> บาท</h5>
                                <p class="card-subtitle mb-2 text-muted">จาก: <?php echo htmlspecialchars($reward['ur_partner_name']); ?></p>
                                <hr>
                                <p class="text-center">
                                    รหัสคูปองของคุณ:
                                    <span class="reward-code d-block mt-1"><?php echo htmlspecialchars($reward['ur_code']); ?></span>
                                </p>
                                <small class="text-muted d-block text-end">
                                    ได้รับเมื่อ: <?php echo date('d/m/Y', strtotime($reward['ur_claimed_at'])); ?> | 
                                    <strong><?php echo $expiry_text; ?></strong>
                                </small>
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
        <script src="assets/bootstrap/bootstrap-5.3.8/dist/js/bootstrap.bundle.js"></script>
    
</body>
</html>