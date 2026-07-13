-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 08:01 AM
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
-- Database: `hostel_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `user_id`, `title`, `message`, `created_at`, `updated_at`) VALUES
(1, 1, 'Welcome to Academic Year', 'Welcome to the new academic year! Please ensure all fees are paid on time.', '2025-11-22 10:18:38', NULL),
(2, 1, 'Hostel Inspection', 'Hostel inspection will be conducted next week. Please keep your rooms clean.', '2025-11-22 10:18:38', NULL),
(3, 1, 'WiFi Password Update', 'New WiFi passwords will be distributed tomorrow morning.', '2025-11-22 10:18:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reads`
--

CREATE TABLE `announcement_reads` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checkins_checkouts`
--

CREATE TABLE `checkins_checkouts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_number` varchar(10) NOT NULL,
  `room_number` int(11) NOT NULL DEFAULT 0,
  `type` enum('Check-In','Check-Out') NOT NULL,
  `place` varchar(100) DEFAULT NULL,
  `time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_number` int(11) NOT NULL,
  `designation` enum('Student','Security') NOT NULL,
  `complaint` text NOT NULL,
  `status` enum('Pending','Notified','Action Taken','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_number` varchar(10) DEFAULT NULL,
  `remarks` varchar(255) NOT NULL,
  `payment_month` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receipt` varchar(255) NOT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_number` int(11) NOT NULL,
  `room_type` enum('Regular','Sick Room') NOT NULL DEFAULT 'Regular',
  `status` enum('Available','Occupied','Maintenance') NOT NULL DEFAULT 'Available',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_number`, `room_type`, `status`, `assigned_to`, `created_at`) VALUES
(1, 'Sick Room', 'Available', NULL, '2025-11-22 09:15:25'),
(2, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(3, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(4, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(5, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(6, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(7, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(8, 'Regular', 'Occupied', NULL, '2025-11-22 09:15:25'),
(9, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(10, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(11, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(12, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(13, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(14, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(15, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(16, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(17, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(18, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(19, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(20, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(21, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(22, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(23, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(24, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(25, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(26, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(27, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(28, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(29, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(30, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(31, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(32, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(33, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(34, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(35, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(36, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(37, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(38, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(39, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(40, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(41, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(42, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(43, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(44, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(45, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(46, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(47, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(48, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(49, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(50, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(51, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(52, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(53, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(54, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(55, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(56, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(57, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(58, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(59, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(60, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(61, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(62, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(63, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(64, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(65, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(66, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(67, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(68, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(69, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(70, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(71, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(72, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(73, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(74, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(75, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(76, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(77, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(78, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(79, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(80, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(81, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(82, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(83, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(84, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(85, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(86, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(87, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(88, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(89, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(90, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(91, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(92, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(93, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(94, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(95, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(96, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(97, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(98, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(99, 'Regular', 'Available', NULL, '2025-11-22 09:15:25'),
(100, 'Regular', 'Available', NULL, '2025-11-22 09:15:25');

-- --------------------------------------------------------

--
-- Table structure for table `room_change_requests`
--

CREATE TABLE `room_change_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_room` int(11) NOT NULL,
  `desired_room` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Check','Under Review','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','warden','student','security') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `home_address` text DEFAULT NULL,
  `phone_number` varchar(12) DEFAULT NULL,
  `student_number` varchar(10) DEFAULT NULL,
  `room_number` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `home_address`, `phone_number`, `student_number`, `room_number`, `created_at`, `created_by`) VALUES
(1, 'admin', 'admin@cmb.ac.lk', '$2y$10$/sZyajZFkRvxcT7eTBUSM.1O5NQBPFW5/Yk4vZ.lLb57z6310w0JS', 'admin', 'Admin User', 'SL', '+94717420995', NULL, NULL, '2025-11-22 08:29:13', NULL),
(2, 'warden001', 'warden001@cmb.ac.lk', '$2y$10$QVGy.ArKiGEVrRfhjyElpeAyGeSndYOqFRgEmmQo17okhyyqWCX0K', 'warden', 'Warden', 'Colombo 07', '+94771234567', NULL, NULL, '2025-11-22 08:29:13', 1),
(3, 'security001', 'security001@cmb.ac.lk', '$2y$10$Oe3k/5m1vZRd1GCW.4mOZugmPs3Z01hehkTKzA0prReiNg0i.ag/q', 'security', 'Security', 'Colombo 05', '+94771234568', NULL, NULL, '2025-11-22 08:29:13', 1),
(4, 'student001', 'student001@cmb.ac.lk', '$2y$10$LiJMBKDm4c.FpqVf1Fxmz.nBO111cAYmD.jvKK8Zxax6EYYFMtIg6', 'student', 'Student 01', 'Kandy', '94947712345', '2023t01871', 8, '2025-11-22 08:29:13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_checkin_status`
--

CREATE TABLE `user_checkin_status` (
  `user_id` int(11) NOT NULL,
  `current_status` enum('Checked-In','Checked-Out') NOT NULL DEFAULT 'Checked-Out',
  `last_checkin_time` datetime DEFAULT NULL,
  `last_checkout_time` datetime DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`announcement_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `checkins_checkouts`
--
ALTER TABLE `checkins_checkouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_checkins_type_time` (`type`,`time`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_complaints_user_status` (`user_id`,`status`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_payments_user_date` (`user_id`,`paid_at`),
  ADD KEY `idx_payments_month` (`user_id`,`payment_month`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_number`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `room_change_requests`
--
ALTER TABLE `room_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_room_change_user_status` (`user_id`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `user_checkin_status`
--
ALTER TABLE `user_checkin_status`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `checkins_checkouts`
--
ALTER TABLE `checkins_checkouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `room_change_requests`
--
ALTER TABLE `room_change_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD CONSTRAINT `announcement_reads_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `checkins_checkouts`
--
ALTER TABLE `checkins_checkouts`
  ADD CONSTRAINT `checkins_checkouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `room_change_requests`
--
ALTER TABLE `room_change_requests`
  ADD CONSTRAINT `room_change_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_change_requests_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_checkin_status`
--
ALTER TABLE `user_checkin_status`
  ADD CONSTRAINT `user_checkin_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
