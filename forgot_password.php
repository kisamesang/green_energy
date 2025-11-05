<?php
// -----------------------------------------------------------------------------
// หน้า Forgot Password (กู้คืนรหัสผ่าน) (Traditional Style)
// (3.5.4.2 ข้อ 5 - ระบบช่วยเหลือ)
// (*** คืนค่าโค้ด 3 ขั้นตอน ***)
// -----------------------------------------------------------------------------
require_once 'db_connect.php'; // $conn และ session_start()

// 1. ตรวจสอบว่า Login หรือยัง? (ถ้าใช่ ให้เด้งไป Dashboard)
if (isset($_SESSION['u_id'])) {
    header('Location: dashboard.php');
    exit;
}

// === START BUG FIX: เคลียร์ Session เก่าเมื่อเข้าหน้าใหม่ (GET Request) ===
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // ถ้าเป็นการโหลดหน้าใหม่ (ไม่ใช่การกดปุ่ม Submit ใน Step 1, 2, 3)
    // ให้ล้าง Session การกู้คืนเก่าทิ้งทั้งหมด เพื่อบังคับให้เริ่ม Step 1
    unset($_SESSION['forgot_user_id']);
    unset($_SESSION['forgot_step_3_verified']);
}
// === END BUG FIX ===

$error_message = '';
$success_message = '';
$step = 1; // 1 = หา Username, 2 = ตอบคำถาม, 3 = ตั้งรหัสใหม่

