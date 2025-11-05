-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 06:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `green_energy_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `c_id` int(11) NOT NULL COMMENT 'ID อัตโนมัติ (Primary Key) ของแคมเปญ',
  `c_title` varchar(255) NOT NULL COMMENT 'ชื่อแคมเปญ เช่น "ลด 20% รับเลย!"',
  `c_description` text NOT NULL COMMENT 'คำอธิบายรายละเอียดของแคมเปญ',
  `c_partner_name` varchar(100) NOT NULL COMMENT 'ชื่อ Partner เจ้าของส่วนลด เช่น "Samsung"',
  `c_reward_value` int(11) NOT NULL COMMENT 'มูลค่ารางวัล/ส่วนลด เช่น 500 (บาท)',
  `c_reduction_target` decimal(5,2) NOT NULL COMMENT 'เป้าหมายการลด (เก็บเป็นทศนิยม) เช่น 0.20 (สำหรับ 20%)',
  `c_is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะแคมเปญ (TRUE=เปิดใช้งาน, FALSE=ปิดใช้งาน)',
  `c_expires_at` date DEFAULT NULL COMMENT 'วันที่แคมเปญหมดเขต (เที่ยงคืน)',
  `c_created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้างแคมเปญ (อัปเดตอัตโนมัติ)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`c_id`, `c_title`, `c_description`, `c_partner_name`, `c_reward_value`, `c_reduction_target`, `c_is_active`, `c_expires_at`, `c_created_at`) VALUES
(1, 'ลด 20% รับเลย!', 'ลดการใช้ไฟฟ้าลง 20% เทียบกับเดือนล่าสุด รับส่วนลด 500 บาท จาก Partner ของเรา', 'Samsung', 500, 0.20, 1, NULL, '2025-11-05 16:37:29'),
(2, 'Carrier Cool Challenge', 'ประหยัดไฟ 15% (เทียบเดือนก่อน) รับส่วนลดล้างแอร์ 300 บาท', 'Carrier', 300, 0.15, 1, NULL, '2025-11-05 16:37:29'),
(3, 'Green Hero 10%', 'ลดการใช้ไฟ 10% 2 เดือนติดต่อกัน รับส่วนลดร้านกาแฟ 100 บาท', 'Amazon Cafe', 100, 0.10, 1, NULL, '2025-11-05 16:37:29'),
(4, 'The 25% Elite', 'ท้าทาย! ลดการใช้ไฟ 25% รับคูปอง 1,000 บาท!', 'Power Buy', 1000, 0.25, 1, NULL, '2025-11-05 16:37:29'),
(5, 'Summer Sale (หมดเขต)', 'แคมเปญเก่า ลด 10% รับ 100 บาท (หมดเขตแล้ว)', 'Big C', 100, 0.10, 0, NULL, '2025-11-05 16:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `energy_log`
--

CREATE TABLE `energy_log` (
  `el_id` int(11) NOT NULL COMMENT 'รหัสบันทึก',
  `u_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้งาน (Foreign Key อ้างอิง users.u_id)',
  `el_date` date NOT NULL COMMENT 'วันที่สิ้นสุดรอบการบันทึก (YYYY-MM-DD)',
  `el_kwh_usage` decimal(10,2) NOT NULL COMMENT 'ปริมาณการใช้ไฟฟ้า (kWh)',
  `el_water_usage` decimal(10,2) NOT NULL COMMENT 'ปริมาณการใช้น้ำ (ลบ.ม.)',
  `el_period_start` date NOT NULL COMMENT 'วันที่เริ่มต้นรอบการบันทึก',
  `el_calculated_cost` decimal(10,2) DEFAULT NULL COMMENT 'ค่าใช้จ่ายที่คำนวณได้ (บาท)',
  `el_bill_proof_file` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์หลักฐานบิล',
  `el_verification_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending' COMMENT 'สถานะ: pending, verified, rejected'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางบันทึกการใช้พลังงาน';

--
-- Dumping data for table `energy_log`
--

INSERT INTO `energy_log` (`el_id`, `u_id`, `el_date`, `el_kwh_usage`, `el_water_usage`, `el_period_start`, `el_calculated_cost`, `el_bill_proof_file`, `el_verification_status`) VALUES
(15, 12, '2025-10-30', 300.00, 20.00, '2025-10-01', 1200.00, NULL, 'pending'),
(16, 12, '2025-11-27', 100.00, 10.00, '2025-11-01', 400.00, NULL, 'pending'),
(17, 12, '2025-12-30', 10.00, 1.00, '2025-12-01', 40.00, NULL, 'pending'),
(18, 12, '2026-01-28', 100.00, 111.00, '2026-01-01', 400.00, NULL, 'pending'),
(19, 12, '2025-09-15', 10.00, 20.00, '2025-09-01', 40.00, NULL, 'pending'),
(20, 13, '2025-11-04', 500.00, 20.00, '2025-11-01', 2000.00, NULL, 'pending'),
(21, 13, '2025-12-31', 250.00, 56.00, '2025-12-01', 1000.00, NULL, 'pending'),
(22, 14, '2025-11-29', 10.00, 10.00, '2025-11-01', 40.00, '14_2025-11_690b6a40a8b43.jpg', 'verified'),
(23, 14, '2025-12-30', 50.00, 10.00, '2025-12-01', 200.00, '14_2025-12_690b6f1926116.png', 'verified'),
(24, 14, '2026-01-30', 30.00, 5.00, '2026-01-01', 120.00, '14_2026-01_690b6f7b3d7ff.jpg', 'verified'),
(25, 14, '2026-02-25', 250.00, 650.00, '2026-02-01', 1000.00, '14_2026-02_690b718ba6e76.png', 'verified'),
(26, 14, '2026-03-25', 10.00, 2.00, '2026-03-01', 40.00, '14_2026-03_690b7fce641ee.png', 'verified'),
(27, 15, '2025-10-30', 290.00, 10.00, '2025-10-01', 1160.00, '15_2025-10_690b8383b1448.png', 'verified'),
(28, 15, '2025-11-29', 200.00, 10.00, '2025-11-01', 800.00, '15_2025-11_690b84070e430.jpg', 'verified'),
(29, 15, '2025-12-31', 169.00, 10.00, '2025-12-01', 676.00, '15_2025-12_690b872cdde45.png', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `p_id` int(11) NOT NULL,
  `p_name` varchar(100) NOT NULL COMMENT 'ชื่อแบรนด์/พันธมิตร',
  `p_website_url` varchar(255) DEFAULT NULL COMMENT 'ลิงก์เว็บ (ถ้ามี)',
  `p_is_active` tinyint(1) NOT NULL DEFAULT 1,
  `p_logo` varchar(100) DEFAULT NULL COMMENT 'ชื่อไฟล์รูปภาพ (เช่น logo.png)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`p_id`, `p_name`, `p_website_url`, `p_is_active`, `p_logo`) VALUES
(1, 'Brand A (Eco Energy)', '#', 1, 'sumsung.png'),
(2, 'Brand B (Solar Inc.)', '#', 1, 'Carrier.png'),
(3, 'Gov Support', '#', 1, 'gov.png'),
(4, 'SARABURI TECHNICAL COLLEGE', '#', 1, 'sbtc.png');

-- --------------------------------------------------------

--
-- Table structure for table `saving_goals`
--

CREATE TABLE `saving_goals` (
  `sg_id` int(11) NOT NULL COMMENT 'รหัสเป้าหมาย',
  `u_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้งาน (Foreign Key อ้างอิง users.u_id)',
  `sg_type` enum('kwh','cost') NOT NULL COMMENT 'ประเภทเป้าหมาย (ไฟฟ้า/ค่าใช้จ่าย)',
  `sg_target_value` decimal(10,2) NOT NULL COMMENT 'มูลค่าเป้าหมายที่ต้องการประหยัด/ใช้',
  `sg_start_date` date NOT NULL COMMENT 'วันที่เริ่มต้นเป้าหมาย',
  `sg_end_date` date NOT NULL COMMENT 'วันที่สิ้นสุดเป้าหมาย',
  `sg_is_completed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'สถานะ: บรรลุเป้าหมาย (0=ไม่สำเร็จ, 1=สำเร็จ)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางเป้าหมายการประหยัดพลังงาน';

--
-- Dumping data for table `saving_goals`
--

INSERT INTO `saving_goals` (`sg_id`, `u_id`, `sg_type`, `sg_target_value`, `sg_start_date`, `sg_end_date`, `sg_is_completed`) VALUES
(21, 12, 'kwh', 50.00, '2025-12-01', '2025-12-31', 0),
(22, 12, 'kwh', 50.00, '2025-12-01', '2025-12-31', 0),
(23, 12, 'kwh', 300.00, '2025-10-01', '2025-10-31', 0),
(24, 12, 'kwh', 200.00, '2025-11-01', '2025-11-30', 0),
(25, 12, 'kwh', 200.00, '2025-11-01', '2025-11-30', 0),
(26, 12, 'kwh', 200.00, '2025-11-01', '2025-11-30', 0),
(27, 12, 'kwh', 100.00, '2025-12-01', '2025-12-31', 0),
(28, 12, 'kwh', 500.00, '2026-01-01', '2026-01-31', 0),
(29, 12, 'kwh', 1000.00, '2025-09-01', '2025-09-30', 0),
(30, 13, 'kwh', 500.00, '2025-11-01', '2025-11-30', 0),
(31, 13, 'kwh', 400.00, '2025-12-01', '2025-12-31', 0),
(32, 14, 'kwh', 300.00, '2025-11-01', '2025-11-30', 0),
(33, 14, 'kwh', 100.00, '2025-12-01', '2025-12-31', 0),
(34, 14, 'kwh', 50.00, '2026-01-01', '2026-01-31', 0),
(35, 14, 'kwh', 250.00, '2026-02-01', '2026-02-28', 0),
(36, 14, 'kwh', 100.00, '2026-03-01', '2026-03-31', 0),
(37, 14, 'kwh', 100.00, '2026-03-01', '2026-03-31', 0),
(38, 15, 'kwh', 300.00, '2025-10-01', '2025-10-31', 0),
(39, 15, 'kwh', 232.00, '2025-11-01', '2025-11-30', 0),
(40, 15, 'kwh', 170.00, '2025-12-01', '2025-12-31', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tips`
--

