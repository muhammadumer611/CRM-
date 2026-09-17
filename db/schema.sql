-- ========================================================
-- Hostel Management System (HMS) - Production Database Schema
-- Ready for Import in phpMyAdmin / MySQL / MariaDB
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- Table structure for `admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `students`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id_str` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `cnic` varchar(15) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `address` text NOT NULL,
  `profile_img` varchar(255) DEFAULT NULL,
  `cnic_front` varchar(255) DEFAULT NULL,
  `cnic_back` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `guardian_phone` varchar(20) NOT NULL,
  `guardian_cnic` varchar(15) DEFAULT NULL,
  `relation` varchar(50) NOT NULL,
  `resident_type` varchar(50) NOT NULL DEFAULT 'Student',
  `college_university` varchar(255) DEFAULT NULL,
  `job_workplace` varchar(255) DEFAULT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `vehicle_type` varchar(80) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `guardian_address` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `monthly_fee` decimal(10,2) DEFAULT NULL,
  `added_by_name` varchar(100) DEFAULT NULL,
  `added_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id_str` (`student_id_str`),
  UNIQUE KEY `cnic` (`cnic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `rooms`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `block` varchar(50) NOT NULL,
  `floor` varchar(20) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `total_beds` int(11) NOT NULL,
  `occupied_beds` int(11) DEFAULT 0,
  `monthly_fee` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) NOT NULL,
  `status` enum('Available','Partially Occupied','Occupied','Disabled') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`,`block`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `room_allocations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `room_allocations`;
CREATE TABLE `room_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `bed_number` int(11) NOT NULL,
  `joining_date` date NOT NULL,
  `leaving_date` date DEFAULT NULL,
  `status` enum('Active','Closed') DEFAULT 'Active',
  `remarks` text DEFAULT NULL,
  `allocated_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active_bed_flag` varchar(64) GENERATED ALWAYS AS (if(`status` = 'Active',concat(`room_id`,'_',`bed_number`),NULL)) VIRTUAL,
  `active_student_flag` varchar(64) GENERATED ALWAYS AS (if(`status` = 'Active',cast(`student_id` as char charset cp850),NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_active_room_bed` (`active_bed_flag`),
  UNIQUE KEY `uk_active_student` (`active_student_flag`),
  KEY `student_id` (`student_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `room_allocations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `room_allocations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `room_occupants`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `room_occupants`;
CREATE TABLE `room_occupants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_allocation_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `cnic` varchar(15) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `relation` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_room_occupant_cnic` (`cnic`),
  KEY `idx_room_occupants_allocation` (`room_allocation_id`),
  CONSTRAINT `room_occupants_ibfk_1` FOREIGN KEY (`room_allocation_id`) REFERENCES `room_allocations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `reservations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `cnic` varchar(15) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `district` varchar(80) NOT NULL,
  `room_id` int(11) NOT NULL,
  `bed_number` int(11) NOT NULL,
  `reservation_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reservation_date` date NOT NULL,
  `expected_arrival_date` date DEFAULT NULL,
  `status` enum('PENDING','CONFIRMED','ARRIVED','CANCELLED','EXPIRED') NOT NULL DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `reserved_by_name` varchar(100) DEFAULT NULL,
  `converted_student_id` int(11) DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `converted_by_name` varchar(100) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reservations_room_bed` (`room_id`,`bed_number`),
  KEY `idx_reservations_status` (`status`),
  KEY `idx_reservations_cnic` (`cnic`),
  KEY `idx_reservations_date` (`reservation_date`),
  KEY `converted_student_id` (`converted_student_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`converted_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `reservation_payments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reservation_payments`;
CREATE TABLE `reservation_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Online','Card','Other') NOT NULL DEFAULT 'Cash',
  `transaction_ref` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Completed','Reversed') NOT NULL DEFAULT 'Completed',
  `applied_to_student_id` int(11) DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `created_by_admin` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reservation_payment_ref` (`transaction_ref`),
  KEY `idx_reservation_payments_reservation` (`reservation_id`,`payment_date`),
  KEY `created_by_admin` (`created_by_admin`),
  KEY `idx_reservation_payments_applied` (`applied_to_student_id`,`status`),
  CONSTRAINT `fk_reservation_payments_applied_student` FOREIGN KEY (`applied_to_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reservation_payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_payments_ibfk_2` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `fee_records`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `fee_records`;
CREATE TABLE `fee_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `billing_month` int(11) NOT NULL,
  `billing_year` int(11) NOT NULL,
  `invoice_date` date NOT NULL DEFAULT curdate(),
  `amount` decimal(10,2) NOT NULL,
  `additional_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('Paid','Pending','Partial','Overdue') DEFAULT 'Pending',
  `charge_type` varchar(30) NOT NULL DEFAULT 'MONTHLY_FEE',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`,`billing_month`,`billing_year`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  UNIQUE KEY `uk_fee_invoice_number` (`invoice_number`),
  KEY `idx_fee_records_student_period` (`student_id`,`billing_year`,`billing_month`),
  KEY `idx_fee_records_status_due` (`status`,`due_date`),
  CONSTRAINT `fee_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `fee_payments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `fee_payments`;
CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Online','Card','JazzCash','EasyPaisa','Other') NOT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `received_by_admin` int(11) DEFAULT NULL,
  `received_by_name` varchar(100) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `status` enum('Completed','Reversed') NOT NULL DEFAULT 'Completed',
  `reversed_by_admin` int(11) DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `received_by_admin` (`received_by_admin`),
  KEY `idx_fee_payments_invoice` (`invoice_id`,`payment_date`),
  KEY `idx_fee_payments_date` (`payment_date`),
  KEY `fk_fee_payments_reversed_by_admin` (`reversed_by_admin`),
  CONSTRAINT `fee_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `fee_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_payments_ibfk_2` FOREIGN KEY (`received_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fee_payments_reversed_by_admin` FOREIGN KEY (`reversed_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `payment_allocations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payment_allocations`;
CREATE TABLE `payment_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payment_allocations_payment` (`payment_id`),
  KEY `idx_payment_allocations_invoice` (`invoice_id`),
  CONSTRAINT `fk_payment_allocations_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `fee_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_allocations_payment` FOREIGN KEY (`payment_id`) REFERENCES `fee_payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `student_credits`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `student_credits`;
CREATE TABLE `student_credits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `source_type` varchar(50) NOT NULL,
  `source_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_credits_student` (`student_id`),
  CONSTRAINT `student_credits_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `refunds`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `refunds`;
CREATE TABLE `refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `refund_date` date NOT NULL,
  `reason` text NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Online','Card','Other') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `processed_by_admin` int(11) DEFAULT NULL,
  `status` enum('Pending','Processed','Rejected','Cancelled') NOT NULL DEFAULT 'Processed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `refund_number` (`refund_number`),
  KEY `student_id` (`student_id`),
  KEY `processed_by_admin` (`processed_by_admin`),
  CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`processed_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `security_deposits`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `security_deposits`;
CREATE TABLE `security_deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `original_amount` decimal(12,2) NOT NULL,
  `remaining_amount` decimal(12,2) NOT NULL,
  `status` enum('HELD','ADJUSTED','PARTIALLY_REFUNDED','REFUNDED','FORFEITED') NOT NULL DEFAULT 'HELD',
  `processed_by_name` varchar(100) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_security_deposits_student` (`student_id`),
  CONSTRAINT `security_deposits_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `security_deposit_transactions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `security_deposit_transactions`;
CREATE TABLE `security_deposit_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `security_deposit_id` int(11) NOT NULL,
  `transaction_type` enum('ADJUSTMENT','REFUND','FORFEIT','HOLD') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `created_by_admin` int(11) DEFAULT NULL,
  `processed_by_name` varchar(100) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `security_deposit_id` (`security_deposit_id`),
  KEY `created_by_admin` (`created_by_admin`),
  CONSTRAINT `security_deposit_transactions_ibfk_1` FOREIGN KEY (`security_deposit_id`) REFERENCES `security_deposits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `security_deposit_transactions_ibfk_2` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `additional_charges`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `additional_charges`;
CREATE TABLE `additional_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `charge_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `charge_date` date NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `created_by_admin` int(11) DEFAULT NULL,
  `status` enum('Pending','Paid','Partial','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `created_by_admin` (`created_by_admin`),
  CONSTRAINT `additional_charges_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `additional_charges_ibfk_2` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `discounts`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `discounts`;
CREATE TABLE `discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `discount_type` enum('PERCENTAGE','FIXED') NOT NULL,
  `discount_value` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `created_by_admin` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `created_by_admin` (`created_by_admin`),
  CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `fee_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discounts_ibfk_2` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `late_fees`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `late_fees`;
CREATE TABLE `late_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `grace_period_days` int(11) NOT NULL DEFAULT 0,
  `fee_type` enum('FIXED','PERCENTAGE') NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `late_fees_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `fee_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `student_history`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `student_history`;
CREATE TABLE `student_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `performed_by_admin` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `performed_by_admin` (`performed_by_admin`),
  CONSTRAINT `student_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_history_ibfk_2` FOREIGN KEY (`performed_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `alumni`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `alumni`;
CREATE TABLE `alumni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_student_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `cnic` varchar(15) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `guardian_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`guardian_info`)),
  `previous_room` varchar(50) DEFAULT NULL,
  `previous_bed` int(11) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `leaving_date` date DEFAULT NULL,
  `leaving_reason` text DEFAULT NULL,
  `final_fee_status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `checked_out_by_name` varchar(100) DEFAULT NULL,
  `checked_out_at` timestamp NULL DEFAULT NULL,
  `processed_by_name` varchar(100) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `system_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Default Admin Account
-- --------------------------------------------------------
INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'KingsHostel', 'admin@hostel.local', '$2y$10$PYE591EmpePNdISWozn4ZOOKlM7aF2MSklX1MAwCg9YaxtA05IlFC', NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