try {
    // ตรวจสอบว่ามีการ POST ข้อมูลหรือไม่
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        $action = $_POST['action'] ?? '';

        // --- STEP 1: ค้นหา Username ---
        if ($action === 'find_user') {
            $username_or_email = $_POST['username_or_email'] ?? ''; // --- EDIT 1: เปลี่ยนชื่อตัวแปร
            if (empty($username_or_email)) {
                throw new Exception("กรุณากรอก Username หรือ อีเมล");
            }

            // (แบบง่าย) ค้นหาคำถามจาก Username หรือ Email
            // --- EDIT 2: อัปเดต SQL Query ให้ค้นหาได้ 2 ช่องทาง ---
            $sql_find = "SELECT u_id FROM users WHERE u_username = '$username_or_email' OR u_email = '$username_or_email'";
            $result = $conn->query($sql_find);

            if ($result->num_rows > 0) {
                $user_row = $result->fetch_assoc();
                // เก็บ u_id ไว้ใน Session เพื่อใช้ในขั้นตอนต่อไป
                $_SESSION['forgot_user_id'] = $user_row['u_id'];
                $step = 2; // ไปขั้นตอนที่ 2
            } else {
                throw new Exception("ไม่พบ Username หรือ อีเมล นี้ในระบบ");
            }
            // --- END EDIT 2 ---
        
        // --- STEP 2: ตรวจสอบคำตอบ ---
        } elseif ($action === 'check_answer') {
            $selected_question = $_POST['security_question'] ?? '';
            $typed_answer = $_POST['security_answer'] ?? '';
            $user_id = $_SESSION['forgot_user_id'] ?? null; // ดึง ID ผู้ใช้จาก Session

            if (empty($selected_question) || $selected_question === "-- กรุณาเลือกคำถาม --" || empty($typed_answer) || empty($user_id)) {
                throw new Exception("เกิดข้อผิดพลาด: กรุณาเลือกคำถามและกรอกคำตอบ");
            }

            // (แบบง่าย) ดึงคำถามและคำตอบที่แฮชไว้ (Hashed Answer)
            $sql_check = "SELECT u_security_question, u_security_answer FROM users WHERE u_id = $user_id";
            $result = $conn->query($sql_check);
            $user_row = $result->fetch_assoc();
            
            $saved_question = $user_row['u_security_question']; // คำถามจริงจาก DB
            $hashed_answer = $user_row['u_security_answer']; // คำตอบจริงจาก DB

            // 1. ตรวจสอบ "คำถาม" ที่เลือก
            // *** START BUG FIX (แก้ไข Error "คำถามไม่ตรง"): ใช้ trim() เพื่อตัดช่องว่างที่มองไม่เห็น ***
            if (trim($selected_question) !== trim($saved_question)) {
            // *** END BUG FIX ***
                $step = 2; // ยังอยู่ที่ขั้นตอนที่ 2
                throw new Exception("คำถามที่เลือกไม่ตรงกับที่ท่านตั้งค่าไว้ในระบบ");
            }
            
            // 2. ตรวจสอบ "คำตอบ" (ไม่สนใจตัวพิมพ์เล็ก/ใหญ่)
            if (password_verify(strtolower(trim($typed_answer)), $hashed_answer)) {
                // ถ้าคำตอบถูกต้อง
                $_SESSION['forgot_step_3_verified'] = true; // อนุญาตให้ตั้งรหัสใหม่
                $step = 3; // ไปขั้นตอนที่ 3
            } else {
                // ถ้าคำตอบผิด
                $step = 2; // ยังอยู่ที่ขั้นตอนที่ 2
                throw new Exception("คำตอบไม่ถูกต้อง!");
            }

        // --- STEP 3: ตั้งรหัสผ่านใหม่ ---
        } elseif ($action === 'reset_password') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $user_id = $_SESSION['forgot_user_id'] ?? null;
            $is_verified = $_SESSION['forgot_step_3_verified'] ?? false; // ต้องผ่าน Step 2 มาก่อน

            if (!$user_id || !$is_verified) {
                throw new Exception("คุณไม่ได้รับอนุญาตให้ตั้งรหัสผ่านใหม่ (Session หมดอายุ?)");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("รหัสผ่านใหม่และการยืนยันไม่ตรงกัน");
            }
            if (strlen($new_password) < 6) {
                throw new Exception("รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร");
            }

            // เข้ารหัสรหัสผ่านใหม่ (3.5.4.1 ข้อ 3)
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // (แบบง่าย) อัปเดตรหัสผ่านใหม่
            $sql_update = "UPDATE users SET u_password = '$new_hashed_password' WHERE u_id = $user_id";
            if ($conn->query($sql_update) === TRUE) {
                $success_message = "ตั้งรหัสผ่านใหม่เรียบ ร้อยแล้ว! คุณสามารถใช้รหัสใหม่นี้เข้าสู่ระบบได้ทันที";
                // (เคลียร์ Session ของการกู้คืน)
                unset($_SESSION['forgot_user_id']);
                unset($_SESSION['forgot_step_3_verified']);
                $step = 4; // (ขั้นตอนเสร็จสิ้น)
            } else {
                throw new Exception("ไม่สามารถอัปเดตรหัสผ่านได้: " . $conn->error);
            }
        }

    } // ปิด 'if (POST)'

    // (จัดการการแสดงผลของ Step 2 และ 3 ถ้ามีการ Refresh หน้า)
    if (isset($_SESSION['forgot_user_id']) && $step == 1) {
        $step = 2; // ถ้ามี Session ค้างอยู่ ให้ไป Step 2
    }
    if (isset($_SESSION['forgot_step_3_verified']) && $step != 4) {
        $step = 3; // ถ้าผ่าน Step 2 แล้ว ให้ไป Step 3
    }
    
} catch (Exception $e) { 
    $error_message = $e->getMessage();
}

$conn->close(); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กู้คืนรหัสผ่าน - Green Digital Tracker</title>
    <!-- CSS (Bootstrap, Font Awesome, Google Font) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- === START EDIT 1: ปรับ Style ให้รองรับ Navbar/Footer === -->
    <style>
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
        .forgot-container { max-width: 500px; width: 100%; }
    </style>
    <!-- === END EDIT 1 === -->
