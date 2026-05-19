-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 05:24 PM
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
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_deliverables`
--

INSERT INTO `tbl_deliverables` (`id`, `project_id`, `file_path`, `display_name`, `version`, `uploaded_at`) VALUES
(1, 2, 'uploads/deliverables/project_2_v1_1778641610.jpg', 'Poster', 1, '2026-05-13 03:06:50');

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

--
-- Dumping data for table `tbl_invoices`
--

INSERT INTO `tbl_invoices` (`id`, `project_id`, `total_amount`, `amount_paid`, `status`, `due_date`) VALUES
(1, 2, 500.00, 500.00, 'paid', '2026-05-28'),
(3, 4, 1000.00, 1000.00, 'paid', '0000-00-00'),
(4, 5, 500.00, 0.00, 'unpaid', '2026-05-23');

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

--
-- Dumping data for table `tbl_messages`
--

INSERT INTO `tbl_messages` (`id`, `project_id`, `sender_id`, `message`, `sent_at`, `created_at`) VALUES
(1, 2, 2, 'hi', '2026-05-12 06:11:22', '2026-05-12 06:11:22'),
(2, 2, 1, 'hola', '2026-05-12 11:46:04', '2026-05-12 11:46:04'),
(3, 2, 2, 'thank you!!', '2026-05-13 03:08:46', '2026-05-13 03:08:46'),
(4, 2, 2, 'hey', '2026-05-13 03:12:49', '2026-05-13 03:12:49'),
(5, 2, 2, 'hi', '2026-05-13 03:13:14', '2026-05-13 03:13:14'),
(6, 2, 2, 'hey', '2026-05-13 03:16:32', '2026-05-13 03:16:32');

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

--
-- Dumping data for table `tbl_payments`
--

INSERT INTO `tbl_payments` (`id`, `invoice_id`, `amount`, `payment_method`, `paid_at`) VALUES
(1, 1, 500.00, 'GCash', '2026-05-12 11:47:38'),
(4, 3, 500.00, 'GCash', '2026-05-14 03:49:41'),
(5, 3, 500.00, 'Cash', '2026-05-14 03:50:34');

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
  `status` enum('pending','in-progress','revision','completed') DEFAULT 'pending',
  `progress_percent` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_projects`
--

INSERT INTO `tbl_projects` (`id`, `client_id`, `title`, `service_type`, `budget`, `payment_status`, `description`, `status`, `progress_percent`, `created_at`) VALUES
(2, 2, 'Painting', 'Graphic Design', 500.00, 'paid', 'Gusto ko maganda', 'completed', 100, '2026-05-12 05:37:55'),
(4, 2, 'POSTER', 'Graphic Design', 1000.00, 'paid', 'OKI DOC', 'in-progress', 20, '2026-05-14 03:48:16'),
(5, 3, 'Tula', 'Graphic Design', 500.00, 'unpaid', 'Mahaba', 'pending', 0, '2026-05-19 15:04:15');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` text NOT NULL,
  `role` enum('client','admin') DEFAULT 'client',
  `company_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `full_name`, `email`, `password_hash`, `role`, `company_name`, `created_at`) VALUES
(1, 'ADMIN', 'admin@gmail.com', 'ADMIN', 'admin', 'ADMIN', '2026-05-11 16:30:35'),
(2, 'Philip Strongs', 'Phil@gmail.com', 'phil', 'client', 'Strongs', '2026-05-11 17:43:23'),
(3, 'JC Dincos', 'jcdinco@gmail.com', 'jcdinco', 'client', 'Jc company', '2026-05-13 03:22:16'),
(4, 'Gabby', 'gab@gmail.com', 'gabby', 'client', 'Gab company', '2026-05-13 03:24:13');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_invoices`
--
ALTER TABLE `tbl_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_messages`
--
ALTER TABLE `tbl_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
