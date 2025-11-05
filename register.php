<?php
// -----------------------------------------------------------------------------
// หน้า Register (สมัครสมาชิก) (Traditional Style)
// -----------------------------------------------------------------------------
require_once 'db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบว่า Login หรือยัง? (ถ้าใช่ ให้เด้งไป Dashboard)
if (isset($_SESSION['u_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// 2. ตรวจสอบการส่งข้อมูล (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // (แบบง่าย) ดึงข้อมูลจากฟอร์ม
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // === START EDIT 1: รับค่าคำถามจาก Dropdown ===
    $security_question = $_POST['security_question'] ?? ''; // (รับมาจาก Dropdown)
    $security_answer = $_POST['security_answer'] ?? '';
    // === END EDIT 1 ===

    try {
        // 3. ตรวจสอบความถูกต้อง (Validate) (3.5.4.2 ข้อ 2)
        // === START EDIT 2: เพิ่มการตรวจสอบว่าเลือกคำถามแล้ว ===
        if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($security_question) || empty($security_answer)) {
            throw new Exception("กรุณากรอกข้อมูลทั้งหมดให้ครบถ้วน");
        }
        if ($security_question === "-- กรุณาเลือกคำถาม --") {
             throw new Exception("กรุณาเลือกคำถามรักษาความปลอดภัย");
        }
        // === END EDIT 2 ===
        if ($password !== $confirm_password) {
            throw new Exception("รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน");
        }
        if (strlen($password) < 6) {
            throw new Exception("รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร");
        }

        // 4. (แบบง่าย) ตรวจสอบ Username หรือ Email ซ้ำ
        $sql_check = "SELECT u_id FROM users WHERE u_username = '$username' OR u_email = '$email'";
        $result_check = $conn->query($sql_check);
        if ($result_check->num_rows > 0) {
            throw new Exception("Username หรือ Email นี้ถูกใช้งานแล้ว");
        }

        // 5. เข้ารหัสรหัสผ่าน (3.5.4.1 ข้อ 3) และ คำตอบ
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_answer = password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT); // (เก็บคำตอบเป็นตัวพิมพ์เล็กและตัดช่องว่าง)

        // 6. (แบบง่าย) บันทึกข้อมูลลงฐานข้อมูล (3.5.4.2 ข้อ 1)
        // (SQL Query ไม่ต้องแก้ไข)
        $sql_insert = "INSERT INTO users (u_full_name, u_email, u_username, u_password, u_security_question, u_security_answer, u_role, u_status) 
                       VALUES ('$full_name', '$email', '$username', '$hashed_password', '$security_question', '$hashed_answer', 'user', 'active')";
        
        if ($conn->query($sql_insert) === TRUE) {
            $success_message = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
            // (ถ้าสำเร็จ อาจจะ redirect ไปหน้า login)
             header('Refresh: 2; URL=login.php');
        } else {
            throw new Exception("ไม่สามารถสมัครสมาชิกได้: " . $conn->error);
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
    <title>สมัครสมาชิก - Green Digital Tracker</title>
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
        .register-container { max-width: 550px; width: 100%; }
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
                        <a class="nav-link" href="login.php">เข้าสู่ระบบ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="register.php">สมัครสมาชิก</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- === END EDIT: 2 === -->

    <!-- === START EDIT: 3. ปรับ Main Content (ย้าย .register-container) === -->
    <main class="main-content">
        <div class="register-container">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-success"></i>
                        <h1 class="h3 mb-3 fw-normal">สมัครสมาชิกใหม่</h1>
                    </div>

                    <!-- 7. แสดงข้อความ Error/Success (ถ้ามี) -->
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

                    <form method="POST" action="register.php">
                        <!-- ส่วนข้อมูลส่วนตัว -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <hr class="my-3">
                        
                        <!-- ส่วนข้อมูล Login -->
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้งาน (Username)</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <hr class="my-3">

                        <!-- 8. ส่วนสำหรับกู้คืนรหัสผ่าน (3.5.4.2 ข้อ 5) -->
                        <h5 class="h6 text-muted">ส่วนสำหรับกู้คืนรหัสผ่าน</h5>
                        <div class="mb-3">
                            <label for="security_question" class="form-label">ตั้งคำถามรักษาความปลอดภัยของคุณ</label>
                            <select class="form-select" id="security_question" name="security_question" required>
                                <option>-- กรุณาเลือกคำถาม --</option>
                                <option>สัตว์เลี้ยงตัวแรกของคุณชื่ออะไร?</option>
                                <option>นามสกุลเดิมของมารดาคุณคืออะไร?</option>
                                <option>คุณเกิดที่เมืองอะไร?</option>
                                <option>โรงเรียนประถมของคุณชื่ออะไร?</option>
                                <option>อาหารจานโปรดของคุณคืออะไร?</option>
                                <option>ฮีโร่วัยเด็กของคุณคือใคร?</option>
                            </select>
                            <div class="form-text">กรุณาเลือกคำถามที่คุณจำคำตอบได้แม่นยำ</div>
                        </div>

                        <div class="mb-3">
                            <label for="security_answer" class="form-label">คำตอบของคุณ</label>
                            <input type="text" class="form-control" id="security_answer" name="security_answer" placeholder="เช่น: ส้ม" required>
                            <div class="form-text">คำตอบนี้จะไม่สนใจตัวพิมพ์เล็ก/ใหญ่ (aA เหมือนกัน)</div>
                        </div>
                        
                        <button class="btn btn-success w-100 py-2 mt-3" type="submit"><i class="fas fa-user-plus me-2"></i>สร้างบัญชี</button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="login.php" class="text-decoration-none">มีบัญชีอยู่แล้ว? กลับไปเข้าสู่ระบบ</a>
                    </div>

                </div>
            </div>
        </div>
    </main>
    <!-- === END EDIT: 3 === -->

    <!-- === START EDIT: 4. เพิ่ม Footer === -->
    <footer class="footer mt-auto py-3 bg-dark text-white-50">
        <div class="container text-center">
            <small>© 2025 Green Digital Tracker: Developed by SARABURI TECHNICAL COLLEGE</small>
        </div>
    </footer>
    <!-- === END EDIT: 4 === -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