CREATE TABLE `tips` (
  `t_id` int(11) NOT NULL COMMENT 'รหัสคำแนะนำ',
  `t_title` varchar(200) NOT NULL COMMENT 'หัวข้อคำแนะนำ',
  `t_content` text NOT NULL COMMENT 'เนื้อหาคำแนะนำโดยละเอียด',
  `t_category` varchar(100) NOT NULL COMMENT 'หมวดหมู่ (เช่น แอร์, ไฟ, น้ำ)',
  `t_is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผล (0=ปิด, 1=เปิด)',
  `t_display_until` datetime DEFAULT NULL COMMENT 'แสดงผลถึงวันที่ (สำหรับตั้งเวลาปิด)',
  `u_id` int(11) DEFAULT NULL COMMENT 'ผู้สร้าง (Foreign Key อ้างอิง users.u_id)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางเคล็ดลับ/คำแนะนำประหยัดพลังงาน';

--
-- Dumping data for table `tips`
--

INSERT INTO `tips` (`t_id`, `t_title`, `t_content`, `t_category`, `t_is_active`, `t_display_until`, `u_id`) VALUES
(1, 'ประหยัดไฟ 101: แอร์', 'ตั้งอุณหภูมิเครื่องปรับอากาศที่ 26 องศาเซลเซียสเพื่อลดภาระเครื่องทำความเย็นและช่วยประหยัดไฟได้ถึง 10%', 'ไฟฟ้า', 1, NULL, NULL),
(2, 'ประหยัดไฟ 102: ปลั๊ก', 'ถอดปลั๊กเครื่องใช้ไฟฟ้าทุกครั้งหลังเลิกใช้งาน แม้ในโหมด Standby ก็ยังมีการกินไฟอยู่', 'ไฟฟ้า', 1, NULL, NULL),
(3, 'ประหยัดน้ำ: ก๊อกน้ำ', 'ตรวจสอบก๊อกน้ำและท่อน้ำภายในบ้านอย่างสม่ำเสมอ หากมีการรั่วซึมเล็กน้อยจะสามารถสูญเสียน้ำได้มากต่อเดือน', 'น้ำ', 1, NULL, NULL),
(4, 'Green Tip: โหมดประหยัด', 'ใช้โหมดประหยัดพลังงาน (Eco Mode) ในเครื่องซักผ้าและเครื่องล้างจานเสมอเพื่อลดการใช้พลังงานสูงสุด', 'ไฟฟ้า/น้ำ', 1, NULL, NULL),
(5, 'บำรุงรักษาตู้เย็น', 'ทำความสะอาดคอยล์ด้านหลังตู้เย็นทุก 6 เดือน จะช่วยให้ตู้เย็นทำงานมีประสิทธิภาพมากขึ้นและประหยัดไฟ', 'ไฟฟ้า', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `u_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้งาน',
  `u_username` varchar(100) NOT NULL COMMENT 'ชื่อผู้ใช้ (สำหรับ Login)',
  `u_password` varchar(255) NOT NULL COMMENT 'รหัสผ่าน (ต้องเข้ารหัส)',
  `u_security_question` varchar(255) NOT NULL,
  `u_security_answer` varchar(255) NOT NULL,
  `u_email` varchar(150) NOT NULL COMMENT 'อีเมล (สำหรับกู้คืนรหัสผ่าน)',
  `u_full_name` varchar(200) NOT NULL COMMENT 'ชื่อ-นามสกุล',
  `u_role` enum('admin','user') NOT NULL DEFAULT 'user' COMMENT 'สิทธิ์การใช้งาน (Admin/User)',
  `u_status` enum('active','suspended') NOT NULL DEFAULT 'active' COMMENT 'สถานะบัญชี',
  `u_created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้างบัญชี'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางข้อมูลสมาชิก';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `u_username`, `u_password`, `u_security_question`, `u_security_answer`, `u_email`, `u_full_name`, `u_role`, `u_status`, `u_created_at`) VALUES
(8, 'abc', '$2y$10$wbg9mepJM0eZQ0SB9EjQ4u6cUDUrcipgX9wI2oUbdYYX6gKcb9l5O', 'What city were you born in?', '$2y$10$yB54mA4T9Z8FzNCPrP7EZuQCa0cLgFNbxcptX58qUKTwhnAuqu9RC', 'abc@mail.com', 'สุรวต ทองจันทร์', 'user', 'active', '2025-11-02 10:37:02'),
(9, 'kit', '$2y$10$pW.sS9r.CUQY1FyguoeN0eayREGU8ABZ/uQBhC3JQhdXM.ZqvtIzi', 'ฮีโร่วัยเด็กของคุณคือใคร?', '$2y$10$L.wWvgBu9NkwuAeUfrXQqeO9lziZt49TKP7e7tyEhd40ItHbUCZoO', 'kit@mail.com', 'กิตติวัต', 'user', 'active', '2025-11-02 13:02:42'),
(10, 'jong', '$2y$10$3i/oq6lafonAfxYNAZLBN.0zJXQiY8WWsxnLYJWQWF3vZM5fJ16PG', 'สัตว์เลี้ยงตัวแรกของคุณชื่ออะไร?', '$2y$10$ulVoKzn0LijLHYqSrMda4efFa7il0Nj9BdNn9zFhJHsAxM6dCiPjm', 'jong@mail.com', 'จงจัด', 'user', 'active', '2025-11-02 13:16:36'),
(11, 'hunsa', '$2y$10$/Zv00LP8x8AHqxM4Xa7oiOew9mS1kyZbBRQ3kG8iLfs0lxlo2SB.W', 'คุณเกิดที่เมืองอะไร?', '$2y$10$xyM2HU449qIxlnMgjcf4TuNm7z3FqkEYITjUK.GyE1BSYY/xYigT2', 'hunsa@mail.com', 'หรรษา มานี้', 'user', 'active', '2025-11-02 15:42:59'),
(12, 'krukit', '$2y$10$08sjdmvBy9Gx54vr2sHRhOYe6W0YvHVORX7.h8pZUz2NA7Lk1baMi', 'สัตว์เลี้ยงตัวแรกของคุณชื่ออะไร?', '$2y$10$NXFphh.4fycflO5lera/nO9uAxw8dfk7IbMeE/fLZatPzm7TnEArq', 'kisame@gmail.com', 'กิตติชัย ทองดี', 'user', 'active', '2025-11-02 23:04:48'),
(13, 'janvit', '$2y$10$VecOXFR6aupGKMaruZjwgeVNuYHZAxNQ1fgTBBFzt4rxa5CBOUDhq', 'ฮีโร่วัยเด็กของคุณคือใคร?', '$2y$10$FMp8DV0TNpJGBksx0Uyp3OEfog4iALH9YIJ5plURk3BBAwsOiGani', 'kittichai.tong@sbt.ac.th', 'เจนวิทย์ ทั้งวัน', 'user', 'active', '2025-11-04 18:07:06'),
(14, 'pa', '$2y$10$Uj4qOjOiabvwTfVXw7br/ebsPAeZ4M7IMIZTEmnxytycrYM/76gDa', 'อาหารจานโปรดของคุณคืออะไร?', '$2y$10$08ZoJl4RES8/RGwboDLhTOT4cp41DVlHNUaXeenYy35yAkZI0haqK', 'pa@gmail.com', 'ประสอน', 'user', 'active', '2025-11-05 22:15:13'),
(15, 'admin', '$2y$10$JtyHBN4gnDoHC9SZ.r81AucTg9ihko7Zv1clS06kcLQFR5IQ64fPS', 'อาหารจานโปรดของคุณคืออะไร?', '$2y$10$IKZ7hN8kzS1n7U2x2yFyCuRhPnM.LPg3Ej3f/v4Rcifok8/VH8aiy', 'admin@gmail.com', 'สุพจ', 'user', 'active', '2025-11-06 00:03:09');

-- --------------------------------------------------------

--
-- Table structure for table `user_campaigns`
--

CREATE TABLE `user_campaigns` (
  `uc_id` int(11) NOT NULL COMMENT 'ID อัตโนมัติ (Primary Key) ของการเข้าร่วม',
  `u_id` int(11) NOT NULL COMMENT 'ID ของผู้ใช้ (Foreign Key จากตาราง users)',
  `c_id` int(11) NOT NULL COMMENT 'ID ของแคมเปญ (Foreign Key จากตาราง campaigns)',
  `uc_baseline_kwh` decimal(10,2) NOT NULL COMMENT 'ค่าไฟอ้างอิง (Baseline) ณ วันที่กดรับ (เช่น 500 kWh)',
  `uc_target_kwh` decimal(10,2) NOT NULL COMMENT 'ค่าไฟเป้าหมายที่ต้องทำให้ได้ (เช่น 400 kWh)',
  `uc_status` enum('accepted','completed','failed') NOT NULL DEFAULT 'accepted' COMMENT 'สถานะปัจจุบัน (รับแล้ว, ทำสำเร็จ, ไม่สำเร็จ)',
  `uc_accepted_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่ผู้ใช้กดยอมรับแคมเปญ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_campaigns`
--

INSERT INTO `user_campaigns` (`uc_id`, `u_id`, `c_id`, `uc_baseline_kwh`, `uc_target_kwh`, `uc_status`, `uc_accepted_at`) VALUES
(1, 8, 1, 500.00, 400.00, 'accepted', '2025-11-05 16:37:29'),
(2, 9, 2, 400.00, 340.00, 'completed', '2025-11-05 16:37:29'),
(3, 10, 3, 300.00, 270.00, 'completed', '2025-11-05 16:37:29'),
(4, 11, 4, 800.00, 600.00, 'failed', '2025-11-05 16:37:29'),
(5, 12, 1, 450.00, 360.00, 'completed', '2025-11-05 16:37:29'),
(6, 14, 1, 250.00, 200.00, 'accepted', '2025-11-05 16:39:55'),
(7, 14, 2, 250.00, 212.50, 'accepted', '2025-11-05 16:40:51'),
(8, 14, 3, 250.00, 225.00, 'accepted', '2025-11-05 16:41:07'),
(9, 14, 4, 10.00, 7.50, 'accepted', '2025-11-05 16:58:17'),
(10, 15, 1, 290.00, 232.00, 'completed', '2025-11-05 17:05:07'),
(11, 15, 2, 200.00, 170.00, 'completed', '2025-11-05 17:18:46');

-- --------------------------------------------------------

--
-- Table structure for table `user_rewards`
--

CREATE TABLE `user_rewards` (
  `ur_id` int(11) NOT NULL COMMENT 'ID อัตโนมัติ (Primary Key) ของรางวัล',
  `u_id` int(11) NOT NULL COMMENT 'ID ของผู้ใช้ (Foreign Key จากตาราง users) เจ้าของรางวัล',
  `c_id` int(11) NOT NULL COMMENT 'ID ของแคมเปญ (Foreign Key จากตาราง campaigns) ที่ทำสำเร็จ',
  `ur_code` varchar(50) NOT NULL COMMENT 'รหัสคูปอง (สร้างแบบสุ่ม) เช่น "COUPON-XYZ123"',
  `ur_value` int(11) NOT NULL COMMENT 'มูลค่ารางวัล (คัดลอกมาจาก campaigns) เช่น 500',
  `ur_partner_name` varchar(100) NOT NULL COMMENT 'ชื่อ Partner (คัดลอกมาจาก campaigns) เช่น "Samsung"',
  `ur_expires_at` date DEFAULT NULL COMMENT 'วันที่คูปองหมดอายุ',
  `ur_claimed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่ได้รับรางวัล (อัปเดตอัตโนมัติ)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_rewards`
--

INSERT INTO `user_rewards` (`ur_id`, `u_id`, `c_id`, `ur_code`, `ur_value`, `ur_partner_name`, `ur_expires_at`, `ur_claimed_at`) VALUES
(1, 9, 2, 'COUPON-CARRIER-D4E5F6', 300, 'Carrier', NULL, '2025-11-05 16:37:29'),
(2, 10, 3, 'COUPON-AMAZON-G7H8I9', 100, 'Amazon Cafe', NULL, '2025-11-05 16:37:29'),
(3, 12, 1, 'COUPON-SAMSUNG-M4N5P6', 500, 'Samsung', NULL, '2025-11-05 16:37:29'),
(4, 9, 1, 'COUPON-SAMSUNG-X1Y2Z3', 500, 'Samsung', NULL, '2025-11-05 16:37:29'),
(5, 10, 2, 'COUPON-CARRIER-A7B8C9', 300, 'Carrier', NULL, '2025-11-05 16:37:29'),
(6, 15, 1, 'COUPON-690B84070F029', 500, 'Samsung', NULL, '2025-11-05 17:06:15'),
(7, 15, 2, 'COUPON-690B872CDECE1', 300, 'Carrier', '2025-12-05', '2025-11-05 17:19:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexes for table `energy_log`
--
ALTER TABLE `energy_log`
  ADD PRIMARY KEY (`el_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`p_id`);

