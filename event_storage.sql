-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2025 at 09:57 PM
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
-- Database: `event_storage`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`) VALUES
(12, 'CSE');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `event_description` text DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `branch` varchar(50) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_faculty` varchar(50) DEFAULT NULL,
  `is_collaboration` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `event_description`, `from_date`, `to_date`, `media_path`, `branch`, `created_by`, `created_at`, `assigned_faculty`, `is_collaboration`) VALUES
(45, 'yoga day', 'asdfghjkl', '2025-03-28', '2025-04-01', '[\"ideal\\/CSE\\/jassica\\/MyUploads\\/yoga day\\/Screenshot 2024-01-18 132313.png\",\"ideal\\/CSE\\/jassica\\/MyUploads\\/yoga day\\/Screenshot 2024-01-18 132336.png\"]', 'CSE', 'jassica', '2025-03-28 05:31:59', NULL, 0),
(49, 'test event', 'faculty', '2025-03-28', '2025-04-03', '[\"ideal\\/CSE\\/kumar\\/MyUploads\\/test event\\/salesforce ss.jpg\"]', 'CSE', 'kumar', '2025-03-29 05:29:54', NULL, 0),
(56, 'collab test', 'qwq', '2025-03-30', '2025-04-04', '[]', 'CSE', 'dinesh', '2025-03-30 10:33:43', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Principal','HOD','Faculty','Student') NOT NULL,
  `branch` varchar(50) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `branch`, `date_created`) VALUES
(28, 'dinesh', 'budidadineshkumar@gmail.com', '$2y$10$D8.4OM8dXKdGd7Bj57po0eMfKsv7F8vx/FvfMQXFeG/NdMMXgJ5Ui', 'HOD', 'CSE', '2025-03-18 00:19:05'),
(29, 'kumar', 'budidadineshkumar123@gmail.com', '$2y$10$ijJGoJg4H0mRBfTMF77ZLuxGKM/0cv7hrZGovdkDigZYNZ3W/ehGK', 'Faculty', 'CSE', '2025-03-18 00:21:02'),
(31, 'jassica', 'kjassica79@gmail.com', '$2y$10$zPt/hwD9xH6FGhIDEPv4z.e8hJtxMgMsPexvs9WkM9g7D9CjU2KH2', 'Faculty', 'CSE', '2025-03-28 10:56:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_name` (`name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
