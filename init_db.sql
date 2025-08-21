-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2025 at 11:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rayterton_prodtracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('Hadir','Telat','Izin','Sakit','Alpa') DEFAULT 'Alpa',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `user_id`, `date`, `check_in`, `check_out`, `status`, `notes`, `created_at`) VALUES
(8, 3, '2025-08-14', NULL, NULL, 'Izin', 'Sedang sakit', '2025-08-14 08:15:03'),
(9, 3, '2025-08-15', '16:24:52', '16:24:54', 'Alpa', NULL, '2025-08-15 09:24:52');

-- --------------------------------------------------------

--
-- Table structure for table `production_reports`
--

CREATE TABLE `production_reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `job_type` enum('Program','Website','Mobile Apps','Training Materi','Mengajar','QA/Testing','UI/UX','DevOps','Dokumentasi') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Progress','Selesai') DEFAULT 'Progress',
  `proof_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `proof_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_reports`
--

INSERT INTO `production_reports` (`report_id`, `user_id`, `report_date`, `job_type`, `title`, `description`, `status`, `proof_link`, `created_at`, `proof_image`) VALUES
(1, 2, '2025-08-14', 'Website', 'blabla', 'blabla', 'Progress', 'https://blabla', '2025-08-14 06:06:55', NULL),
(2, 2, '2025-08-14', 'Website', 'blabla', 'blabla', 'Progress', 'https://blabla', '2025-08-14 06:07:38', NULL),
(3, 3, '2025-08-15', 'Mengajar', 'Test', '123123', 'Selesai', NULL, '2025-08-15 08:16:39', NULL),
(4, 3, '2025-08-15', 'Program', 'f', 'f', 'Selesai', NULL, '2025-08-15 08:19:18', '../uploads/689eed862e406_Screenshot (2).png'),
(5, 2, '2025-08-15', 'Website', 'Progresss Casa Medica', 'Membuat page login', 'Progress', NULL, '2025-08-15 08:20:55', '../uploads/689eede79d217_Screenshot 2024-01-11 201846.png'),
(6, 3, '2025-08-15', 'UI/UX', 'test kompresi', 'testtest', 'Progress', NULL, '2025-08-15 09:42:31', '../uploads/adminn_UI_UX_20250815_164231.png'),
(7, 3, '2025-08-15', 'Website', 'we', 'we', 'Selesai', NULL, '2025-08-15 09:43:06', '../uploads/adminn_Website_20250815_164306.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'Admin Rayterton', 'admin@rayterton.local', '$2y$10$O4Nw.K1f1C0GQ6gQCNhZC.xs6QzD7xHhYI6xw6n8QCDWZ0hV6qj7S', 'admin', 1, '2025-08-14 05:59:23'),
(2, 'admin2', 'admin2@rayterton.local', '$2y$10$3kp0XtkEuKnIHqGVXPl61upDn1S6dC1NtOfAQS8X0UV25gJiLiHme', 'staff', 1, '2025-08-14 06:02:33'),
(3, 'adminn', 'adminn@rayterton.local', '$2y$10$hE1zWVRUROvzZ94x5bjUcuf8a6cxXuGd7gbXcjCCg5Ik7IwSqmOBy', 'admin', 1, '2025-08-14 07:15:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`date`);

--
-- Indexes for table `production_reports`
--
ALTER TABLE `production_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_reports_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `production_reports`
--
ALTER TABLE `production_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `production_reports`
--
ALTER TABLE `production_reports`
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
