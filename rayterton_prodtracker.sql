-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Agu 2025 pada 04.07
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

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
-- Struktur dari tabel `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('Hadir','Telat','Izin','Sakit','Alpa') DEFAULT 'Alpa',
  `location` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `user_id`, `date`, `check_in`, `check_out`, `status`, `location`, `notes`, `created_at`) VALUES
(8, 3, '2025-08-14', NULL, NULL, 'Izin', '', 'Sedang sakit', '2025-08-14 08:15:03'),
(9, 3, '2025-08-15', '16:24:52', '16:24:54', 'Alpa', '', NULL, '2025-08-15 09:24:52'),
(10, 4, '2025-08-21', '17:25:13', NULL, 'Alpa', '', NULL, '2025-08-21 10:25:13'),
(11, 4, '2025-08-22', '13:30:08', '14:02:47', 'Alpa', '', NULL, '2025-08-22 06:30:08'),
(13, 2, '2025-08-22', '13:50:58', NULL, 'Alpa', '', NULL, '2025-08-22 06:50:58'),
(14, 5, '2025-08-22', '13:54:58', NULL, 'Alpa', '', NULL, '2025-08-22 06:54:58'),
(19, 3, '2025-08-22', '14:43:21', NULL, 'Alpa', 'Kantor Mentes', NULL, '2025-08-22 07:43:21'),
(20, 4, '2025-08-25', '08:47:21', NULL, 'Telat', 'Kantor Mentes', NULL, '2025-08-25 01:47:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` enum('Employe','Internship') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `employees`
--

INSERT INTO `employees` (`employee_id`, `user_id`, `name`, `position`, `phone`, `created_at`, `updated_at`) VALUES
(1, 4, 'Rizky Putra Hadi Sarwono', 'Internship', '085811095721', '2025-08-21 10:41:30', '2025-08-22 08:25:10'),
(2, 5, 'Bryan Syahputra', 'Internship', '088213292741', '2025-08-22 06:54:36', '2025-08-22 06:54:36'),
(3, 6, 'Muhammad Nabil', 'Internship', NULL, '2025-08-22 07:09:52', '2025-08-22 07:09:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `production_reports`
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
-- Dumping data untuk tabel `production_reports`
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
-- Struktur dari tabel `users`
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
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'Admin Rayterton', 'admin@rayterton.local', '$2y$10$O4Nw.K1f1C0GQ6gQCNhZC.xs6QzD7xHhYI6xw6n8QCDWZ0hV6qj7S', 'admin', 1, '2025-08-14 05:59:23'),
(2, 'admin2', 'admin2@rayterton.local', '$2y$10$3kp0XtkEuKnIHqGVXPl61upDn1S6dC1NtOfAQS8X0UV25gJiLiHme', 'staff', 1, '2025-08-14 06:02:33'),
(3, 'adminn', 'adminn@rayterton.local', '$2y$10$hE1zWVRUROvzZ94x5bjUcuf8a6cxXuGd7gbXcjCCg5Ik7IwSqmOBy', 'admin', 1, '2025-08-14 07:15:59'),
(4, 'Putra', 'Putra@rayterton.local', '$2y$10$KmVIRJIylokPZ.ovsinT6.BPBNZEoMhVhREjwb7WfLCtT1PoutkKG', 'staff', 1, '2025-08-21 10:10:43'),
(5, 'Bryan', 'Bryan@rayterton.local', '$2y$10$IqojS1l34SdhAcqsN9WJ3.SF2dKgCskYXv0e25RazpPUW1ZaPn..S', 'staff', 1, '2025-08-22 06:53:58'),
(6, 'Nabil', 'Nabil@rayterton.local', '$2y$10$9fhlx6Wz64iCv9sc7Ck8ruCEy1CM3yd.3l51Z/ef63FWCvymkFwfe', 'staff', 1, '2025-08-22 07:09:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `work_force`
--

CREATE TABLE `work_force` (
  `workforce_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `workforce_name` varchar(100) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`date`);

--
-- Indeks untuk tabel `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `production_reports`
--
ALTER TABLE `production_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_reports_user` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `work_force`
--
ALTER TABLE `work_force`
  ADD PRIMARY KEY (`workforce_id`),
  ADD KEY `fk_employee_workforce` (`employee_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `production_reports`
--
ALTER TABLE `production_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `work_force`
--
ALTER TABLE `work_force`
  MODIFY `workforce_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `production_reports`
--
ALTER TABLE `production_reports`
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `work_force`
--
ALTER TABLE `work_force`
  ADD CONSTRAINT `fk_employee_workforce` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
