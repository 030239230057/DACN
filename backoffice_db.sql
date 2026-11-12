-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 12, 2026 at 02:02 PM
-- Server version: 5.7.31
-- PHP Version: 7.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `backoffice_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_assessments`
--

DROP TABLE IF EXISTS `ai_assessments`;
CREATE TABLE IF NOT EXISTS `ai_assessments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `FK_profile_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FK_requested_by` bigint(20) UNSIGNED NOT NULL,
  `risk_level` enum('Thấp','Trung bình','Cao') COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `anomalies` json NOT NULL,
  `recommendation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_time_ms` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_assessments`
--

INSERT INTO `ai_assessments` (`id`, `FK_profile_id`, `FK_requested_by`, `risk_level`, `summary`, `anomalies`, `recommendation`, `response_time_ms`, `created_at`) VALUES
(1, 'HS-043', 1, 'Cao', 'Khách hàng có tỷ lệ nợ trên thu nhập (DTI) vượt quá ngưỡng an toàn. Khả năng thanh toán hàng tháng bị ảnh hưởng nghiêm trọng nếu có rủi ro tài chính.', '[\"DTI quá cao lên tới 54.1%\", \"Thu nhập không đủ bù đắp chi phí sinh hoạt sau khi trừ nợ.\"]', 'Nên TỪ CHỐI cấp tín dụng hoặc yêu cầu tăng tỷ lệ trả trước lên 50-60%.', 2289, '2026-11-12 07:10:25'),
(2, 'HS-047', 1, 'Thấp', 'Hồ sơ tín dụng đạt chuẩn xuất sắc. Khách hàng có nguồn thu nhập rất tốt, ổn định, và dư nợ hiện tại không đáng kể so với thu nhập.', '[\"Không phát hiện điểm bất thường nào đáng lo ngại.\"]', 'An toàn tuyệt đối để PHÊ DUYỆT giải ngân ngay lập tức theo chính sách tiêu chuẩn.', 1856, '2026-11-12 07:15:26'),
(3, 'HS-037', 1, 'Cao', 'Khách hàng có tỷ lệ nợ trên thu nhập (DTI) vượt quá ngưỡng an toàn. Khả năng thanh toán hàng tháng bị ảnh hưởng nghiêm trọng nếu có rủi ro tài chính phát sinh.', '[\"Tỷ lệ nợ (DTI) cực kỳ nguy hiểm, lên tới 89%\", \"Thu nhập không đủ để vừa trang trải phí sinh hoạt vừa trả nợ.\"]', 'Kiên quyết TỪ CHỐI cấp tín dụng. Có thể tư vấn khách hàng thanh toán bớt dư nợ cũ hoặc tăng tỷ lệ trả trước lên mức 50-60%.', 2165, '2026-11-12 07:16:52'),
(4, 'HS-050', 1, 'Trung bình', 'Hồ sơ tín dụng ở mức tạm chấp nhận. Khách hàng có khả năng trả nợ nhưng có một vài rủi ro tiềm ẩn về tuổi tác hoặc dư nợ cận biên.', '[\"DTI đang ở mức cảnh báo (38.4%)\", \"Độ tuổi hoặc thâm niên làm việc chưa đáp ứng hồ sơ Vàng.\"]', 'Có thể PHÊ DUYỆT nhưng yêu cầu nhân viên Sales gọi điện thẩm định chéo nơi làm việc, hoặc bổ sung người đồng trả nợ.', 2146, '2026-11-12 07:19:26'),
(5, 'HS-044', 1, 'Thấp', 'Hồ sơ tín dụng đạt chuẩn xuất sắc. Khách hàng có nguồn thu nhập rất tốt, ổn định, và dư nợ hiện tại không đáng kể so với thu nhập.', '[\"Không phát hiện điểm bất thường nào đáng lo ngại.\"]', 'An toàn tuyệt đối để PHÊ DUYỆT giải ngân ngay lập tức theo chính sách tiêu chuẩn.', 2214, '2026-11-12 07:24:07'),
(6, 'HS-043', 1, 'Cao', 'DTI 54.1% vượt ngưỡng an toàn, khả năng thanh toán hàng tháng bị ảnh hưởng nếu phát sinh rủi ro tài chính.', '[\"DTI = 54.1%, vượt ngưỡng an toàn 50%.\", \"Dư nợ cũ 15.000.000 đ chiếm 42.9% thu nhập ròng.\"]', 'Đề xuất TỪ CHỐI hoặc yêu cầu tăng tỷ lệ trả trước.', 2003, '2026-11-12 08:17:06'),
(7, 'HS-044', 1, 'Thấp', 'Hồ sơ đạt chuẩn: DTI 25.4%, độ tuổi 24, không có dấu hiệu bất thường.', '[]', 'Đề xuất PHÊ DUYỆT theo chính sách tiêu chuẩn.', 2008, '2026-11-12 08:17:47'),
(8, 'HS-043', 1, 'Cao', 'DTI 54.1% vượt ngưỡng an toàn, khả năng thanh toán hàng tháng bị ảnh hưởng nếu phát sinh rủi ro tài chính.', '[\"DTI = 54.1%, vượt ngưỡng an toàn 50%.\", \"Dư nợ cũ 15.000.000 đ chiếm 42.9% thu nhập ròng.\"]', 'Đề xuất TỪ CHỐI hoặc yêu cầu tăng tỷ lệ trả trước.', 2015, '2026-11-12 08:19:25'),
(9, 'HS-043', 1, 'Cao', 'DTI 54.1% vượt ngưỡng an toàn, khả năng thanh toán hàng tháng bị ảnh hưởng nếu phát sinh rủi ro tài chính.', '[\"DTI = 54.1%, vượt ngưỡng an toàn 50%.\", \"Dư nợ cũ 15.000.000 đ chiếm 42.9% thu nhập ròng.\"]', 'Đề xuất TỪ CHỐI hoặc yêu cầu tăng tỷ lệ trả trước.', 2010, '2026-11-12 11:33:51'),
(10, 'HS-043', 1, 'Cao', 'DTI 54.1% vượt ngưỡng an toàn, khả năng thanh toán hàng tháng bị ảnh hưởng nếu phát sinh rủi ro tài chính.', '[\"DTI = 54.1%, vượt ngưỡng an toàn 50%.\", \"Dư nợ cũ 15.000.000 đ chiếm 42.9% thu nhập ròng.\"]', 'Đề xuất TỪ CHỐI hoặc yêu cầu tăng tỷ lệ trả trước.', 2005, '2026-11-12 13:20:17');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `FK_profile_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FK_user_id` bigint(20) UNSIGNED NOT NULL,
  `action` enum('create','update','approve','reject','cancel') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `FK_profile_id`, `FK_user_id`, `action`, `reason`, `created_at`) VALUES
