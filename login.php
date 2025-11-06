<?php
// -----------------------------------------------------------------------------
// หน้า Login (Traditional Style)
// -----------------------------------------------------------------------------
require_once 'db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบว่า Login หรือยัง? (ถ้าใช่ ให้เด้งไป Dashboard)
if (isset($_SESSION['u_id'])) {
    header('Location: dashboard.php'); // (เราจะสร้างไฟล์นี้ต่อไป)
    exit;
}

$error_message = '';

// 2. ตรวจสอบการส่งข้อมูล (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    try {
        // (แบบง่าย) ดึงข้อมูลจากฟอร์ม
        $username_or_email = $_POST['username_or_email'] ?? ''; // (รับค่าจากฟอร์ม)
        $password = $_POST['password'] ?? '';

        // 3. ตรวจสอบความถูกต้อง (Validate)
        if (empty($username_or_email) || empty($password)) {
            throw new Exception("กรุณากรอก Username/Email และ รหัสผ่าน");
        }
        
        // 4. (แบบง่าย) ค้นหาผู้ใช้จาก Username หรือ Email
        // === START EDIT: แก้ไข SQL Query ให้รองรับ Email ===
        $sql_check = "SELECT * FROM users WHERE u_username = '$username_or_email' OR u_email = '$username_or_email'";
        // === END EDIT ===
        
        $result_check = $conn->query($sql_check);

        if ($result_check->num_rows === 1) {
            // 5. พบผู้ใช้ -> ตรวจสอบรหัสผ่าน (3.5.4.1 ข้อ 3)
            $user = $result_check->fetch_assoc();
            
            if (password_verify($password, $user['u_password'])) {
                // 6. รหัสผ่านถูกต้อง -> ตรวจสอบสถานะ (3.5.4.3 ข้อ 1)
                if ($user['u_status'] === 'active') {
                    // 7. Login สำเร็จ (3.5.4.1 ข้อ 1 & 2)
                    $_SESSION['u_id'] = $user['u_id'];
                    $_SESSION['u_username'] = $user['u_username'];
                    $_SESSION['u_role'] = $user['u_role']; // (เก็บ Role)
                    
                    // 8. เด้งไปหน้า Dashboard (3.5.4.1 ข้อ 4 - Role-based access)
                    if ($user['u_role'] === 'admin') {
                        header('Location: manage_users.php'); // (Admin ไปหน้าจัดการผู้ใช้)
                    } else {
                        header('Location: dashboard.php'); // (User ไปหน้า Dashboard)
                    }
                    exit;
                } else {
                    // (กรณีบัญชีถูกระงับ)
                    throw new Exception("บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ");
                }
            } else {
                // (รหัสผ่านไม่ถูกต้อง)
                throw new Exception("Username หรือ Password ไม่ถูกต้อง");
            }
        } else {
            // (ไม่พบผู้ใช้)
            throw new Exception("Username หรือ Password ไม่ถูกต้อง");
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
    
    $conn->close(); // ปิดการเชื่อมต่อ
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Green Digital Tracker</title>
    <!-- CSS (Bootstrap, Font Awesome, Google Font) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* === START EDIT: 1. ปรับ Style ให้รองรับ Navbar/Footer === */
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #f8f9fa; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar-brand { font-weight: 700; }
        .main-content { 
            flex-grow: 1; 
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .login-container { max-width: 450px; width: 100%; }
        /* === END EDIT: 1 === */
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- === START EDIT: 2. เพิ่ม Navbar (สำหรับ Guest) === -->
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
                        <a class="nav-link" href="index.php">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="login.php">เข้าสู่ระบบ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">สมัครสมาชิก</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- === END EDIT: 2 === -->
    
    <!-- === START EDIT: 3. ปรับ Main Content (ย้าย .login-container) === -->
    <main class="main-content">
        <div class="login-container">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <i class="fas fa-leaf fa-3x text-success"></i>
                        <h1 class="h3 mb-3 fw-normal">เข้าสู่ระบบ</h1>
                    </div>

                    <!-- 9. แสดงข้อความ Error (ถ้ามี) -->
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- 10. ฟอร์ม Login (3.5.4.1 ข้อ 1) -->
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <!-- === START EDIT: 4. อัปเดต Label/Name === -->
                            <label for="username_or_email" class="form-label">ชื่อผู้ใช้งาน (Username) หรือ อีเมล</label>
                            <input type="text" class="form-control" id="username_or_email" name="username_or_email" required>
                            <!-- === END EDIT: 4 === -->
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button class="btn btn-success w-100 py-2 mt-3" type="submit"><i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ</button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="forgot_password.php" class="text-decoration-none">ลืมรหัสผ่าน?</a>
                    </div>
                    <hr>
                    <div class="text-center">
                        <a href="register.php" class="btn btn-outline-secondary w-100">ยังไม่มีบัญชี? สมัครสมาชิก</a>
                    </div>

                </div>
            </div>
        </div>
    </main>
    <!-- === END EDIT: 3 === -->

    <!-- === START EDIT: 5. เพิ่ม Footer === -->
    <footer class="footer mt-auto py-3 bg-dark text-white-50">
        <div class="container text-center">
            <!-- *** START EDIT: 6. เปลี่ยนข้อความ Footer ตามที่ขอ *** -->
            <small>© 2025 Green Digital Tracker: Developed by SARABURI TECHNICAL COLLEGE</small>
            <!-- *** END EDIT: 6 *** -->
        </div>
    </footer>
    <!-- === END EDIT: 5 === -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

