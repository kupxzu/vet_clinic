-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 09:53 PM
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
-- Database: `vet_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_type` varchar(100) NOT NULL DEFAULT 'Check-up',
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `client_id`, `pet_id`, `appointment_date`, `appointment_time`, `appointment_type`, `status`, `notes`, `date_created`, `date_updated`) VALUES
(1, 2, 1, '2025-05-24', '03:26:00', 'Check-up', 'pending', 'Check ups', '2025-05-23 19:26:28', '2025-05-23 19:36:51');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `pet_name` varchar(255) NOT NULL,
  `breed_id` int(11) NOT NULL,
  `pet_age` int(11) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `client_id`, `pet_name`, `breed_id`, `pet_age`, `date_created`, `date_updated`) VALUES
(1, 2, 'Buddy', 1, 2, '2025-05-23 19:05:57', '2025-05-23 19:05:57'),
(2, 3, 'Bitchass', 2, 1, '2025-05-23 19:52:12', '2025-05-23 19:52:12');

-- --------------------------------------------------------

--
-- Table structure for table `pet_breeds`
--

CREATE TABLE `pet_breeds` (
  `id` int(11) NOT NULL,
  `breed_name` varchar(100) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pet_breeds`
--

INSERT INTO `pet_breeds` (`id`, `breed_name`, `date_created`) VALUES
(1, 'Golden Retriver', '2025-05-23 19:05:19'),
(2, 'Sphinx', '2025-05-23 19:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client') NOT NULL DEFAULT 'client',
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `password`, `role`, `date_created`, `date_updated`) VALUES
(1, 'Admin', 'admin@example.com', 'admin', '$2y$10$qT2fjAffjhRb9hlOUqQsU.uGmvtMBQvsGrxXklE33N4JuzalsW8Hy', 'admin', '2025-05-23 17:46:48', '2025-05-23 17:50:35'),
(2, 'Client 1', 'client@example.com', 'Client 1', '$2y$10$na9dYYzH9INDElzat4WmC.PhNQNPwDuAcZ8vvJWU2scv8IJj39D0C', 'client', '2025-05-23 18:35:33', '2025-05-23 18:35:33'),
(3, 'Client 2', 'client2@example.com', 'Client 2', '$2y$10$/vgv54EZ.CJaxRTs5t4Ld.3uoO7wNnG1Suhn3.ibY1i2wnFkCR2rq', 'client', '2025-05-23 18:44:26', '2025-05-23 18:44:26'),
(4, 'Client 3', 'client3@example.com', 'Client 3', '$2y$10$RoFgklfik2zAc7FVyEkeUu5fIKw1cRD79MILevU0Mo8DDdpJiUReO', 'client', '2025-05-23 18:45:29', '2025-05-23 18:45:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `breed_id` (`breed_id`);

--
-- Indexes for table `pet_breeds`
--
ALTER TABLE `pet_breeds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `breed_name` (`breed_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pet_breeds`
--
ALTER TABLE `pet_breeds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pets_ibfk_2` FOREIGN KEY (`breed_id`) REFERENCES `pet_breeds` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
