<?php
    // -----------------------------------------------------------------------------
    // หน้า Logout (Traditional Style) (3.5.4.1 ข้อ 1)
    // -----------------------------------------------------------------------------

    // 1. เริ่ม Session (เพื่อที่จะทำลายมัน)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 2. ล้างค่า Session ทั้งหมด
    $_SESSION = array();

    // 3. ทำลาย Session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    // 4. เด้งกลับไปหน้าแรก (index.php)
    header('Location: index.php');
    exit; // จบการทำงานทันที

