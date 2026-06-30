-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 01:30 PM
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
-- Database: `security_bmi_lab`
--

-- --------------------------------------------------------

--
-- Table structure for table `persons`
--

CREATE TABLE `persons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `height` decimal(5,2) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `bmi` decimal(5,2) NOT NULL,
  `category` varchar(30) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `persons`
--

INSERT INTO `persons` (`id`, `user_id`, `name`, `age`, `height`, `weight`, `bmi`, `category`, `notes`, `created_at`) VALUES
(1, 2, 'Aiman Updated', 22, 1.70, 65.00, 10.00, 'Normal', 'testing protected field update', '2026-06-29 14:25:48'),
(2, 2, 'Nur Aisyah Zulkifli', 22, 1.60, 49.00, 19.14, 'Normal', 'Healthy BMI range', '2026-06-29 14:25:48'),
(3, 3, 'Ahmad Farhan Roslan', 21, 1.75, 82.00, 26.78, 'Overweight', 'Needs weight monitoring', '2026-06-29 14:25:48'),
(4, 4, 'Siti Nur Balqis Ismail', 23, 1.55, 43.00, 17.90, 'Underweight', 'Low BMI sample', '2026-06-29 14:25:48'),
(5, 5, 'Muhammad Danish Azman', 22, 1.68, 72.00, 25.51, 'Overweight', 'Slightly above normal range', '2026-06-29 14:25:48'),
(6, 6, 'Nur Imanina Hassan', 21, 1.62, 54.00, 20.58, 'Normal', 'Normal BMI record', '2026-06-29 14:25:48'),
(7, 7, 'Amirul Hakim Rahman', 24, 1.72, 95.00, 32.11, 'Obese', 'High BMI sample for monitoring', '2026-06-29 14:25:48'),
(8, 8, 'Farah Nadhirah Yusof', 22, 1.58, 50.00, 20.03, 'Normal', 'Regular BMI check', '2026-06-29 14:25:48'),
(9, 9, 'Haziq Irfan Abdullah', 23, 1.80, 68.00, 20.99, 'Normal', 'Tall student with normal BMI', '2026-06-29 14:25:48'),
(10, 10, 'Nurul Syafiqah Kamarudin', 21, 1.57, 61.00, 24.75, 'Normal', 'Near upper normal range', '2026-06-29 14:25:48'),
(11, 11, 'Mohd Hafiz Jamal', 25, 1.69, 78.00, 27.31, 'Overweight', 'Sample external user record', '2026-06-29 14:25:48'),
(12, 12, 'Nadia Sofea Ramli', 24, 1.63, 47.00, 17.69, 'Underweight', 'Underweight example record', '2026-06-29 14:25:48'),
(13, 13, 'Fikri Hazim Othman', 26, 1.74, 88.00, 29.07, 'Overweight', 'Close to obese threshold', '2026-06-29 14:25:48'),
(14, 14, 'Puteri Amira Shafie', 23, 1.59, 52.00, 20.57, 'Normal', 'Normal BMI record', '2026-06-29 14:25:48'),
(15, 15, 'Siti Hajar Ibrahim', 35, 1.58, 56.00, 22.43, 'Normal', 'Staff sample BMI record', '2026-06-29 14:25:48'),
(16, 16, 'Faizal Zainuddin', 38, 1.73, 85.00, 28.40, 'Overweight', 'Staff overweight sample', '2026-06-29 14:25:48'),
(17, 17, 'Noraini Salleh', 42, 1.61, 63.00, 24.30, 'Normal', 'Staff health monitoring record', '2026-06-29 14:25:48'),
(18, 18, 'Khairul Anwar Musa', 40, 1.76, 98.00, 31.64, 'Obese', 'Staff high BMI sample', '2026-06-29 14:25:48'),
(19, 19, 'Amran Hamid', 50, 1.70, 74.00, 25.61, 'Overweight', 'Admin sample BMI record', '2026-06-29 14:25:48'),
(20, 20, 'Mazlina Ahmad', 45, 1.60, 58.00, 22.66, 'Normal', 'Admin sample BMI record', '2026-06-29 14:25:48'),
(21, 1, '', 21, 1.70, -65.00, -22.49, 'Underweight', '', '2026-06-29 15:36:06'),
(22, 1, '', -5, 0.00, -70.00, 0.00, '', 'testing invalid BMI', '2026-06-29 15:46:49'),
(23, 1, '', 22, 1.70, 65.00, 0.00, '', 'empty name test', '2026-06-29 15:54:04'),
(24, 1, 'Sara', 21, 1.60, 55.00, 0.00, '', 'user 2 record', '2026-06-29 15:58:01'),
(25, 1, 'XSS Test', 22, 1.70, 65.00, 0.00, '', '<img src=x onerror=alert(1)>', '2026-06-29 16:12:44'),
(26, 1, 'Negative Weight Test', 22, 1.70, -70.00, 0.00, '', 'Testing negative weight', '2026-06-30 09:24:29'),
(27, 1, '', 0, 0.00, 0.00, 0.00, '', '', '2026-06-30 09:27:09'),
(28, 1, 'Negative Weight Test', 22, 1.70, -70.00, 0.00, '', 'Testing negative weight', '2026-06-30 09:27:36'),
(29, 1, 'Negative Weight Test', 22, 1.70, -70.00, 0.00, '', 'Testing negative weight', '2026-06-30 09:28:07'),
(30, 1, 'Negative Weight Test', 22, 1.70, -70.00, 0.00, '', 'Testing negative weight', '2026-06-30 09:32:28'),
(31, 1, 'Aiman', 22, 1.70, 65.00, 0.00, '', 'Normal record', '2026-06-30 09:39:47'),
(32, 1, 'Aiman Updated', 22, 1.70, 65.00, 22.49, 'Normal', 'Testing protected fields', '2026-06-30 09:55:49'),
(33, 2, 'Aisyah', 22, 1.70, 65.00, 22.49, 'Normal', 'Normal record', '2026-06-30 10:09:43'),
(34, 1, 'XSS Test', 22, 1.70, 65.00, 22.49, 'Normal', '<img src=x onerror=alert(1)>', '2026-06-30 10:18:09'),
(35, 1, 'XSS Test', 22, 1.70, 65.00, 22.49, 'Normal', '<img src=x onerror=alert(1)>', '2026-06-30 10:28:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('user','staff','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Muhammad Aiman Hakimi', 'aiman@student.utm.my', '', '$2y$10$tLS8VRbLWMEulFoWC9v4EO..mMhEtjN.ydAr/g8Bw2H1TviHQm/3q', 'user', '2026-06-29 14:25:48'),
(2, 'Nur Aisyah Zulkifli', 'aisyah@student.utm.my', '', '$2y$10$hCQ7.PvV897G1LqQDdNp6uLaEV9LzeblbIYze7rqR15o/f5EZajf2', 'user', '2026-06-29 14:25:48'),
(3, 'Ahmad Farhan Roslan', 'farhan@student.utm.my', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(4, 'Siti Nur Balqis Ismail', 'balqis@student.utm.my', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(5, 'Muhammad Danish Azman', 'danish@student.utm.my', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(6, 'Nur Imanina Hassan', 'imanina@student.utm.my', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(7, 'Amirul Hakim Rahman', 'amirul@student.utm.my', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(8, 'Farah Nadhirah Yusof', 'farah@student.utm.my', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(9, 'Haziq Irfan Abdullah', 'haziq@student.utm.my', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(10, 'Nurul Syafiqah Kamarudin', 'syafiqah@student.utm.my', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(11, 'Mohd Hafiz Jamal', 'hafiz@google.com', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(12, 'Nadia Sofea Ramli', 'nadia@google.com', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(13, 'Fikri Hazim Othman', 'fikri@google.com', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(14, 'Puteri Amira Shafie', 'amira@google.com', 'password123', 'password123', 'user', '2026-06-29 14:25:48'),
(15, 'Siti Hajar Ibrahim', 'siti.hajar@utm.my', 'password123', 'password123', 'staff', '2026-06-29 14:25:48'),
(16, 'Faizal Zainuddin', 'faizal.zainuddin@utm.my', 'password123', 'password123', 'staff', '2026-06-29 14:25:48'),
(17, 'Noraini Salleh', 'noraini.salleh@utm.my', 'password123', 'password123', 'staff', '2026-06-29 14:25:48'),
(18, 'Khairul Anwar Musa', 'khairul.anwar@utm.my', 'password123', 'password123', 'staff', '2026-06-29 14:25:48'),
(19, 'Amran Hamid', 'amran.hamid@utm.my', 'password123', 'password123', 'admin', '2026-06-29 14:25:48'),
(20, 'Mazlina Ahmad', 'mazlina.ahmad@utm.my', 'password123', 'password123', 'admin', '2026-06-29 14:25:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `persons`
--
ALTER TABLE `persons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `persons`
--
ALTER TABLE `persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `persons`
--
ALTER TABLE `persons`
  ADD CONSTRAINT `persons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