(1, 'HS-050', 4, 'create', NULL, '2026-11-08 01:00:00'),
(2, 'HS-049', 7, 'create', NULL, '2026-11-08 02:15:00'),
(3, 'HS-048', 2, 'create', NULL, '2026-11-08 03:30:00'),
(4, 'HS-047', 4, 'create', NULL, '2026-11-08 04:45:00'),
(5, 'HS-046', 7, 'create', NULL, '2026-11-07 01:20:00'),
(6, 'HS-045', 2, 'create', NULL, '2026-11-07 02:35:00'),
(7, 'HS-044', 7, 'create', NULL, '2026-11-07 03:50:00'),
(8, 'HS-043', 8, 'create', NULL, '2026-11-06 01:10:00'),
(9, 'HS-042', 8, 'create', NULL, '2026-11-06 02:25:00'),
(10, 'HS-041', 8, 'create', NULL, '2026-11-06 03:40:00'),
(11, 'HS-040', 8, 'create', NULL, '2026-11-05 01:30:00'),
(12, 'HS-039', 4, 'create', NULL, '2026-11-05 02:45:00'),
(13, 'HS-038', 7, 'create', NULL, '2026-11-05 04:00:00'),
(14, 'HS-037', 4, 'create', NULL, '2026-11-05 07:15:00'),
(15, 'HS-036', 2, 'create', NULL, '2026-11-04 01:50:00'),
(16, 'HS-035', 7, 'create', NULL, '2026-11-04 03:05:00'),
(17, 'HS-034', 7, 'create', NULL, '2026-11-04 04:20:00'),
(18, 'HS-033', 7, 'create', NULL, '2026-11-03 01:40:00'),
(19, 'HS-032', 7, 'create', NULL, '2026-11-03 02:55:00'),
(20, 'HS-031', 2, 'create', NULL, '2026-11-03 04:10:00'),
(21, 'HS-030', 2, 'create', NULL, '2026-11-02 01:15:00'),
(22, 'HS-029', 8, 'create', NULL, '2026-11-02 02:30:00'),
(23, 'HS-028', 7, 'create', NULL, '2026-11-01 01:45:00'),
(24, 'HS-027', 7, 'create', NULL, '2026-11-01 03:00:00'),
(25, 'HS-026', 4, 'create', NULL, '2026-10-30 01:25:00'),
(26, 'HS-025', 8, 'create', NULL, '2026-10-30 02:40:00'),
(27, 'HS-024', 8, 'create', NULL, '2026-10-28 01:05:00'),
(28, 'HS-023', 4, 'create', NULL, '2026-10-28 02:20:00'),
(29, 'HS-022', 2, 'create', NULL, '2026-10-25 01:35:00'),
(30, 'HS-021', 7, 'create', NULL, '2026-10-25 02:50:00'),
(31, 'HS-020', 2, 'create', NULL, '2026-10-22 01:15:00'),
(32, 'HS-019', 2, 'create', NULL, '2026-10-20 02:30:00'),
(33, 'HS-018', 8, 'create', NULL, '2026-10-18 01:45:00'),
(34, 'HS-017', 7, 'create', NULL, '2026-10-15 03:00:00'),
(35, 'HS-016', 8, 'create', NULL, '2026-10-15 04:15:00'),
(36, 'HS-015', 8, 'create', NULL, '2026-10-12 01:25:00'),
(37, 'HS-014', 8, 'create', NULL, '2026-10-10 02:40:00'),
(38, 'HS-013', 7, 'create', NULL, '2026-10-10 03:55:00'),
(39, 'HS-012', 4, 'create', NULL, '2026-10-08 01:35:00'),
(40, 'HS-011', 8, 'create', NULL, '2026-10-05 02:50:00'),
(41, 'HS-010', 2, 'create', NULL, '2026-10-05 04:05:00'),
(42, 'HS-009', 7, 'create', NULL, '2026-10-02 01:20:00'),
(43, 'HS-008', 4, 'create', NULL, '2026-10-02 02:35:00'),
(44, 'HS-007', 4, 'create', NULL, '2026-09-28 03:50:00'),
(45, 'HS-006', 8, 'create', NULL, '2026-09-25 01:40:00'),
(46, 'HS-005', 7, 'create', NULL, '2026-09-22 02:55:00'),
(47, 'HS-004', 4, 'create', NULL, '2026-09-18 04:10:00'),
(48, 'HS-003', 4, 'create', NULL, '2026-09-15 01:30:00'),
(49, 'HS-002', 7, 'create', NULL, '2026-09-10 02:45:00'),
(50, 'HS-001', 7, 'create', NULL, '2026-09-05 04:00:00'),
(51, 'HS-049', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-08 04:15:00'),
(52, 'HS-048', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-08 05:30:00'),
(53, 'HS-046', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-07 03:20:00'),
(54, 'HS-045', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-07 04:35:00'),
(55, 'HS-042', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-06 04:25:00'),
(56, 'HS-041', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-06 05:40:00'),
(57, 'HS-040', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-05 03:30:00'),
(58, 'HS-038', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-05 06:00:00'),
(59, 'HS-036', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-04 03:50:00'),
(60, 'HS-035', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-04 05:05:00'),
(61, 'HS-032', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-03 04:55:00'),
(62, 'HS-031', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-03 06:10:00'),
(63, 'HS-029', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-02 04:30:00'),
(64, 'HS-028', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-11-01 03:45:00'),
(65, 'HS-026', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-30 03:25:00'),
(66, 'HS-024', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-28 03:05:00'),
(67, 'HS-023', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-28 04:20:00'),
(68, 'HS-021', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-25 04:50:00'),
(69, 'HS-020', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-22 03:15:00'),
(70, 'HS-017', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-15 05:00:00'),
(71, 'HS-016', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-15 06:15:00'),
(72, 'HS-014', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-10 04:40:00'),
(73, 'HS-013', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-10 05:55:00'),
(74, 'HS-011', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-05 04:50:00'),
(75, 'HS-009', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-02 03:20:00'),
(76, 'HS-008', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-10-02 04:35:00'),
(77, 'HS-006', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-09-25 03:40:00'),
(78, 'HS-005', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-09-22 04:55:00'),
(79, 'HS-003', 5, 'approve', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-09-15 03:30:00'),
(80, 'HS-002', 5, 'reject', 'Quyết định dựa trên phân tích rủi ro và các chỉ số', '2026-09-10 04:45:00'),
(81, 'HS-047', 1, 'approve', 'Hồ sơ đủ điều kiện, DTI an toàn', '2026-11-12 07:15:43'),
(82, 'HS-050', 1, 'approve', 'Hồ sơ đủ điều kiện', '2026-11-12 07:20:04'),
(83, 'HS-044', 1, 'approve', 'Hồ sơ đủ điều kiện', '2026-11-12 08:18:24');

-- --------------------------------------------------------

--
-- Table structure for table `installment_profiles`
--

DROP TABLE IF EXISTS `installment_profiles`;
CREATE TABLE IF NOT EXISTS `installment_profiles` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FK_created_by` bigint(20) UNSIGNED NOT NULL,
  `customer_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cccd` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age` tinyint(3) UNSIGNED NOT NULL,
  `net_income` decimal(15,0) NOT NULL,
  `existing_debt` decimal(15,0) NOT NULL,
  `goods_value` decimal(15,0) NOT NULL,
  `down_payment` decimal(15,0) NOT NULL,
  `term_months` smallint(5) UNSIGNED NOT NULL,
  `occupation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tenure_months` smallint(5) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `loan_amount` decimal(15,0) NOT NULL,
  `down_payment_ratio` decimal(6,4) NOT NULL,
  `monthly_installment` decimal(15,0) NOT NULL,
  `dti` decimal(6,4) NOT NULL,
  `flag_age` tinyint(1) NOT NULL,
  `flag_down_payment` tinyint(1) NOT NULL,
  `flag_dti` tinyint(1) NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `status_text` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Chờ thẩm định',
  `interest_rate` decimal(5,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `installment_profiles`
--

INSERT INTO `installment_profiles` (`id`, `FK_created_by`, `customer_name`, `cccd`, `phone`, `age`, `net_income`, `existing_debt`, `goods_value`, `down_payment`, `term_months`, `occupation`, `company`, `tenure_months`, `product_name`, `loan_amount`, `down_payment_ratio`, `monthly_installment`, `dti`, `flag_age`, `flag_down_payment`, `flag_dti`, `status`, `status_text`, `interest_rate`, `created_at`) VALUES
('HS-050', 4, 'Nguyễn Thị Thanh', '079195001234', '0906251661', 28, '15000000', '3000000', '34000000', '8500000', 12, 'Nhân viên kinh doanh', 'Công ty Cổ phần A', 36, 'iPhone 15 Pro Max', '25500000', '25.0000', '2762500', '38.4000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-08 01:00:00'),
('HS-049', 7, 'Bạch Hồng Cường', '079188009988', '0912334556', 26, '20000000', '5000000', '30000000', '9000000', 12, 'Trưởng nhóm', 'Tập đoàn IT B', 24, 'Laptop Lenovo ThinkPad', '21000000', '30.0000', '2275000', '36.3000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-08 02:15:00'),
('HS-048', 2, 'Trần Ngọc Ánh', '001199002233', '0988776655', 22, '9000000', '6000000', '15000000', '3000000', 6, 'Thu ngân', 'Cửa hàng tiện lợi C', 3, 'Tủ lạnh Panasonic', '12000000', '20.0000', '2300000', '92.2000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-11-08 03:30:00'),
('HS-047', 4, 'Phạm Văn Đồng', '079192004455', '0977665544', 31, '18000000', '2000000', '45000000', '13500000', 18, 'Tài xế', 'Công ty Vận tải M', 18, 'Xe máy Honda SH Mode', '31500000', '30.0000', '2537500', '25.2000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-08 04:45:00'),
('HS-046', 7, 'Hoàng Thị Bích', '079198007766', '0933221144', 25, '14000000', '1000000', '20000000', '4000000', 12, 'Lễ tân', 'Công ty TNHH Dịch Vụ E', 12, 'Máy giặt LG Inverter', '16000000', '20.0000', '1733333', '19.5000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-07 01:20:00'),
('HS-045', 2, 'Bùi Thị Hà', '079200001212', '0933332211', 23, '8000000', '5000000', '18000000', '3600000', 9, 'Công nhân', 'Công ty May mặc', 6, 'Điện thoại Samsung A54', '14400000', '20.0000', '1960000', '87.0000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-11-07 02:35:00'),
('HS-044', 7, 'Trần Quỳnh Như', '079199008877', '0901223344', 24, '12000000', '0', '28000000', '5600000', 9, 'Giao dịch viên', 'Ngân hàng TMCP', 8, 'iPad Pro 11 inch', '22400000', '20.0000', '3048888', '25.4000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-07 03:50:00'),
('HS-043', 8, 'Lê Đức Anh', '001194002211', '0988112233', 29, '35000000', '15000000', '52000000', '15600000', 12, 'Lập trình viên', 'FPT Software', 48, 'MacBook Pro 14', '36400000', '30.0000', '3943333', '54.1000', 1, 1, 0, 'pending', 'Chờ thẩm định', '0.0250', '2026-11-06 01:10:00'),
('HS-042', 8, 'Vũ Minh Tuấn', '079194002345', '0901998877', 29, '30000000', '4000000', '39000000', '11700000', 9, 'Chuyên viên Phân tích', 'Shopee Việt Nam', 28, 'MacBook Air M3', '27300000', '30.0000', '3715833', '25.7000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-06 02:25:00'),
('HS-041', 8, 'Trương Ngọc Ánh', '079195003434', '0977774433', 28, '10000000', '7000000', '28000000', '5600000', 12, 'Bán hàng online', 'Kinh doanh tự do', 12, 'iPhone 14', '22400000', '20.0000', '2426666', '94.2000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-11-06 03:40:00'),
('HS-040', 8, 'Đỗ Tiến Đạt', '079182001234', '0912123123', 41, '45000000', '10000000', '89000000', '26700000', 18, 'Phó phòng', 'Ngân hàng Vietcombank', 120, 'Xe máy Vespa Sprint', '62300000', '30.0000', '5018444', '33.3000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-05 01:30:00'),
('HS-039', 4, 'Hoàng Hải Yến', '079190005566', '0933556677', 33, '25000000', '5000000', '18000000', '5400000', 6, 'Trưởng phòng NS', 'Công ty May mặc XNK', 60, 'Tủ lạnh Samsung Inverter', '12600000', '30.0000', '2415000', '29.6000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-11-05 02:45:00'),
('HS-038', 7, 'Khởi My', '079199005656', '0911116655', 24, '9000000', '6500000', '21000000', '6300000', 12, 'Pha chế', 'Quán Cafe', 5, 'Xe máy Yamaha Janus', '14700000', '30.0000', '1592500', '89.9000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-11-05 04:00:00'),
('HS-037', 4, 'Đặng Thái Sơn', '048201009999', '0911223344', 22, '7000000', '4500000', '20000000', '4000000', 12, 'Bán hàng', 'Cửa hàng Circle K', 4, 'Điện thoại OPPO Reno', '16000000', '20.0000', '1733333', '89.0000', 1, 1, 0, 'pending', 'Chờ thẩm định', '0.0250', '2026-11-05 07:15:00'),
('HS-036', 2, 'Ngô Kiến Huy', '079190004567', '0944445555', 33, '18000000', '3000000', '24000000', '7200000', 12, 'Quản lý kho', 'Công ty TNHH TM DV V', 48, 'Samsung Galaxy S24', '16800000', '30.0000', '1820000', '26.7000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-04 01:50:00'),
('HS-035', 7, 'Jack - J97', '079197007878', '0944448877', 26, '11000000', '8000000', '35000000', '10500000', 12, 'Thực tập sinh', 'Công ty Âm nhạc', 8, 'Xe máy Honda SH Mode', '24500000', '30.0000', '2654166', '96.8000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-11-04 03:05:00'),
('HS-034', 7, 'Vũ Thị Trà My', '079197003322', '0966778899', 26, '16000000', '2000000', '31000000', '9300000', 12, 'Content Creator', 'Công ty TNHH Media Z', 14, 'Máy ảnh Sony ZV-E10', '21700000', '30.0000', '2350833', '27.1000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-11-04 04:20:00'),
('HS-033', 7, 'Bùi Tuấn Kiệt', '079183001111', '0909001122', 40, '28000000', '12000000', '85000000', '25500000', 24, 'Kỹ sư giám sát', 'Công ty XD Hòa Bình', 80, 'Xe máy Honda SH 160i', '59500000', '30.0000', '3966666', '57.0000', 1, 1, 0, 'pending', 'Chờ thẩm định', '0.0250', '2026-11-03 01:40:00'),
('HS-032', 7, 'Phan Đình Phùng', '079186008899', '0933334444', 37, '22000000', '2000000', '21000000', '6300000', 6, 'Giảng viên', 'Đại học Bách Khoa', 96, 'Laptop HP Pavilion', '14700000', '30.0000', '2817500', '21.8000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-03 02:55:00'),
('HS-031', 2, 'Phạm Thị Hương', '079193009090', '0901010101', 30, '12000000', '9000000', '25000000', '5000000', 12, 'Kế toán kho', 'Nội thất Gỗ', 20, 'Laptop Dell Vostro', '20000000', '20.0000', '2166666', '93.0000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-11-03 04:10:00'),
('HS-030', 2, 'Ngô Thanh Vân', '079193004444', '0944556677', 30, '11000000', '1000000', '22000000', '4400000', 12, 'Giáo viên', 'Trường Tiểu học', 40, 'Laptop Dell Inspiron', '17600000', '20.0000', '1906666', '26.4000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-11-02 01:15:00'),
('HS-029', 8, 'Trấn Thành', '079184001122', '0909998877', 39, '35000000', '5000000', '42000000', '8400000', 12, 'Biên tập viên', 'Đài truyền hình TP.HCM', 60, 'Smart TV QLED 75 inch', '33600000', '20.0000', '3640000', '24.6000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-11-02 02:30:00'),
('HS-028', 7, 'Vũ Khắc Tiệp', '079185002121', '0933331212', 38, '50000000', '40000000', '45000000', '13500000', 9, 'Giám đốc', 'Tự doanh', 48, 'iPhone 15 Pro Max', '31500000', '30.0000', '4287500', '88.5000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-11-01 01:45:00'),
('HS-027', 7, 'Đỗ Hữu Phát', '079198005555', '0977889900', 25, '14000000', '8000000', '24000000', '7200000', 9, 'Shipper', 'Giao hàng Tiết kiệm', 10, 'Xe máy Honda Wave', '16800000', '30.0000', '2286666', '73.4000', 1, 1, 0, 'pending', 'Chờ thẩm định', '0.0250', '2026-11-01 03:00:00'),
('HS-026', 4, 'Kelvin Khánh', '079197003344', '0966667777', 26, '15000000', '4000000', '32000000', '9600000', 12, 'Designer', 'Công ty CP Quảng cáo', 18, 'Máy tính đồ họa Asus', '22400000', '30.0000', '2426666', '42.8000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-10-30 01:25:00'),
('HS-025', 8, 'Lý Nhã Kỳ', '079188006666', '0901112233', 35, '40000000', '18000000', '42000000', '12600000', 12, 'Chuyên viên môi giới', 'Công ty Bất động sản', 24, 'Samsung Galaxy Z Fold', '29400000', '30.0000', '3185000', '52.9000', 1, 1, 0, 'pending', 'Chờ thẩm định', '0.0250', '2026-10-30 02:40:00'),
('HS-024', 8, 'Ngọc Trinh', '079194004343', '0988883434', 29, '15000000', '10000000', '32000000', '9600000', 12, 'Nhân viên bán hàng', 'Shop Thời Trang', 2, 'Túi xách (Qua đối tác)', '22400000', '30.0000', '2426666', '82.8000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-10-28 01:05:00'),
('HS-023', 4, 'Thiều Bảo Trâm', '079195005566', '0988889999', 28, '19000000', '3000000', '36000000', '7200000', 12, 'Tư vấn viên', 'Thẩm mỹ viện Kangnam', 24, 'iPhone 15 Pro 256GB', '28800000', '20.0000', '3120000', '32.2000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-10-28 02:20:00'),
('HS-022', 2, 'Huỳnh Minh Tiến', '079202007777', '0933445566', 21, '5000000', '0', '15000000', '3000000', 12, 'Phục vụ', 'Sinh viên / Bán thời gian', 2, 'Laptop Asus Vivobook', '12000000', '20.0000', '1300000', '26.0000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-10-25 01:35:00'),
('HS-021', 7, 'Lê Quang Hải', '079202006565', '0966665656', 21, '3000000', '0', '20000000', '4000000', 12, 'Sinh viên', 'Chưa đi làm', 0, 'Laptop MSI Gaming', '16000000', '20.0000', '1733333', '57.7000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-10-25 02:50:00'),
('HS-020', 2, 'Kim Lý', '079181007788', '0911112222', 42, '28000000', '6000000', '48000000', '14400000', 18, 'Quản lý PT', 'Trung tâm Thể hình', 72, 'Xe máy Honda AirBlade', '33600000', '30.0000', '2706666', '31.0000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-10-22 01:15:00'),
('HS-019', 2, 'Trương Ngọc Ánh', '079185008888', '0966112233', 38, '15000000', '2000000', '18000000', '5400000', 9, 'Điều dưỡng', 'Bệnh viện Quận 1', 120, 'Máy giặt Electrolux', '12600000', '30.0000', '1715000', '24.7000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-10-20 02:30:00'),
('HS-018', 8, 'Lâm Tâm Như', '079196009911', '0988556677', 27, '13000000', '6000000', '28000000', '5600000', 12, 'Kỹ thuật viên', 'Spa Viện thẩm mỹ', 15, 'iPhone 14 Pro 128GB', '22400000', '20.0000', '2426666', '64.8000', 1, 1, 0, 'pending', 'Chờ thẩm định', '0.0250', '2026-10-18 01:45:00'),
('HS-017', 7, 'Trần Đại Nghĩa', '079178008787', '0911117878', 45, '8500000', '6000000', '15000000', '4500000', 9, 'Bảo vệ', 'Công ty Bảo vệ', 36, 'Smart TV LG', '10500000', '30.0000', '1429166', '87.4000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-10-15 03:00:00'),
('HS-016', 8, 'Mỹ Tâm', '079189009900', '0933335555', 34, '50000000', '8000000', '45000000', '13500000', 12, 'Product Manager', 'Công ty Cổ phần VNG', 48, 'MacBook Pro 16 inch', '31500000', '30.0000', '3412500', '22.8000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-10-15 04:15:00'),
('HS-015', 8, 'Võ Minh Châu', '079191002222', '0905667788', 32, '22000000', '4000000', '35000000', '10500000', 12, 'Bếp trưởng', 'Nhà hàng chay', 30, 'Xe máy Yamaha Grande', '24500000', '30.0000', '2654166', '30.2000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-10-12 01:25:00'),
('HS-014', 8, 'Đặng Tuấn Tài', '079197000909', '0944449090', 26, '14000000', '11000000', '22000000', '6600000', 12, 'Thợ máy', 'Gara Ô tô', 12, 'Điện thoại iPhone 13', '15400000', '30.0000', '1668333', '90.4000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-10-10 02:40:00'),
('HS-013', 7, 'Hồ Quang Hiếu', '079192002233', '0977778888', 31, '16000000', '1500000', '18000000', '5400000', 6, 'Kỹ thuật viên', 'Nhà máy Bia Heineken', 36, 'Máy lạnh Daikin', '12600000', '30.0000', '2415000', '24.4000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-10-10 03:55:00'),
('HS-012', 4, 'Trần Thanh Hùng', '079178003333', '0912112233', 45, '30000000', '15000000', '65000000', '19500000', 18, 'Chủ tạp hóa', 'Kinh doanh tự do', 60, 'Smart TV Sony OLED', '45500000', '30.0000', '3665277', '62.2000', 1, 1, 0, 'pending', 'Chờ thẩm định', '0.0250', '2026-10-08 01:35:00'),
('HS-011', 8, 'Nguyễn Tiến Linh', '079196001212', '0909092121', 27, '20000000', '15000000', '40000000', '8000000', 18, 'HLV', 'CLB Thể thao', 24, 'Xe máy Honda AirBlade', '32000000', '20.0000', '2577777', '87.8000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-10-05 02:50:00'),
('HS-010', 2, 'Nguyễn Bích Ngọc', '079200004444', '0977112233', 23, '8000000', '0', '19000000', '3800000', 9, 'Thực tập sinh', 'KPMG', 3, 'iPad Air 5', '15200000', '20.0000', '2068888', '25.8000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-10-05 04:05:00'),
('HS-009', 7, 'Đông Nhi', '079194004455', '0901114444', 29, '26000000', '5000000', '32000000', '6400000', 12, 'Tiếp viên', 'Vietnam Airlines', 24, 'iPhone 15 256GB', '25600000', '20.0000', '2773333', '29.8000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-10-02 01:20:00'),
('HS-008', 4, 'Hoàng Cảnh Du', '079198003434', '0933334343', 25, '10000000', '7000000', '25000000', '5000000', 12, 'Photographer', 'Studio Ảnh', 6, 'Máy ảnh Canon EOS R50', '20000000', '20.0000', '2166666', '91.6000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-10-02 02:35:00'),
('HS-007', 4, 'Phan Đình Tùng', '079187005555', '0933112233', 36, '24000000', '2000000', '29000000', '8700000', 6, 'Kỹ sư điện', 'Tập đoàn Điện lực', 84, 'Máy lạnh Panasonic', '20300000', '30.0000', '3890833', '24.5000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-09-28 03:50:00'),
('HS-006', 8, 'Ông Cao Thắng', '079187006677', '0944447777', 36, '18000000', '3000000', '20000000', '6000000', 9, 'Trưởng chuyền', 'Công ty TNHH SX', 60, 'Tủ lạnh LG Inverter', '14000000', '30.0000', '1905555', '27.2000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-09-25 01:40:00'),
('HS-005', 7, 'Phạm Thu Hằng', '079190005656', '0977776565', 33, '9500000', '3000000', '18000000', '5400000', 9, 'Giáo viên', 'Trường Mầm Non', 48, 'Máy giặt Samsung', '12600000', '30.0000', '1715000', '49.6000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-09-22 02:55:00'),
('HS-004', 4, 'Đoàn Thiên Ân', '079198006666', '0908112233', 25, '16000000', '5000000', '25000000', '7500000', 12, 'Nhân viên Marketing', 'Công ty TNHH ABC', 16, 'Samsung Galaxy S23', '17500000', '30.0000', '1895833', '43.0000', 1, 1, 1, 'pending', 'Chờ thẩm định', '0.0250', '2026-09-18 04:10:00'),
('HS-003', 4, 'Bảo Thy', '079196008899', '0966669999', 27, '15000000', '0', '22000000', '4400000', 9, 'Giao dịch viên', 'Ngân hàng ACB', 18, 'iPad Air 5 256GB', '17600000', '20.0000', '2395555', '15.9000', 1, 1, 1, 'approved', 'Đã phê duyệt', '0.0250', '2026-09-15 01:30:00'),
('HS-002', 7, 'Võ Thành Tâm', '079184007878', '0988888787', 39, '12000000', '8000000', '20000000', '6000000', 12, 'Thợ hàn', 'Xưởng cơ khí', 24, 'Tủ lạnh LG Inverter', '14000000', '30.0000', '1516666', '79.3000', 1, 1, 0, 'rejected', 'Đã từ chối', '0.0250', '2026-09-10 02:45:00'),
('HS-001', 7, 'Mai Thu Huyền', '079192007777', '0988990011', 31, '20000000', '12000000', '32000000', '6400000', 12, 'Quản lý cửa hàng', 'Công ty Mỹ phẩm', 24, 'iPhone 15 Plus 128GB', '25600000', '20.0000', '2773333', '73.8000', 1, 1, 0, 'pending', 'Chờ thẩm định', '0.0250', '2026-09-05 04:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('staff','appraiser','manager') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Hồ Tấn Phát (Thẩm định)', 'ho.tanphat_app', '$2y$10$ntnHjHIDirSEeU3yCaIHmOrqvVZ2TY4K4P07soklgKwVZ/Wz0MuLW', 'appraiser', '2026-11-12 04:48:49'),
(2, 'Lê Bảo Ngọc (Tín dụng)', 'le.baongoc_st', '$2y$10$ntnHjHIDirSEeU3yCaIHmOrqvVZ2TY4K4P07soklgKwVZ/Wz0MuLW', 'staff', '2026-11-12 04:48:49'),
(3, 'Lương Thế Vinh (Thẩm định)', 'luong.thevinh_app', '$2y$10$ntnHjHIDirSEeU3yCaIHmOrqvVZ2TY4K4P07soklgKwVZ/Wz0MuLW', 'appraiser', '2026-11-12 04:48:49'),
(4, 'Phạm Gia Hân (Tín dụng)', 'pham.giahan_st', '$2y$10$ntnHjHIDirSEeU3yCaIHmOrqvVZ2TY4K4P07soklgKwVZ/Wz0MuLW', 'staff', '2026-11-12 04:48:49'),
(5, 'Nguyễn Thị Thu Hoài (Thẩm định)', 'nguyen.thuhoai_app', '$2y$10$ntnHjHIDirSEeU3yCaIHmOrqvVZ2TY4K4P07soklgKwVZ/Wz0MuLW', 'appraiser', '2026-11-12 04:48:49'),
(6, 'Trần Quản Trị (Manager)', 'admin', '$2y$10$ntnHjHIDirSEeU3yCaIHmOrqvVZ2TY4K4P07soklgKwVZ/Wz0MuLW', 'manager', '2026-11-12 04:48:49'),
(7, 'Lê Minh Tuấn (Tín dụng)', 'le.minhtuan_st', '$2y$10$ntnHjHIDirSEeU3yCaIHmOrqvVZ2TY4K4P07soklgKwVZ/Wz0MuLW', 'staff', '2026-11-12 05:12:51'),
(8, 'Ngô Thanh Sơn (Tín dụng)', 'ngo.thanhson_st', '$2y$10$ntnHjHIDirSEeU3yCaIHmOrqvVZ2TY4K4P07soklgKwVZ/Wz0MuLW', 'staff', '2026-11-12 05:12:51');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