</head>
<!-- === START EDIT 2: ปรับ Body Class === -->
<body class="d-flex flex-column min-vh-100">
<!-- === END EDIT 2 === -->

    <!-- === START EDIT: 3. เพิ่ม Navbar (สำหรับ Guest) === -->
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
                        <a class="nav-link" href="register.php">สมัครสมาชิก</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- === END EDIT: 3 === -->

    <!-- === START EDIT: 4. ปรับ Main Content (ย้าย .forgot-container) === -->
    <main class="main-content">
        <div class="forgot-container">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x text-warning"></i>
                        <h1 class="h3 mb-3 fw-normal">กู้คืนรหัสผ่าน</h1>
                    </div>

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
                            <div class="mt-2 text-center">
                                <a href="login.php" class="btn btn-success btn-sm">กลับไปหน้า Login</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- ----------------------------------------------------------------- -->
                    <!-- STEP 1: ค้นหา Username -->
                    <!-- ----------------------------------------------------------------- -->
                    <?php if ($step === 1 && empty($success_message)): ?>
                        <h5 class="text-center mb-3 text-success fw-bold">ขั้นตอนที่ 1: ค้นหาบัญชี</h5>
                        <p class="text-muted text-center">กรุณากรอก Username หรือ อีเมล ของคุณเพื่อค้นหาบัญชี</p>
                        <form method="POST" action="forgot_password.php">
                            <input type="hidden" name="action" value="find_user">
                            <div class="mb-3">
                                <label for="username_or_email" class="form-label">ชื่อผู้ใช้งาน (Username) หรือ อีเมล</label>
                                <input type="text" class="form-control" id="username_or_email" name="username_or_email" required>
                            </div>
                            <button class="btn btn-primary w-100 py-2 mt-2" type="submit"><i class="fas fa-search me-2"></i>ค้นหา</button>
                        </form>
                    
                    <!-- ----------------------------------------------------------------- -->
                    <!-- STEP 2: ตอบคำถาม -->
                    <!-- ----------------------------------------------------------------- -->
                    <?php elseif ($step === 2 && empty($success_message)): ?>
                        <h5 class="text-center mb-3 text-success fw-bold">ขั้นตอนที่ 2: ตอบคำถามรักษาความปลอดภัย</h5>
                        <p class="text-muted">กรุณาเลือกคำถามและตอบคำถามรักษาความปลอดภัยของคุณ:</p>
                        <form method="POST" action="forgot_password.php">
                            <input type="hidden" name="action" value="check_answer">
                            
                            <div class="mb-3">
                                <label for="security_question" class="form-label fw-bold">เลือกคำถามของคุณ:</label>
                                <select class="form-select" id="security_question" name="security_question" required>
                                    <option>-- กรุณาเลือกคำถาม --</option>
                                    <option>สัตว์เลี้ยงตัวแรกของคุณชื่ออะไร?</option>
                                    <option>นามสกุลเดิมของมารดาคุณคืออะไร?</option>
                                    <option>คุณเกิดที่เมืองอะไร?</option>
                                    <option>โรงเรียนประถมของคุณชื่ออะไร?</option>
                                    <option>อาหารจานโปรดของคุณคืออะไร?</option>
                                    <option>ฮีโร่วัยเด็กของคุณคือใคร?</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="security_answer" class="form-label">คำตอบของคุณ:</label>
                                <input type="text" class="form-control" id="security_answer" name="security_answer" required>
                                <div class="form-text">คำตอบไม่สนใจตัวพิมพ์เล็ก/ใหญ่ (aA เหมือนกัน)</div>
                            </div>
                            <button class="btn btn-primary w-100 py-2 mt-2" type="submit"><i class="fas fa-check me-2"></i>ยืนยันคำตอบ</button>
                        </form>

                    <!-- ----------------------------------------------------------------- -->
                    <!-- STEP 3: ตั้งรหัสผ่านใหม่ -->
                    <!-- ----------------------------------------------------------------- -->
                    <?php elseif ($step === 3 && empty($success_message)): ?>
                        <h5 class="text-center mb-3 text-success fw-bold">ขั้นตอนที่ 3: ตั้งรหัสผ่านใหม่</h5>
                        <p class="text-muted text-center">คำตอบถูกต้อง! กรุณาตั้งรหัสผ่านใหม่</p>
                        <form method="POST" action="forgot_password.php">
                            <input type="hidden" name="action" value="reset_password">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">รหัสผ่านใหม่ (อย่างน้อย 6 ตัวอักษร)</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button class="btn btn-warning w-100 py-2 mt-2" type="submit"><i class="fas fa-save me-2"></i>บันทึกรหัสผ่านใหม่</button>
                        </form>
                    <?php endif; ?>


                    <div class="text-center mt-4">
                        <a href="login.php" class="text-decoration-none">กลับสู่หน้าเข้าสู่ระบบ</a>
                    </div>

                </div>
            </div>
        </div>
    </main>
    <!-- === END EDIT: 4 === -->
    
    <!-- === START EDIT: 5. เพิ่ม Footer === -->
    <footer class="footer mt-auto py-3 bg-dark text-white-50">
        <div class="container text-center">
            <small>© 2025 Green Digital Tracker: Developed by SARABURI TECHNICAL COLLEGE</small>
        </div>
    </footer>
    <!-- === END EDIT: 5 === -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

