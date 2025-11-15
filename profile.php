<?php

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

$error_message = '';
$success_message = '';

// 3. ตรวจสอบการส่งข้อมูล (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $action = $_POST['action'] ?? '';

    try {
        // --- ACTION 1: อัปเดตโปรไฟล์ (3.5.4.2 ข้อ 3) ---
        if ($action === 'update_profile') {
            $full_name = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';

            if (empty($full_name) || empty($email)) {
                throw new Exception("ชื่อ-นามสกุล และ อีเมล ห้ามว่าง");
            }
            
            // (แบบง่าย) ตรวจสอบอีเมลซ้ำ (ที่ไม่ใช่ของตัวเอง)
            $sql_check_email = "SELECT u_id FROM users WHERE u_email = '$email' AND u_id != $user_id";
            $result_check = $conn->query($sql_check_email);
            if ($result_check->num_rows > 0) {
                throw new Exception('อีเมลนี้ถูกใช้งานโดยบัญชีอื่นแล้ว');
            }

            // (แบบง่าย) อัปเดตข้อมูล
            $sql_update_profile = "UPDATE users SET u_full_name = '$full_name', u_email = '$email' WHERE u_id = $user_id";
            if ($conn->query($sql_update_profile) === TRUE) {
                $success_message = 'อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว';
                // อัปเดตชื่อใน Session (ถ้ามี)
                $_SESSION['u_username'] = $user_username; // (Username ไม่ได้เปลี่ยน)
            } else {
                throw new Exception("ไม่สามารถอัปเดตข้อมูลได้: " . $conn->error);
            }
        
        // --- ACTION 2: เปลี่ยนรหัสผ่าน (3.5.4.2 ข้อ 4) ---
        } elseif ($action === 'change_password') {
            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("กรุณากรอกข้อมูลรหัสผ่านให้ครบถ้วน");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("รหัสผ่านใหม่และการยืนยันไม่ตรงกัน");
            }
            if (strlen($new_password) < 6) {
                throw new Exception("รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร");
            }

            // (แบบง่าย) ดึงรหัสผ่านเก่ามาตรวจสอบ
            $sql_get_pass = "SELECT u_password FROM users WHERE u_id = $user_id";
            $result_pass = $conn->query($sql_get_pass);
            $user_row = $result_pass->fetch_assoc();
            $hashed_password = $user_row['u_password'];

            // ตรวจสอบรหัสผ่านเก่า
            if (password_verify($old_password, $hashed_password)) {
                // ถ้าถูกต้อง -> เข้ารหัสรหัสผ่านใหม่ (3.5.4.1 ข้อ 3)
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // (แบบง่าย) อัปเดตรหัสผ่านใหม่
                $sql_update_pass = "UPDATE users SET u_password = '$new_hashed_password' WHERE u_id = $user_id";
                if ($conn->query($sql_update_pass) === TRUE) {
                    $success_message = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
                } else {
                    throw new Exception("ไม่สามารถเปลี่ยนรหัสผ่านได้: " . $conn->error);
                }
            } else {
                // ถ้าไม่ถูกต้อง
                throw new Exception("รหัสผ่านเดิมไม่ถูกต้อง!");
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
} // สิ้นสุดการตรวจสอบ POST

// 4. ดึงข้อมูล (GET) มาแสดงผล (สำหรับฟอร์ม)
// (แบบง่าย) ดึงข้อมูลปัจจุบันของผู้ใช้
try {
    $sql_get_data = "SELECT u_full_name, u_email, u_username FROM users WHERE u_id = $user_id";
    $result_data = $conn->query($sql_get_data);
    $user_data = $result_data->fetch_assoc();
    $user_full_name = $user_data['u_full_name'] ?? $user_username; // อัปเดตชื่อ (เผื่อมีการเปลี่ยนแปลง)
} catch (Exception $e) {
    die("Error fetching user data: " . $e->getMessage());
}

$conn->close(); // ปิดการเชื่อมต่อ
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน - Green Digital Tracker</title>
    <!-- Bootstrap CSS -->
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
                            <a class="nav-link active" href="profile.php">โปรไฟล์</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tips.php">เคล็ดลับ</a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- เมนู Logout (แสดงชื่อ) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($user_full_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            <li><a class="dropdown-item active" href="profile.php">แก้ไขโปรไฟล์</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content: Profile -->
    <main class="main-content">
        <div class="container">
            
            <h2 class="h4 mb-4">โปรไฟล์และการตั้งค่า</h2>

            <!-- แสดงข้อความ Error/Success (ถ้ามี) -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                
                <!-- 1. ฟอร์มแก้ไขโปรไฟล์ (3.5.4.2 ข้อ 3) -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-user-edit me-2"></i>
                            แก้ไขข้อมูลส่วนตัว
                        </div>
                        <div class="card-body">
                            <form method="POST" action="profile.php">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label class="form-label">ชื่อผู้ใช้งาน (Username)</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['u_username']); ?>" disabled readonly>
                                    <div class="form-text">ชื่อผู้ใช้งานไม่สามารถเปลี่ยนแปลงได้</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['u_full_name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">อีเมล</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['u_email']); ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>บันทึกข้อมูลส่วนตัว</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 2. ฟอร์มเปลี่ยนรหัสผ่าน (3.5.4.2 ข้อ 4) -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-key me-2"></i>
                            เปลี่ยนรหัสผ่าน
                        </div>
                        <div class="card-body">
                            <form method="POST" action="profile.php">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="old_password" class="form-label">รหัสผ่านเดิม</label>
                                    <input type="password" class="form-control" id="old_password" name="old_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">รหัสผ่านใหม่ (อย่างน้อย 6 ตัวอักษร)</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning w-100"><i class="fas fa-lock me-2"></i>อัปเดตรหัสผ่าน</button>
                            </form>
                        </div>
                    </div>
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
  <script src="assets/bootstrap/bootstrap-5.3.8/dist/js/bootstrap.bundle.js"></script>">
    
</body>
</html>
