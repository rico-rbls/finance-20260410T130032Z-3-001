-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2026 at 09:59 AM
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
-- Database: `finance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget_reservations`
--

CREATE TABLE `budget_reservations` (
  `res_id` int(11) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `po_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount_reserved` decimal(15,2) DEFAULT NULL,
  `status` enum('pending','committed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_reservations`
--

INSERT INTO `budget_reservations` (`res_id`, `dept_id`, `description`, `amount_reserved`, `status`) VALUES
(1, 1, 'Lab', 100.00, ''),
(2, 1, 'Tour', 1000.00, ''),
(6, 1, 'New Laboratory Microscopes', 12500.00, 'pending'),
(7, 2, 'Annual Software Licenses', 4500.00, ''),
(8, 3, 'Library Book Restock', 2100.00, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `account_id` int(11) NOT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`account_id`, `account_name`, `account_type`, `balance`) VALUES
(1, 'Cash at Bank', 'Asset', 100000.00),
(2, 'Accounts Receivable', 'Asset', 100.00),
(3, 'Accounts Payable', 'Liability', 0.00),
(4, 'Cash', 'Asset', 250000.00),
(5, 'Accounts Receivable', 'Asset', 15100.00),
(6, 'Tuition Revenue', 'Revenue', 0.00),
(7, 'Institutional Budget', 'Equity', 500000.00),
(8, 'Accounts Payable', 'Liability', 0.00),
(9, 'Accounts Payable', 'Liability', 0.00),
(10, 'Supplies Expense', 'Expense', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) DEFAULT NULL,
  `total_budget` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`, `total_budget`) VALUES
(1, 'College of Computing Science and Engineering ', 50000.00),
(2, 'College of Tourism and Hospitality', 150000.00),
(3, 'College of Human Kinetics', 85000.00),
(4, 'College of Teacher Education', 50000.00),
(5, 'College of Business Administration', 120000.00);

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `date_issued` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_id`, `student_name`, `total_amount`, `date_issued`, `status`) VALUES
(1, 'Jazer', 100.00, '2026-03-19 16:00:00', 'paid'),
(2, 'Jazer', 12.00, '2026-03-19 16:00:00', 'paid'),
(3, 'John Doe', 100.00, '2026-03-19 16:00:00', 'unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `entry_id` int(11) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`entry_id`, `transaction_date`, `description`, `reference_type`, `reference_id`) VALUES
(1, '2026-03-20 06:09:31', 'Student Payment Receipt - Invoice #1', 'Student_Invoice', 1),
(2, '2026-03-20 06:10:03', 'Student Payment Receipt - Invoice #2', 'Student_Invoice', 2);

-- --------------------------------------------------------

--
-- Table structure for table `ledger_details`
--

CREATE TABLE `ledger_details` (
  `detail_id` int(11) NOT NULL,
  `entry_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ledger_details`
--

INSERT INTO `ledger_details` (`detail_id`, `entry_id`, `account_id`, `debit`, `credit`) VALUES
(1, 1, 1, 100.00, 0.00),
(2, 1, 2, 0.00, 100.00),
(3, 2, 1, 12.00, 0.00),
(4, 2, 2, 0.00, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(15,2) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ap_invoices`
--

CREATE TABLE `ap_invoices` (
  `ap_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `amount_due` decimal(15,2) DEFAULT NULL,
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `date_issued` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_payments`
--

CREATE TABLE `vendor_payments` (
  `vendor_payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `ap_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `status` enum('pending','approved','received','cancelled') DEFAULT 'pending',
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`po_id`, `dept_id`, `vendor_id`, `total_amount`, `status`, `date_created`) VALUES
(1, 1, 1, 1000.00, 'pending', '2026-03-20 07:00:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','finance_officer','dept_head','student') DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `role`) VALUES
(1, 'admin_user', '$2y$10$oxw8/dFBmROhfYRifs1pyeIOvzmTbRxRgVP17wdD9E5Variv3S2.W', 'Finance Director', 'admin'),
(2, 'officer_one', '$2y$10$hyM9A/SwRXlpuJNOg4A3I.TkgWxjHWuD6yqgZOX/.KYSrvW6Sy6KC', 'Accountant Smith', 'finance_officer'),
(3, 'head_it', '$2y$10$1FI8DtuZUAE2RKY7QPDV0OfMht6VAKOSkCIEpA/4rdrGxUcnL9kvO', 'IT Dept Head', 'dept_head'),
(4, 'student_test', '$2y$10$C1W2Fn4ju.u4emkeXnJrteAFHncu3V6v/VEuYz4sHS7sjZQq8U9Ma', 'John Doe', 'student');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `vendor_id` int(11) NOT NULL,
  `vendor_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`vendor_id`, `vendor_name`, `email`) VALUES
(1, 'ASUS', 'asus@gmail.com'),
(2, 'ASUS', 'asus@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget_reservations`
--
ALTER TABLE `budget_reservations`
  ADD PRIMARY KEY (`res_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`account_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`entry_id`);

--
-- Indexes for table `ledger_details`
--
ALTER TABLE `ledger_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `entry_id` (`entry_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`vendor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget_reservations`
--
ALTER TABLE `budget_reservations`
  MODIFY `res_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ledger_details`
--
ALTER TABLE `ledger_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ap_invoices`
--
ALTER TABLE `ap_invoices`
  MODIFY `ap_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_payments`
--
ALTER TABLE `vendor_payments`
  MODIFY `vendor_payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `vendor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_reservations`
--
ALTER TABLE `budget_reservations`
  ADD KEY `po_id` (`po_id`),
  ADD CONSTRAINT `budget_reservations_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`),
  ADD CONSTRAINT `budget_reservations_ibfk_2` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`);

--
-- Constraints for table `ap_invoices`
--
ALTER TABLE `ap_invoices`
  ADD PRIMARY KEY (`ap_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `dept_id` (`dept_id`),
  ADD CONSTRAINT `ap_invoices_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `ap_invoices_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`),
  ADD CONSTRAINT `ap_invoices_ibfk_3` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- Constraints for table `vendor_payments`
--
ALTER TABLE `vendor_payments`
  ADD PRIMARY KEY (`vendor_payment_id`),
  ADD KEY `ap_id` (`ap_id`),
  ADD CONSTRAINT `vendor_payments_ibfk_1` FOREIGN KEY (`ap_id`) REFERENCES `ap_invoices` (`ap_id`);

--
-- Constraints for table `ledger_details`
--
ALTER TABLE `ledger_details`
  ADD CONSTRAINT `ledger_details_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `journal_entries` (`entry_id`),
  ADD CONSTRAINT `ledger_details_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`account_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
