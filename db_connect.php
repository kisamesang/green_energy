<?php
// PHP Script สำหรับเชื่อมต่อฐานข้อมูล (Traditional Style)
// -----------------------------------------------------------------------------

// *** START EDIT: เริ่ม Session ที่นี่ ***
// ต้องเรียก session_start() ก่อนที่จะมี HTML output ใดๆ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// *** END EDIT ***

// เปิดการแสดงข้อผิดพลาด (สำหรับ Debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "green_energy_db"; // ชื่อฐานข้อมูลที่ถูกต้อง

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// (เราจะไม่ปิด $conn->close() ที่นี่ เพื่อให้ไฟล์อื่นเรียกใช้ได้)
?>