--
-- Indexes for table `saving_goals`
--
ALTER TABLE `saving_goals`
  ADD PRIMARY KEY (`sg_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `tips`
--
ALTER TABLE `tips`
  ADD PRIMARY KEY (`t_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`u_id`),
  ADD UNIQUE KEY `u_username` (`u_username`),
  ADD UNIQUE KEY `u_email` (`u_email`);

--
-- Indexes for table `user_campaigns`
--
ALTER TABLE `user_campaigns`
  ADD PRIMARY KEY (`uc_id`),
  ADD KEY `u_id` (`u_id`),
  ADD KEY `c_id` (`c_id`);

--
-- Indexes for table `user_rewards`
--
ALTER TABLE `user_rewards`
  ADD PRIMARY KEY (`ur_id`),
  ADD UNIQUE KEY `ur_code_unique` (`ur_code`) COMMENT 'ป้องกันรหัสคูปองซ้ำ',
  ADD KEY `u_id` (`u_id`),
  ADD KEY `c_id` (`c_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID อัตโนมัติ (Primary Key) ของแคมเปญ', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `energy_log`
--
ALTER TABLE `energy_log`
  MODIFY `el_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสบันทึก', AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `p_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `saving_goals`
--
ALTER TABLE `saving_goals`
  MODIFY `sg_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสเป้าหมาย', AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `tips`
--
ALTER TABLE `tips`
  MODIFY `t_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสคำแนะนำ', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสผู้ใช้งาน', AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_campaigns`
--
ALTER TABLE `user_campaigns`
  MODIFY `uc_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID อัตโนมัติ (Primary Key) ของการเข้าร่วม', AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_rewards`
--
ALTER TABLE `user_rewards`
  MODIFY `ur_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID อัตโนมัติ (Primary Key) ของรางวัล', AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `energy_log`
--
ALTER TABLE `energy_log`
  ADD CONSTRAINT `fk_user_log` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `saving_goals`
--
ALTER TABLE `saving_goals`
  ADD CONSTRAINT `fk_user_goal` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tips`
--
ALTER TABLE `tips`
  ADD CONSTRAINT `fk_tip_creator` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_campaigns`
--
ALTER TABLE `user_campaigns`
  ADD CONSTRAINT `user_campaigns_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`),
  ADD CONSTRAINT `user_campaigns_ibfk_2` FOREIGN KEY (`c_id`) REFERENCES `campaigns` (`c_id`);

--
-- Constraints for table `user_rewards`
--
ALTER TABLE `user_rewards`
  ADD CONSTRAINT `user_rewards_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`),
  ADD CONSTRAINT `user_rewards_ibfk_2` FOREIGN KEY (`c_id`) REFERENCES `campaigns` (`c_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
