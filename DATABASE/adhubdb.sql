-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2026 at 04:14 PM
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
-- Database: `adhubdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_deliverables`
--

CREATE TABLE `tbl_deliverables` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file_path` text NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `version` int(11) DEFAULT 1,
  `approval_status` enum('pending','approved','revision') NOT NULL DEFAULT 'pending',
  `review_note` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoices`
--

CREATE TABLE `tbl_invoices` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_messages`
--

CREATE TABLE `tbl_messages` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payments`
--

CREATE TABLE `tbl_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_projects`
--

CREATE TABLE `tbl_projects` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `budget` decimal(12,2) DEFAULT 0.00,
  `payment_status` varchar(50) DEFAULT 'unpaid',
  `description` text DEFAULT NULL,
  `status` enum('pending','in-progress','revision','completed','rejected') DEFAULT 'pending',
  `progress_percent` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  CONSTRAINT `chk_service_type` CHECK (`service_type` IN ('','Graphic Design','Logo Design','Slogan','Poster'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','admin') DEFAULT 'client',
  `company_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `full_name`, `email`, `password`, `role`, `company_name`, `created_at`) VALUES
(1, 'ADMIN', 'admin@gmail.com', '$2y$10$ejckuBAK4y2lrstGCDwE5O2Ra5aBdsd6FXxtdlrPPKJmj5XCNBbGy', 'admin', 'ADMIN', '2026-05-11 16:30:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_deliverables`
--
ALTER TABLE `tbl_deliverables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `tbl_invoices`
--
ALTER TABLE `tbl_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `tbl_messages`
--
ALTER TABLE `tbl_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_deliverables`
--
ALTER TABLE `tbl_deliverables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_invoices`
--
ALTER TABLE `tbl_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_messages`
--
ALTER TABLE `tbl_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_deliverables`
--
ALTER TABLE `tbl_deliverables`
  ADD CONSTRAINT `tbl_deliverables_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_invoices`
--
ALTER TABLE `tbl_invoices`
  ADD CONSTRAINT `tbl_invoices_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_messages`
--
ALTER TABLE `tbl_messages`
  ADD CONSTRAINT `tbl_messages_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `tbl_users` (`id`);

--
-- Constraints for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  ADD CONSTRAINT `tbl_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  ADD CONSTRAINT `tbl_projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_chat_messages`
--

CREATE TABLE `tbl_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `tbl_chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_chat_messages_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_requests`
--

CREATE TABLE `tbl_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','accepted','done') NOT NULL DEFAULT 'open',
  `project_id` int(11) DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `tbl_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_requests_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_retainers`
--

CREATE TABLE `tbl_retainers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `tbl_retainers_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_time_logs`
--

CREATE TABLE `tbl_time_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `hours` decimal(6,2) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `tbl_time_logs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_time_logs_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
