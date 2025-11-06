<?php
// -----------------------------------------------------------------------------
// Admin Header (Template)
// (ไฟล์นี้อยู่ใน /admin/ แล้ว)
// -----------------------------------------------------------------------------

// --- (แก้ไข) ต้องถอยกลับ 1 ระดับเพื่อหา db_connect.php ---
require_once '../db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['u_role']) || $_SESSION['u_role'] !== 'admin') {
    // --- (แก้ไข) ต้องถอยกลับ 1 ระดับเพื่อไป index.php ---
    header('Location: ../index.php');
    exit;
}

// 2. ดึงข้อมูล Admin จาก Session (เผื่อใช้)
$admin_id = $_SESSION['u_id'];
$admin_username = $_SESSION['u_username'];
$admin_full_name = $_SESSION['u_full_name'] ?? $admin_username;

// 3. (ตัวแปรสำหรับ Active Menu)
if (!isset($current_page)) {
    $current_page = ''; // ค่าเริ่มต้น
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?> - Green Digital Tracker</title>
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
        
        /* (CSS สำหรับ Admin) */
        .admin-navbar {
            background-color: #343a40; /* สีเทาเข้มสำหรับ Admin */
        }
        .admin-navbar .nav-link.active {
            font-weight: 700;
        }
        .verification-queue .badge {
            font-size: 0.9rem;
        }
        .verification-queue .bill-link {
            font-size: 0.9rem;
        }
        .stat-card {
            border-left: 5px solid #0d6efd;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar (Admin) -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar shadow-sm sticky-top">
        <div class="container">
            <!-- (ลิงก์ภายใน /admin/ ไม่ต้องแก้) -->
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-shield-halved me-2"></i>Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- (ลิงก์ภายใน /admin/ ไม่ต้องแก้) -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'users') ? 'active' : ''; ?>" href="admin_manage_users.php">
                            <i class="fas fa-users me-1"></i>จัดการผู้ใช้
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'content') ? 'active' : ''; ?>" href="admin_manage_content.php">
                            <i class="fas fa-file-alt me-1"></i>จัดการเนื้อหา
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'campaigns') ? 'active' : ''; ?>" href="admin_manage_campaigns.php">
                            <i class="fas fa-gift me-1"></i>จัดการแคมเปญ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'reports') ? 'active' : ''; ?>" href="admin_reports.php">
                            <i class="fas fa-chart-bar me-1"></i>รายงาน
                        </a>
                    </li>
                </ul>
                <!-- เมนู Admin (ขวา) -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <!-- (แก้ไข) ต้องถอยกลับ 1 ระดับเพื่อไป dashboard.php ของ User -->
                        <a class="nav-link" href="../dashboard.php" target="_blank">
                            <i class="fas fa-globe me-1"></i>(ดูหน้าเว็บ)
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-shield me-1"></i> <?php echo htmlspecialchars($admin_full_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminUserDropdown">
                            <!-- (แก้ไข) ต้องถอยกลับ 1 ระดับ -->
                            <li><a class="dropdown-item" href="../profile.php">แก้ไขโปรไฟล์</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>