<?php
// -----------------------------------------------------------------------------
// หน้า Tips (เคล็ดลับ) (Traditional Style) (3.5.4.4 ข้อ 6)
// -----------------------------------------------------------------------------
require_once 'db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบสิทธิ์ (Security)
// ถ้ายังไม่ Login ให้เด้งกลับไปหน้า login.php
if (!isset($_SESSION['u_id'])) {
    header('Location: login.php');
    exit;
}

// 2. ดึงข้อมูลผู้ใช้จาก Session
$user_id = $_SESSION['u_id'];
$user_role = $_SESSION['u_role'];
$user_username = $_SESSION['u_username'];

// 3. ดึงชื่อเต็มของผู้ใช้ (สำหรับแสดงผล)
// (แบบง่าย)
try {
    $sql_user = "SELECT u_full_name FROM users WHERE u_id = $user_id";
    $result_user = $conn->query($sql_user);
    $user_info = $result_user->fetch_assoc();
    $user_full_name = $user_info['u_full_name'] ?? $user_username;
} catch (Exception $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// 4. ดึงข้อมูลเคล็ดลับ (Tips) ที่ใช้งานได้
// (3.5.4.3 ข้อ 2 - ดึงข้อมูลตามวันเวลาที่ตั้งค่าไว้)
$tips_list = [];
try {
    $current_date = date('Y-m-d H:i:s'); // วันที่ปัจจุบัน
    
    // (แบบง่าย)
    // --- START EDIT: 3. แก้ไข SQL Query ให้รองรับ t_display_until IS NULL ---
    $sql_tips = "SELECT t_title, t_content, t_category 
                 FROM tips 
                 WHERE t_is_active = 1 
                   AND (t_display_until > '$current_date' OR t_display_until IS NULL)
                 ORDER BY t_id DESC"; // (เรียงตาม t_id)
    // --- END EDIT: 3 ---
                 
    $result_tips = $conn->query($sql_tips);
    if ($result_tips === false) {
        // ถ้า Query ล้มเหลว
        throw new Exception("Error fetching tips: " . $conn->error);
    }
    
    while ($row = $result_tips->fetch_assoc()) {
        $tips_list[] = $row;
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$conn->close(); // ปิดการเชื่อมต่อ
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เคล็ดลับประหยัดพลังงาน - Green Digital Tracker</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <!-- Google Font "Prompt" -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- START EDIT: 4. ลบ CSS ที่ซับซ้อนของ Accordion ออก --- */
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #f8f9fa; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar-brand { font-weight: 700; }
        .main-content { flex-grow: 1; padding: 2rem 0; }
        
        /* (ลบ .accordion-button และ ::after ที่ไม่จำเป็นออก) */
        /* --- END EDIT: 4 --- */
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar (สำหรับผู้ใช้ที่ Login แล้ว) -->
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
                            <a class="nav-link" href="profile.php">โปรไฟล์</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="tips.php">เคล็ดลับ</a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- เมนู Logout (แสดงชื่อ) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($user_full_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            <li><a class="dropdown-item" href="profile.php">แก้ไขโปรไฟล์</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content: Tips -->
    <main class="main-content">
        <div class="container">
            
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    
                    <h2 class="h4 mb-4"><i class="fas fa-lightbulb me-2 text-warning"></i>เคล็ดลับการประหยัดพลังงาน</h2>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <?php if (empty($tips_list) && !isset($error_message)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            ยังไม่มีเคล็ดลับใหม่ในขณะนี้
                        </div>
                        
                    <!-- --- START EDIT: 5. เปลี่ยน UI จาก Accordion เป็น Cards --- -->
                    <?php elseif (!empty($tips_list)): ?>
                        <!-- วนลูปแสดงผลเคล็ดลับ (UI แบบ Card ที่อ่านง่าย) -->
                        <?php foreach ($tips_list as $index => $tip): ?>
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        <?php echo htmlspecialchars($tip['t_title']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($tip['t_content'])); ?></p>
                                </div>
                                <div class="card-footer bg-light text-muted">
                                    <span class="badge bg-secondary">
                                        หมวดหมู่: <?php echo htmlspecialchars($tip['t_category']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- --- END EDIT: 5 --- -->
                    
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

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>

