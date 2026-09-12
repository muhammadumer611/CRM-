-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hms_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `_test_virtual_idx`
--

DROP TABLE IF EXISTS `_test_virtual_idx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_test_virtual_idx` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `bed_number` int(11) NOT NULL,
  `status` enum('Active','Closed') DEFAULT 'Active',
  `active_bed_flag` varchar(64) GENERATED ALWAYS AS (if(`status` = 'Active',concat(`room_id`,'_',`bed_number`),NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_test_active_bed` (`active_bed_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_test_virtual_idx`
--

LOCK TABLES `_test_virtual_idx` WRITE;
/*!40000 ALTER TABLE `_test_virtual_idx` DISABLE KEYS */;
/*!40000 ALTER TABLE `_test_virtual_idx` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `additional_charges`
--

DROP TABLE IF EXISTS `additional_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `additional_charges`
--

LOCK TABLES `additional_charges` WRITE;
/*!40000 ALTER TABLE `additional_charges` DISABLE KEYS */;
/*!40000 ALTER TABLE `additional_charges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'KingsHostel','admin@hostel.local','$2y$10$zXCJGUb4MB/VtT.FhunWw.H5bnw2icR6oSg5alyxCJpY0GOU7QOSi','2026-08-29 17:00:32','2026-09-11 16:04:31');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alumni`
--

DROP TABLE IF EXISTS `alumni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alumni`
--

LOCK TABLES `alumni` WRITE;
/*!40000 ALTER TABLE `alumni` DISABLE KEYS */;
INSERT INTO `alumni` VALUES (1,'STU-0002','Muhammad Ali Khan','3420330773624','03450650755','{\"name\":\"Fateh Muhammad\",\"phone\":\"03466462014\",\"cnic\":\"7645329778613\",\"relation\":\"Father\"}','General-129',3,'2026-09-11','2026-09-11','Course Completed','Cleared','','tanveer','2026-09-11 09:30:29','tanveer','2026-09-11 09:30:29','2026-09-11 09:30:29');
/*!40000 ALTER TABLE `alumni` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discounts`
--

DROP TABLE IF EXISTS `discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discounts`
--

LOCK TABLES `discounts` WRITE;
/*!40000 ALTER TABLE `discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_payments`
--

DROP TABLE IF EXISTS `fee_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_payments`
--

LOCK TABLES `fee_payments` WRITE;
/*!40000 ALTER TABLE `fee_payments` DISABLE KEYS */;
INSERT INTO `fee_payments` VALUES (1,1,'RCP-EB841229',8000.00,'2026-09-11','Cash',NULL,NULL,1,NULL,NULL,'Completed',NULL,NULL,NULL,'2026-09-11 07:33:12','2026-09-11 07:33:12'),(2,3,'RCP-8AFCA920',8000.00,'2026-09-11','Cash',NULL,NULL,1,'amir','2026-09-11 09:23:59','Completed',NULL,NULL,NULL,'2026-09-11 09:23:59','2026-09-11 09:23:59'),(3,11,'RCP-EDE1B646',8000.00,'2026-09-11','Cash',NULL,NULL,1,'Naveed','2026-09-11 17:48:14','Completed',NULL,NULL,NULL,'2026-09-11 17:48:14','2026-09-11 17:48:14');
/*!40000 ALTER TABLE `fee_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_records`
--

DROP TABLE IF EXISTS `fee_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_records`
--

LOCK TABLES `fee_records` WRITE;
/*!40000 ALTER TABLE `fee_records` DISABLE KEYS */;
INSERT INTO `fee_records` VALUES (1,'INV-6AA3AEAB1DF97',1,9,2026,'2026-09-11',8000.00,0.00,0.00,8000.00,'2026-09-18','2026-09-11','Paid','MONTHLY_FEE','Cash',NULL,NULL,'2026-09-11 07:32:59','2026-09-11 07:33:12'),(2,'INV-78AC9E7B',1,10,2026,'2026-09-11',8000.00,0.00,0.00,0.00,'2026-10-10',NULL,'Pending','MONTHLY_FEE',NULL,NULL,NULL,'2026-09-11 09:19:06','2026-09-11 09:19:06'),(3,'INV-6AA3C8864563C',2,9,2026,'2026-09-11',8000.00,0.00,0.00,8000.00,'2026-09-18','2026-09-11','Paid','MONTHLY_FEE','Cash',NULL,NULL,'2026-09-11 09:23:18','2026-09-11 09:23:59'),(11,'INV-6AA43E8F416AF',18,9,2026,'2026-09-11',8000.00,0.00,0.00,8000.00,'2026-09-18','2026-09-11','Paid','MONTHLY_FEE','Cash',NULL,NULL,'2026-09-11 17:46:55','2026-09-11 17:48:14');
/*!40000 ALTER TABLE `fee_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `late_fees`
--

DROP TABLE IF EXISTS `late_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `late_fees`
--

LOCK TABLES `late_fees` WRITE;
/*!40000 ALTER TABLE `late_fees` DISABLE KEYS */;
/*!40000 ALTER TABLE `late_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'system',
  `priority` varchar(20) NOT NULL DEFAULT 'medium',
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `notification_key` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notifications_key` (`notification_key`),
  KEY `idx_notifications_is_read` (`is_read`),
  KEY `idx_notifications_type` (`type`),
  KEY `idx_notifications_priority` (`priority`),
  KEY `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'Student Inactive','Muhammad Ali Khan is currently marked inactive and may require review.','student','medium','student',2,'student_inactive_2',1,'2026-09-11 09:34:13','2026-09-11 09:35:02'),(2,'Low Bed Availability','Room 130 has only 1 bed(s) remaining.','room','medium','room',2,'room_low_availability_2',1,'2026-09-11 16:02:46','2026-09-11 16:03:08'),(3,'Monthly Fee Pending','Monthly fee for Haider Ali (STU-0003) is pending for September 2026. [Room 129 (Bed 2)] Total Fee: Rs. 8,000, Paid: Rs. 0, Outstanding: Rs. 8,000.','fee','medium','fee_pending',18,'monthly_fee_pending_student_18_2026_9',1,'2026-09-11 17:47:06','2026-09-11 17:49:08');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservation_payments`
--

DROP TABLE IF EXISTS `reservation_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservation_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Online','Card','Other') NOT NULL DEFAULT 'Cash',
  `transaction_ref` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by_admin` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reservation_payment_ref` (`transaction_ref`),
  KEY `idx_reservation_payments_reservation` (`reservation_id`,`payment_date`),
  KEY `created_by_admin` (`created_by_admin`),
  CONSTRAINT `reservation_payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_payments_ibfk_2` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservation_payments`
--

LOCK TABLES `reservation_payments` WRITE;
/*!40000 ALTER TABLE `reservation_payments` DISABLE KEYS */;
INSERT INTO `reservation_payments` VALUES (1,1,2000.00,'2026-09-11','Cash','RES-1-20260911093530','Reservation booking payment',1,'2026-09-11 07:35:30','2026-09-11 07:35:30');
/*!40000 ALTER TABLE `reservation_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,'Haider Ali','2342930773246','03236323842','Mandi Bhauddin',1,2,2000.00,'2026-09-11','2026-09-18','ARRIVED','Reserve student',NULL,18,'2026-09-11 17:46:55','Naveed',NULL,NULL,'2026-09-11 07:35:30','2026-09-11 17:46:55');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_allocations`
--

DROP TABLE IF EXISTS `room_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_allocations`
--

LOCK TABLES `room_allocations` WRITE;
/*!40000 ALTER TABLE `room_allocations` DISABLE KEYS */;
INSERT INTO `room_allocations` VALUES (1,1,1,1,'2026-09-11',NULL,'Active',NULL,NULL,'2026-09-11 07:32:59','2026-09-11 07:32:59','1_1','1'),(2,2,1,3,'2026-09-11','2026-09-11','Closed',NULL,NULL,'2026-09-11 09:23:18','2026-09-11 09:30:29',NULL,NULL),(18,18,1,2,'2026-09-11',NULL,'Active',NULL,NULL,'2026-09-11 17:46:55','2026-09-11 17:46:55','1_2','18');
/*!40000 ALTER TABLE `room_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_occupants`
--

DROP TABLE IF EXISTS `room_occupants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_occupants`
--

LOCK TABLES `room_occupants` WRITE;
/*!40000 ALTER TABLE `room_occupants` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_occupants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'129','General','1st Floor','Four Bed',4,2,0.00,0.00,'Partially Occupied','2026-09-11 07:27:14','2026-09-11 17:46:55'),(2,'130','General','1st Floor','Double',2,0,0.00,0.00,'Available','2026-09-11 07:28:15','2026-09-11 16:02:46'),(3,'131','General','1st Floor','Triple',3,0,0.00,0.00,'Available','2026-09-11 07:29:05','2026-09-11 17:28:11');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_deposit_transactions`
--

DROP TABLE IF EXISTS `security_deposit_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_deposit_transactions`
--

LOCK TABLES `security_deposit_transactions` WRITE;
/*!40000 ALTER TABLE `security_deposit_transactions` DISABLE KEYS */;
INSERT INTO `security_deposit_transactions` VALUES (1,2,'ADJUSTMENT',2000.00,'Deduction upon checkout',NULL,1,'tanveer','2026-09-11 09:30:29','2026-09-11 09:30:29');
/*!40000 ALTER TABLE `security_deposit_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_deposits`
--

DROP TABLE IF EXISTS `security_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_deposits`
--

LOCK TABLES `security_deposits` WRITE;
/*!40000 ALTER TABLE `security_deposits` DISABLE KEYS */;
INSERT INTO `security_deposits` VALUES (1,1,2000.00,2000.00,'HELD',NULL,NULL,'2026-09-11 07:32:59','2026-09-11 07:32:59'),(2,2,2000.00,0.00,'FORFEITED','tanveer','2026-09-11 09:30:29','2026-09-11 09:23:18','2026-09-11 09:30:29'),(3,18,2000.00,2000.00,'HELD',NULL,NULL,'2026-09-11 17:46:55','2026-09-11 17:46:55');
/*!40000 ALTER TABLE `security_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_credits`
--

DROP TABLE IF EXISTS `student_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_credits`
--

LOCK TABLES `student_credits` WRITE;
/*!40000 ALTER TABLE `student_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_history`
--

DROP TABLE IF EXISTS `student_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_history`
--

LOCK TABLES `student_history` WRITE;
/*!40000 ALTER TABLE `student_history` DISABLE KEYS */;
INSERT INTO `student_history` VALUES (1,1,'STUDENT_CREATED','Single-person onboarding completed.',NULL,'{\"student_id_str\":\"STU-0001\",\"full_name\":\"Zain Abdullah\",\"cnic\":\"2342930773245\",\"phone\":\"03450650761\",\"email\":\"m.umerjanii007@gmail.com\",\"blood_group\":\"A+\",\"address\":\"Cannal Park Road\",\"guardian_name\":\"Saif Ullah\",\"guardian_phone\":\"03466462010\",\"guardian_cnic\":\"9876543872347\",\"guardian_address\":\"Cannal Park Road\",\"relation\":\"Father\",\"status\":\"Active\",\"monthly_fee\":8000,\"allocation_id\":\"1\",\"invoice_id\":\"1\"}',1,'2026-09-11 07:32:59'),(2,2,'STUDENT_CREATED','Single-person onboarding completed.',NULL,'{\"student_id_str\":\"STU-0002\",\"full_name\":\"Muhammad Ali Khan\",\"cnic\":\"3420330773624\",\"phone\":\"03450650755\",\"email\":\"ali@gmail.com\",\"blood_group\":\"A+\",\"address\":\"Khushaib\",\"guardian_name\":\"Fateh Muhammad\",\"guardian_phone\":\"03466462014\",\"guardian_cnic\":\"7645329778613\",\"guardian_address\":\"Cannal Park Road\",\"relation\":\"Father\",\"status\":\"Active\",\"monthly_fee\":8000,\"added_by_name\":\"Amir\",\"added_at\":\"2026-09-11 11:23:18\",\"allocation_id\":\"2\",\"invoice_id\":\"3\"}',1,'2026-09-11 09:23:18'),(3,2,'STUDENT_MARKED_ALUMNI','Student STU-0002 was marked as alumni. Reason: Course Completed. Security Deposit Refund: Rs. 0.00 (Deducted: Rs. 2,000.00)','{\"status\":\"Active\",\"room_allocation\":{\"room\":\"General-129\",\"bed\":3},\"security_deposit\":{\"status\":\"HELD\",\"remaining\":\"2000.00\"}}','{\"status\":\"Inactive\",\"alumni_id\":\"1\",\"leaving_date\":\"2026-09-11\",\"security_settlement\":{\"original_amount\":2000,\"deducted\":2000,\"refunded\":0,\"status\":\"FORFEITED\",\"remarks\":\"\"}}',1,'2026-09-11 09:30:29'),(16,18,'STUDENT_CREATED','Single-person onboarding completed.',NULL,'{\"student_id_str\":\"STU-0003\",\"full_name\":\"Haider Ali\",\"cnic\":\"2342930773246\",\"phone\":\"03236323842\",\"email\":null,\"blood_group\":null,\"address\":\"P\\/o Rerka Bala District Mandi Bhauddin\",\"guardian_name\":\"Ali Hassan\",\"guardian_phone\":\"03450650767\",\"guardian_cnic\":\"7687654334567\",\"guardian_address\":null,\"relation\":\"Father\",\"status\":\"Active\",\"monthly_fee\":8000,\"added_by_name\":\"Naveed\",\"added_at\":\"2026-09-11 19:46:55\",\"allocation_id\":\"18\",\"invoice_id\":\"11\"}',1,'2026-09-11 17:46:55');
/*!40000 ALTER TABLE `student_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `guardian_cnic` varchar(15) NOT NULL,
  `relation` varchar(50) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'STU-0001','Zain Abdullah','2342930773245','03450650761','m.umerjanii007@gmail.com','A+','Cannal Park Road',NULL,NULL,NULL,'Saif Ullah','03466462010','9876543872347','Father','Cannal Park Road','Active',8000.00,NULL,NULL,'2026-09-11 07:32:59','2026-09-11 07:32:59'),(2,'STU-0002','Muhammad Ali Khan','3420330773624','03450650755','ali@gmail.com','A+','Khushaib',NULL,NULL,NULL,'Fateh Muhammad','03466462014','7645329778613','Father','Cannal Park Road','Inactive',8000.00,'Amir','2026-09-11 06:23:18','2026-09-11 09:23:18','2026-09-11 09:30:29'),(18,'STU-0003','Haider Ali','2342930773246','03236323842',NULL,NULL,'P/o Rerka Bala District Mandi Bhauddin',NULL,NULL,NULL,'Ali Hassan','03450650767','7687654334567','Father',NULL,'Active',8000.00,'Naveed','2026-09-11 14:46:55','2026-09-11 17:46:55','2026-09-11 17:46:55');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,1,'Create Room',NULL,NULL,'Created room 129',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 07:27:14'),(2,1,'Create Room',NULL,NULL,'Created room 130',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 07:28:15'),(3,1,'Create Room',NULL,NULL,'Created room 131',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 07:29:05'),(4,1,'Fee Payment',NULL,NULL,'Rs. 8000 for invoice #1. Receipt: RCP-EB841229',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 07:33:12'),(5,1,'Failed Login',NULL,NULL,'Failed login attempt',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 09:12:00'),(6,1,'Failed Login',NULL,NULL,'Failed login attempt',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 09:12:16'),(7,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 09:13:11'),(8,1,'Fee Payment',NULL,NULL,'Rs. 8000 for invoice #3. Receipt: RCP-8AFCA920',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 09:23:59'),(9,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 09:34:13'),(10,1,'Admin Settings',NULL,NULL,'Username changed to admin_verify_1789142322',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 15:58:42'),(11,1,'Admin Settings',NULL,NULL,'Password changed successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 15:58:42'),(12,1,'Admin Settings',NULL,NULL,'Username changed to admin_verify_1789142357',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 15:59:17'),(13,1,'Admin Settings',NULL,NULL,'Password changed successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 15:59:17'),(14,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 16:02:46'),(15,1,'Admin Settings',NULL,NULL,'Username changed to KingsHostel',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 16:04:02'),(16,1,'Admin Settings',NULL,NULL,'Password changed successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 16:04:31'),(17,1,'Logout',NULL,NULL,'Admin logged out',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 16:04:35'),(18,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 16:04:56'),(19,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 16:45:49'),(20,1,'Logout',NULL,NULL,'Admin logged out',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 17:14:13'),(21,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 17:14:24'),(22,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 17:22:20'),(23,1,'Fee Payment',NULL,NULL,'Rs. 8000 for invoice #11. Receipt: RCP-EDE1B646',NULL,NULL,'127.0.0.1',NULL,'2026-09-11 17:48:14');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'hms_db'
--

--
-- Dumping routines for database 'hms_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-12 10:04:42
