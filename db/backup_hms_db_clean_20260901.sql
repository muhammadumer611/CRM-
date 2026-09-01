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
-- Current Database: `hms_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `hms_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `hms_db`;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_test_virtual_idx`
--

LOCK TABLES `_test_virtual_idx` WRITE;
/*!40000 ALTER TABLE `_test_virtual_idx` DISABLE KEYS */;
INSERT INTO `_test_virtual_idx` VALUES (1,1,1,'Active','1_1');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
INSERT INTO `admins` VALUES (1,'admin','admin@hostel.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2026-08-29 17:00:32','2026-08-29 17:00:32');
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alumni`
--

LOCK TABLES `alumni` WRITE;
/*!40000 ALTER TABLE `alumni` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_payments`
--

LOCK TABLES `fee_payments` WRITE;
/*!40000 ALTER TABLE `fee_payments` DISABLE KEYS */;
INSERT INTO `fee_payments` VALUES (10,44,'PAY-2026-703223',6000.00,'2026-08-30','Cash',NULL,'',1,'Completed',NULL,NULL,NULL,'2026-08-30 11:33:12','2026-08-30 11:33:12'),(11,44,'PAY-2026-388613',2000.00,'2026-08-30','Cash',NULL,'',1,'Completed',NULL,NULL,NULL,'2026-08-30 11:34:00','2026-08-30 11:34:00'),(38,49,'PAY-2026-955261',15000.00,'2026-08-30','Cash',NULL,'',1,'Completed',NULL,NULL,NULL,'2026-08-30 18:34:50','2026-08-30 18:34:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_records`
--

LOCK TABLES `fee_records` WRITE;
/*!40000 ALTER TABLE `fee_records` DISABLE KEYS */;
INSERT INTO `fee_records` VALUES (44,'INV-2026-231724',100,8,2026,'2026-08-30',8000.00,2000.00,0.00,8000.00,'2026-09-14','2026-08-30','Partial','MONTHLY_FEE','Cash',NULL,'Admission setup invoice; security deposit included as additional charge.','2026-08-30 11:32:23','2026-08-30 11:34:00'),(49,'INV-2026-942842',113,9,2026,'2026-08-30',15000.00,20000.00,0.00,15000.00,'2026-09-05','2026-08-30','Partial','MONTHLY_FEE','Cash',NULL,'Admission setup invoice; security deposit included as additional charge.','2026-08-30 17:38:47','2026-08-30 18:34:50'),(65,'INV-2026-860910',141,8,2026,'2026-08-30',15000.00,0.00,0.00,0.00,'2026-09-05',NULL,'Pending','MONTHLY_FEE',NULL,NULL,'Admission setup invoice; security deposit included as additional charge.','2026-08-30 18:00:47','2026-08-30 18:00:47');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'Low Bed Availability','Room 130 has only 1 bed(s) remaining.','room','medium','room',1,'room_low_availability_1',1,'2026-08-29 17:37:57','2026-08-29 17:38:23'),(2,'Low Bed Availability','Room TEST-RM-001 has only 1 bed(s) remaining.','room','medium','room',3,'room_low_availability_3',0,'2026-08-30 08:04:22',NULL),(3,'Student Without Allocation','Full Room A does not currently have an active room allocation.','student','medium','student',47,'student_without_allocation_47',0,'2026-08-30 10:21:43',NULL),(4,'Student Without Allocation','Concurrency A does not currently have an active room allocation.','student','medium','student',49,'student_without_allocation_49',0,'2026-08-30 10:21:43',NULL),(5,'Fee Pending','Rao Saif Ullah\'s monthly fee for August 2026 is still pending.','fee','medium','fee',44,'fee_pending_student_100_2026_8',0,'2026-08-30 11:33:16',NULL),(6,'Fee Pending','Security Deposit Validation\'s monthly fee for September 2026 is still pending.','fee','medium','fee',49,'fee_pending_student_113_2026_9',0,'2026-08-30 18:33:58',NULL),(7,'Fee Pending','Full Room Primary\'s monthly fee for August 2026 is still pending.','fee','medium','fee',65,'fee_pending_student_141_2026_8',0,'2026-08-30 18:33:58',NULL),(8,'Low Bed Availability','Room SECDEP-2026083019384 has only 1 bed(s) remaining.','room','medium','room',112,'room_low_availability_112',0,'2026-08-30 18:33:58',NULL),(9,'Room Full','Room FULLROOM-TEST-178811 has reached full capacity.','room','high','room',140,'room_full_140',0,'2026-08-30 18:33:58',NULL),(10,'Low Bed Availability','Room FULLROOM-TEST-178811 has only 0 bed(s) remaining.','room','medium','room',140,'room_low_availability_140',0,'2026-08-30 18:33:58',NULL),(11,'Student Without Allocation','Security Deposit Validation does not currently have an active room allocation.','student','medium','student',113,'student_without_allocation_113',0,'2026-08-30 18:33:58',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES (4,10,44,6000.00,'2026-08-30 11:33:12'),(5,11,44,2000.00,'2026-08-30 11:34:00'),(15,38,49,15000.00,'2026-08-30 18:34:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_allocations`
--

LOCK TABLES `room_allocations` WRITE;
/*!40000 ALTER TABLE `room_allocations` DISABLE KEYS */;
INSERT INTO `room_allocations` VALUES (62,100,1,1,'2026-08-30',NULL,'Active','Good','2026-08-30 11:32:23','2026-08-30 11:32:23','1_1','100'),(88,141,140,1,'2026-08-30',NULL,'Active','FULL_ROOM allocation','2026-08-30 18:00:47','2026-08-31 15:48:48','140_1','141');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_occupants`
--

LOCK TABLES `room_occupants` WRITE;
/*!40000 ALTER TABLE `room_occupants` DISABLE KEYS */;
INSERT INTO `room_occupants` VALUES (1,88,'Occupant One','0069005161011','03000000003','Brother','2026-08-30 18:00:47','2026-08-30 18:00:47'),(2,88,'Occupant Two','0085809491012','03000000004','Friend','2026-08-30 18:00:47','2026-08-30 18:00:47');
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
) ENGINE=InnoDB AUTO_INCREMENT=181 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'130','Block A','1st Floor','Double',2,1,8000.00,4000.00,'Partially Occupied','2026-08-29 17:32:41','2026-08-29 17:36:47'),(2,'129','Block A','1st Floor','Dormitory',4,0,10000.00,10000.00,'Available','2026-08-29 17:34:29','2026-08-31 16:05:05'),(112,'SECDEP-2026083019384','A','1','Shared',2,0,15000.00,20000.00,'Available','2026-08-30 17:38:47','2026-08-31 16:05:05'),(114,'PENDM-20260830194937','A','1','Shared',2,0,15000.00,20000.00,'Available','2026-08-30 17:49:37','2026-08-30 17:49:37'),(128,'PEND-20260830195217-','A','1','Shared',2,0,15000.00,20000.00,'Available','2026-08-30 17:52:17','2026-08-30 17:52:17'),(140,'FULLROOM-TEST-178811','B','1','Shared',4,1,15000.00,20000.00,'Partially Occupied','2026-08-30 18:00:47','2026-08-31 16:05:05');
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `security_deposit_id` (`security_deposit_id`),
  KEY `created_by_admin` (`created_by_admin`),
  CONSTRAINT `security_deposit_transactions_ibfk_1` FOREIGN KEY (`security_deposit_id`) REFERENCES `security_deposits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `security_deposit_transactions_ibfk_2` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_deposit_transactions`
--

LOCK TABLES `security_deposit_transactions` WRITE;
/*!40000 ALTER TABLE `security_deposit_transactions` DISABLE KEYS */;
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_security_deposits_student` (`student_id`),
  CONSTRAINT `security_deposits_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_deposits`
--

LOCK TABLES `security_deposits` WRITE;
/*!40000 ALTER TABLE `security_deposits` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_history`
--

LOCK TABLES `student_history` WRITE;
/*!40000 ALTER TABLE `student_history` DISABLE KEYS */;
INSERT INTO `student_history` VALUES (1,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0001\",\"full_name\":\"Muhammad Umer\",\"cnic\":\"2342930773245\",\"phone\":\"03450650761\",\"email\":\"muhammadumer.dev.ai@gmail.com\",\"blood_group\":\"B+\",\"address\":\"Rerka Bala district Mandi Bhauddin Thesil Phalia\",\"guardian_name\":\"Saif Ullah\",\"guardian_phone\":\"03466462010\",\"guardian_cnic\":\"3249875644239\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-29 17:36:23'),(2,NULL,'ROOM_ALLOCATED','Allocated to Room Block A-130, Bed 1.',NULL,'{\"room\":\"Block A-130\",\"bed\":1,\"joining_date\":\"2026-08-29\"}',1,'2026-08-29 17:36:47'),(3,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0002\",\"full_name\":\"Haider Ali\",\"cnic\":\"8976543229873\",\"phone\":\"03236323842\",\"email\":\"haiderali@gamil.com\",\"blood_group\":\"B+\",\"address\":\"Bhalwal chack no 28\",\"guardian_name\":\"Fateh Muhammad\",\"guardian_phone\":\"03247657543\",\"guardian_cnic\":\"98765438723478\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-29 17:48:42'),(4,NULL,'ROOM_ALLOCATED','Allocated to Room Block A-129, Bed 4.',NULL,'{\"room\":\"Block A-129\",\"bed\":4,\"joining_date\":\"2026-08-29\"}',1,'2026-08-29 17:49:37'),(16,47,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-1218 as FULL_ROOM.',NULL,'{\"room_id\":53,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:18:49'),(17,47,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":53,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 10:18:49'),(18,47,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-1218 Bed 0','{\"room_id\":53,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 10:18:49'),(19,49,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-121849, bed 1.',NULL,'{\"room_id\":54,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:18:49'),(20,49,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":54,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 10:18:49'),(21,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-1219 as FULL_ROOM.',NULL,'{\"room_id\":63,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:19:08'),(22,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":63,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 10:19:08'),(23,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-1219 Bed 0','{\"room_id\":63,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 10:19:08'),(24,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-121908, bed 1.',NULL,'{\"room_id\":64,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:19:08'),(25,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":64,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 10:19:08'),(26,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830123, bed 1.',NULL,'{\"room_id\":66,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:30:27'),(27,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":66,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 10:30:27'),(28,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-1239 as FULL_ROOM.',NULL,'{\"room_id\":75,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:39:05'),(29,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":75,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 10:39:05'),(30,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-1239 Bed 0','{\"room_id\":75,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 10:39:05'),(31,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-123905, bed 1.',NULL,'{\"room_id\":76,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:39:05'),(32,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":76,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 10:39:05'),(33,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830123, bed 1.',NULL,'{\"room_id\":77,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:39:06'),(34,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":77,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 10:39:06'),(35,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-1239 as FULL_ROOM.',NULL,'{\"room_id\":86,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:39:56'),(36,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":86,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 10:39:56'),(37,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-1239 Bed 0','{\"room_id\":86,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 10:39:56'),(38,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-123956, bed 1.',NULL,'{\"room_id\":87,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:39:56'),(39,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":87,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 10:39:56'),(40,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830123, bed 1.',NULL,'{\"room_id\":88,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 10:39:56'),(41,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":88,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 10:39:56'),(42,NULL,'ROOM_ALLOCATED','Allocated to room A - PH1-ROOM-20260830132, bed 1.',NULL,'{\"room_id\":89,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 11:23:33'),(43,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":89,\"bed_number\":1,\"monthly_fee\":20000,\"security_deposit\":25000,\"initial_payment\":0}',1,'2026-08-30 11:23:33'),(44,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-1323 as FULL_ROOM.',NULL,'{\"room_id\":98,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 11:23:33'),(45,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":98,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 11:23:33'),(46,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-1323 Bed 0','{\"room_id\":98,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 11:23:33'),(47,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-132333, bed 1.',NULL,'{\"room_id\":99,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 11:23:33'),(48,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":99,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 11:23:33'),(49,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830132, bed 1.',NULL,'{\"room_id\":100,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 11:23:34'),(50,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":100,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 11:23:34'),(51,100,'ROOM_ALLOCATED','Allocated to room Block A - 130, bed 1.',NULL,'{\"room_id\":1,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 11:32:23'),(52,100,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":1,\"bed_number\":1,\"monthly_fee\":8000,\"security_deposit\":2000,\"initial_payment\":0}',1,'2026-08-30 11:32:23'),(53,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-1935 as FULL_ROOM.',NULL,'{\"room_id\":109,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:35:37'),(54,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":109,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 17:35:37'),(55,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-1935 Bed 0','{\"room_id\":109,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 17:35:37'),(56,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-193537, bed 1.',NULL,'{\"room_id\":110,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:35:37'),(57,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":110,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 17:35:37'),(58,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830193, bed 1.',NULL,'{\"room_id\":111,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:35:38'),(59,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":111,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 17:35:38'),(60,113,'ROOM_ALLOCATED','Allocated to room A - SECDEP-2026083019384, bed 1.',NULL,'{\"room_id\":112,\"bed_number\":1,\"joining_date\":\"2026-09-01\"}',NULL,'2026-08-30 17:38:47'),(61,113,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":112,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":0}',NULL,'2026-08-30 17:38:47'),(62,NULL,'ROOM_ALLOCATED','Allocated to room A - PENDING-202608301948, bed 1.',NULL,'{\"room_id\":113,\"bed_number\":1,\"joining_date\":\"2026-09-01\"}',NULL,'2026-08-30 17:48:00'),(63,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":113,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":0}',NULL,'2026-08-30 17:48:00'),(64,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830195, bed 1.',NULL,'{\"room_id\":116,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:52:17'),(65,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":116,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 17:52:17'),(66,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-1952 as FULL_ROOM.',NULL,'{\"room_id\":125,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:52:17'),(67,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":125,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 17:52:17'),(68,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-1952 Bed 0','{\"room_id\":125,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 17:52:17'),(69,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-195217, bed 1.',NULL,'{\"room_id\":126,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:52:17'),(70,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":126,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 17:52:17'),(71,NULL,'ROOM_ALLOCATED','Allocated to room A - PH1-ROOM-20260830195, bed 1.',NULL,'{\"room_id\":127,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:52:17'),(72,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":127,\"bed_number\":1,\"monthly_fee\":20000,\"security_deposit\":25000,\"initial_payment\":0}',1,'2026-08-30 17:52:17'),(73,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830195, bed 1.',NULL,'{\"room_id\":129,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:52:58'),(74,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":129,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 17:52:58'),(75,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-1952 as FULL_ROOM.',NULL,'{\"room_id\":138,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:52:59'),(76,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":138,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 17:52:59'),(77,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-1952 Bed 0','{\"room_id\":138,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 17:52:59'),(78,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-195259, bed 1.',NULL,'{\"room_id\":139,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 17:52:59'),(79,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":139,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 17:52:59'),(80,141,'ROOM_ALLOCATED','Allocated to room B - FULLROOM-TEST-178811 as FULL_ROOM.',NULL,'{\"room_id\":140,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:00:47'),(81,141,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":140,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 18:00:47'),(83,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-200247, bed 1.',NULL,'{\"room_id\":150,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:02:47'),(84,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":150,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 18:02:47'),(85,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830200, bed 1.',NULL,'{\"room_id\":151,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:02:48'),(86,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":151,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 18:02:48'),(87,NULL,'ROOM_ALLOCATED','Allocated to room A - PH1-ROOM-20260830200, bed 1.',NULL,'{\"room_id\":152,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:02:48'),(88,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":152,\"bed_number\":1,\"monthly_fee\":20000,\"security_deposit\":25000,\"initial_payment\":0}',1,'2026-08-30 18:02:48'),(89,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-2011 as FULL_ROOM.',NULL,'{\"room_id\":161,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:11:48'),(90,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":161,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 18:11:48'),(91,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-2011 Bed 0','{\"room_id\":161,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 18:11:48'),(92,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-201148, bed 1.',NULL,'{\"room_id\":162,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:11:48'),(93,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":162,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 18:11:48'),(95,NULL,'ROOM_ALLOCATED','Allocated to room F - AVAILV-FULLLOCK-2012 as FULL_ROOM.',NULL,'{\"room_id\":172,\"allocation_type\":\"FULL_ROOM\",\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:12:09'),(96,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":172,\"bed_number\":0,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 18:12:09'),(97,NULL,'ROOM_ALLOCATION_CLOSED','Allocation closed in Room AVAILV-FULLLOCK-2012 Bed 0','{\"room_id\":172,\"bed_number\":0,\"status\":\"Active\"}','{\"leaving_date\":\"2026-08-31\",\"status\":\"Closed\"}',1,'2026-08-30 18:12:09'),(98,NULL,'ROOM_ALLOCATED','Allocated to room G - AVAILV-CONCUR-201209, bed 1.',NULL,'{\"room_id\":173,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:12:09'),(99,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":173,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":0,\"initial_payment\":0}',1,'2026-08-30 18:12:09'),(100,NULL,'ROOM_ALLOCATED','Allocated to room A - ACC-ROOM-20260830202, bed 1.',NULL,'{\"room_id\":174,\"bed_number\":1,\"joining_date\":\"2026-08-30\"}',1,'2026-08-30 18:27:53'),(101,NULL,'ADMISSION_COMPLETED','Admission completed and initial setup created.',NULL,'{\"room_allocation_enabled\":true,\"room_id\":174,\"bed_number\":1,\"monthly_fee\":15000,\"security_deposit\":20000,\"initial_payment\":7000}',1,'2026-08-30 18:27:53'),(102,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0142\",\"full_name\":\"Test Student One\",\"cnic\":\"9999911111111\",\"phone\":\"03001111111\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 1\",\"guardian_phone\":\"03001111112\",\"guardian_cnic\":\"9999911111112\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-31 16:01:53'),(103,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0193\",\"full_name\":\"Test Student Two\",\"cnic\":\"9999922222222\",\"phone\":\"03002222222\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 2\",\"guardian_phone\":\"03002222223\",\"guardian_cnic\":\"9999922222223\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-31 16:01:53'),(104,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0194\",\"full_name\":\"Test Student Three\",\"cnic\":\"9999933333333\",\"phone\":\"03003333333\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 3\",\"guardian_phone\":\"03003333334\",\"guardian_cnic\":\"9999933333334\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-31 16:01:53'),(105,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0195\",\"full_name\":\"Test Student Four\",\"cnic\":\"9999944444444\",\"phone\":\"03004444444\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 4\",\"guardian_phone\":\"03004444445\",\"guardian_cnic\":\"9999944444445\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-31 16:01:53'),(106,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0142\",\"full_name\":\"Test Student One\",\"cnic\":\"9999911111111\",\"phone\":\"03001111111\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 1\",\"guardian_phone\":\"03001111112\",\"guardian_cnic\":\"9999911111112\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-31 16:03:29'),(107,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0197\",\"full_name\":\"Test Student Two\",\"cnic\":\"9999922222222\",\"phone\":\"03002222222\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 2\",\"guardian_phone\":\"03002222223\",\"guardian_cnic\":\"9999922222223\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-31 16:03:29'),(108,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0198\",\"full_name\":\"Test Student Three\",\"cnic\":\"9999933333333\",\"phone\":\"03003333333\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 3\",\"guardian_phone\":\"03003333334\",\"guardian_cnic\":\"9999933333334\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-31 16:03:29'),(109,NULL,'STUDENT_CREATED','Student profile created.',NULL,'{\"student_id_str\":\"STU-0199\",\"full_name\":\"Test Student Four\",\"cnic\":\"9999944444444\",\"phone\":\"03004444444\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 4\",\"guardian_phone\":\"03004444445\",\"guardian_cnic\":\"9999944444445\",\"relation\":\"Father\",\"status\":\"Active\"}',1,'2026-08-31 16:03:29'),(110,NULL,'ROOM_ALLOCATED','Allocated to Room Wing Alpha-TEST-101, Bed 2.',NULL,'{\"room\":\"Wing Alpha-TEST-101\",\"bed\":2,\"joining_date\":\"2026-08-31\"}',1,'2026-08-31 16:03:29'),(111,NULL,'ROOM_ALLOCATED','Allocated to Room Wing Alpha-TEST-101, Bed 4.',NULL,'{\"room\":\"Wing Alpha-TEST-101\",\"bed\":4,\"joining_date\":\"2026-08-31\"}',1,'2026-08-31 16:03:29'),(112,NULL,'ROOM_DEALLOCATED','Deallocated from Room Wing Alpha-TEST-101, Bed 2.','{\"room\":\"Wing Alpha-TEST-101\",\"bed\":2}','{\"leaving_date\":\"2026-08-31\"}',1,'2026-08-31 16:03:29'),(113,NULL,'ROOM_ALLOCATED','Allocated to Room Wing Alpha-TEST-101, Bed 1.',NULL,'{\"room\":\"Wing Alpha-TEST-101\",\"bed\":1,\"joining_date\":\"2026-08-31\"}',1,'2026-08-31 16:03:29'),(114,NULL,'ROOM_ALLOCATED','Allocated to Room Wing Alpha-TEST-101, Bed 3.',NULL,'{\"room\":\"Wing Alpha-TEST-101\",\"bed\":3,\"joining_date\":\"2026-08-31\"}',1,'2026-08-31 16:03:29'),(115,NULL,'ROOM_ALLOCATED','Allocated to Room Wing Alpha-TEST-101, Bed 2.',NULL,'{\"room\":\"Wing Alpha-TEST-101\",\"bed\":2,\"joining_date\":\"2026-08-31\"}',1,'2026-08-31 16:03:29');
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id_str` (`student_id_str`),
  UNIQUE KEY `cnic` (`cnic`)
) ENGINE=InnoDB AUTO_INCREMENT=222 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (47,'STU-0046','Full Room A','0037116033002','03000000013','fullrooma@example.com',NULL,'Full room test',NULL,NULL,NULL,'Guardian','03000000014','6666666666666','Father',NULL,'Active',NULL,'2026-08-30 10:18:49','2026-08-30 10:18:49'),(49,'STU-0048','Concurrency A','0011053064003','03000000017','verify.concurrency-a.1788085129.1785@example.test',NULL,'Concurrency address',NULL,NULL,NULL,'Guardian','03000000018','0047629118888','Father',NULL,'Active',NULL,'2026-08-30 10:18:49','2026-08-30 10:18:49'),(100,'STU-0050','Rao Saif Ullah','3249334558764','03246586962','saif@gmail.com','A+','Faislabad Road Sargodha',NULL,NULL,NULL,'Noman Aslam','03450650761','7687654334567','Father',NULL,'Active',NULL,'2026-08-30 11:32:23','2026-08-30 11:32:23'),(113,'STU-0101','Security Deposit Validation','3520223344556','03000001333','security.1788111527@example.test',NULL,'Security deposit test address',NULL,NULL,NULL,'Guardian','03000001334','2222222222222','Father',NULL,'Active',NULL,'2026-08-30 17:38:47','2026-08-30 17:38:47'),(141,'STU-0114','Full Room Primary','0045812381001','03000000001','fullprimary@example.com',NULL,'Test address',NULL,NULL,NULL,'Guardian','03000000002','3333333333333','Father',NULL,'Active',NULL,'2026-08-30 18:00:47','2026-08-30 18:00:47');
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
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-08-29 17:26:48'),(2,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-08-29 17:31:25'),(3,1,'Create Room',NULL,NULL,'Created room 130 in Block Block A',NULL,NULL,'::1',NULL,'2026-08-29 17:32:41'),(4,1,'Update Room',NULL,NULL,'Updated room ID: 1',NULL,NULL,'::1',NULL,'2026-08-29 17:33:11'),(5,1,'Create Room',NULL,NULL,'Created room 129 in Block Block A',NULL,NULL,'::1',NULL,'2026-08-29 17:34:29'),(6,1,'Create Student',NULL,NULL,'Created student ID: STU-0001',NULL,NULL,'::1',NULL,'2026-08-29 17:36:23'),(7,1,'Room Allocation',NULL,NULL,'Allocated STU-0001 to Room 130',NULL,NULL,'::1',NULL,'2026-08-29 17:36:47'),(8,1,'Create Fee',NULL,NULL,'Created fee for student ID: STU-0001',NULL,NULL,'::1',NULL,'2026-08-29 17:37:39'),(9,1,'Pay Fee',NULL,NULL,'Recorded payment of Rs. 8000 for fee ID: 1',NULL,NULL,'::1',NULL,'2026-08-29 17:37:53'),(10,1,'Create Student',NULL,NULL,'Created student ID: STU-0002',NULL,NULL,'::1',NULL,'2026-08-29 17:48:42'),(11,1,'Room Allocation',NULL,NULL,'Allocated STU-0002 to Room 129',NULL,NULL,'::1',NULL,'2026-08-29 17:49:37'),(12,1,'Create Fee',NULL,NULL,'Created fee for student ID: STU-0002',NULL,NULL,'::1',NULL,'2026-08-29 17:50:56'),(13,1,'Pay Fee',NULL,NULL,'Recorded payment of Rs. 6000 for fee ID: 2',NULL,NULL,'::1',NULL,'2026-08-29 17:51:16'),(14,1,'Failed Login',NULL,NULL,'Failed login attempt',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 08:04:12'),(15,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 08:04:22'),(16,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 10:21:43'),(17,1,'ADMISSION_COMPLETED','student',82,'Student admission completed for Full Room A',NULL,'{\"student_id\":\"82\",\"room_allocation_enabled\":true,\"room_id\":86,\"bed_number\":0,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 10:39:56'),(18,1,'ADMISSION_COMPLETED','student',84,'Student admission completed for Concurrency A',NULL,'{\"student_id\":\"84\",\"room_allocation_enabled\":true,\"room_id\":87,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 10:39:56'),(19,1,'ADMISSION_COMPLETED','student',86,'Student admission completed for Accounting Validation Student',NULL,'{\"student_id\":\"86\",\"room_allocation_enabled\":true,\"room_id\":88,\"bed_number\":1,\"initial_payment\":7000,\"total_charges\":35000}',NULL,NULL,'2026-08-30 10:39:56'),(20,1,'ADMISSION_COMPLETED','student',87,'Student admission completed for Phase 1 Validation Student',NULL,'{\"student_id\":\"87\",\"room_allocation_enabled\":true,\"room_id\":89,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":45000}',NULL,NULL,'2026-08-30 11:23:33'),(21,1,'ADMISSION_COMPLETED','student',95,'Student admission completed for Full Room A',NULL,'{\"student_id\":\"95\",\"room_allocation_enabled\":true,\"room_id\":98,\"bed_number\":0,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 11:23:33'),(22,1,'ADMISSION_COMPLETED','student',97,'Student admission completed for Concurrency A',NULL,'{\"student_id\":\"97\",\"room_allocation_enabled\":true,\"room_id\":99,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 11:23:33'),(23,1,'ADMISSION_COMPLETED','student',99,'Student admission completed for Accounting Validation Student',NULL,'{\"student_id\":\"99\",\"room_allocation_enabled\":true,\"room_id\":100,\"bed_number\":1,\"initial_payment\":7000,\"total_charges\":35000}',NULL,NULL,'2026-08-30 11:23:34'),(24,1,'LOGIN_FAILED','admin',1,'Failed login attempt for username: admin',NULL,'{\"username\":\"admin\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.135.0 Chrome/148.0.7778.280 Electron/42.8.1 Safari/537.36','2026-08-30 11:28:08'),(25,1,'Failed Login',NULL,NULL,'Failed login attempt',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 11:28:08'),(26,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-08-30 11:29:00'),(27,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 11:29:00'),(28,1,'ADMISSION_COMPLETED','student',100,'Student admission completed for Rao Saif Ullah',NULL,'{\"student_id\":\"100\",\"room_allocation_enabled\":true,\"room_id\":1,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":10000}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-08-30 11:32:23'),(29,1,'PAYMENT_RECORDED','fee',44,'Payment recorded for student 100 amount Rs. 6,000.00','{\"remaining_before\":10000}','{\"amount\":6000,\"payment_method\":\"Cash\",\"status\":\"Partial\",\"receipt_number\":\"PAY-2026-703223\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-08-30 11:33:12'),(30,1,'Pay Fee',NULL,NULL,'Recorded payment of Rs. 6000 for fee ID: 44',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 11:33:12'),(31,1,'PAYMENT_RECORDED','fee',44,'Payment recorded for student 100 amount Rs. 2,000.00','{\"remaining_before\":4000}','{\"amount\":2000,\"payment_method\":\"Cash\",\"status\":\"Partial\",\"receipt_number\":\"PAY-2026-388613\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-08-30 11:34:00'),(32,1,'Pay Fee',NULL,NULL,'Recorded payment of Rs. 2000 for fee ID: 44',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 11:34:00'),(33,1,'ADMISSION_COMPLETED','student',108,'Student admission completed for Full Room A',NULL,'{\"student_id\":\"108\",\"room_allocation_enabled\":true,\"room_id\":109,\"bed_number\":0,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 17:35:37'),(34,1,'ADMISSION_COMPLETED','student',110,'Student admission completed for Concurrency A',NULL,'{\"student_id\":\"110\",\"room_allocation_enabled\":true,\"room_id\":110,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 17:35:37'),(35,1,'ADMISSION_COMPLETED','student',112,'Student admission completed for Accounting Validation Student',NULL,'{\"student_id\":\"112\",\"room_allocation_enabled\":true,\"room_id\":111,\"bed_number\":1,\"initial_payment\":7000,\"total_charges\":35000}',NULL,NULL,'2026-08-30 17:35:38'),(36,NULL,'ADMISSION_COMPLETED','student',113,'Student admission completed for Security Deposit Validation',NULL,'{\"student_id\":\"113\",\"room_allocation_enabled\":true,\"room_id\":112,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":35000}',NULL,NULL,'2026-08-30 17:38:47'),(37,1,'SECURITY_DEPOSIT_RECEIVED','student',113,'Security deposit received for student #113. Amount: 3000.',NULL,'{\"amount\":3000,\"reference\":\"DEP-REF-001\",\"status\":\"HELD\"}',NULL,NULL,'2026-08-30 17:38:47'),(38,1,'SECURITY_DEDUCTION','student',113,'Security deduction for student #113. Amount: 800. Reason: Damage charge',NULL,'{\"before\":3000,\"deduction\":800,\"after\":2200,\"reference\":\"DED-REF-001\"}',NULL,NULL,'2026-08-30 17:38:47'),(39,1,'SECURITY_REFUND','student',113,'Security refunded to student #113. Amount: 2200. Receipt: REF-REF-001',NULL,'{\"before\":2200,\"refund_amount\":2200,\"after\":0,\"reference\":\"REF-REF-001\"}',NULL,NULL,'2026-08-30 17:38:47'),(40,NULL,'ADMISSION_COMPLETED','student',114,'Student admission completed for Pending Fees Validation',NULL,'{\"student_id\":\"114\",\"room_allocation_enabled\":true,\"room_id\":113,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":35000}',NULL,NULL,'2026-08-30 17:48:00'),(41,1,'ADMISSION_COMPLETED','student',116,'Student admission completed for Accounting Validation Student',NULL,'{\"student_id\":\"116\",\"room_allocation_enabled\":true,\"room_id\":116,\"bed_number\":1,\"initial_payment\":7000,\"total_charges\":35000}',NULL,NULL,'2026-08-30 17:52:17'),(42,1,'ADMISSION_COMPLETED','student',124,'Student admission completed for Full Room A',NULL,'{\"student_id\":\"124\",\"room_allocation_enabled\":true,\"room_id\":125,\"bed_number\":0,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 17:52:17'),(43,1,'ADMISSION_COMPLETED','student',126,'Student admission completed for Concurrency A',NULL,'{\"student_id\":\"126\",\"room_allocation_enabled\":true,\"room_id\":126,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 17:52:17'),(44,1,'ADMISSION_COMPLETED','student',128,'Student admission completed for Phase 1 Validation Student',NULL,'{\"student_id\":\"128\",\"room_allocation_enabled\":true,\"room_id\":127,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":45000}',NULL,NULL,'2026-08-30 17:52:17'),(45,1,'ADMISSION_COMPLETED','student',129,'Student admission completed for Accounting Validation Student',NULL,'{\"student_id\":\"129\",\"room_allocation_enabled\":true,\"room_id\":129,\"bed_number\":1,\"initial_payment\":7000,\"total_charges\":35000}',NULL,NULL,'2026-08-30 17:52:58'),(46,1,'ADMISSION_COMPLETED','student',137,'Student admission completed for Full Room A',NULL,'{\"student_id\":\"137\",\"room_allocation_enabled\":true,\"room_id\":138,\"bed_number\":0,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 17:52:59'),(47,1,'ADMISSION_COMPLETED','student',139,'Student admission completed for Concurrency A',NULL,'{\"student_id\":\"139\",\"room_allocation_enabled\":true,\"room_id\":139,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 17:52:59'),(48,1,'ADMISSION_COMPLETED','student',141,'Student admission completed for Full Room Primary',NULL,'{\"student_id\":\"141\",\"room_allocation_enabled\":true,\"room_id\":140,\"bed_number\":0,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 18:00:47'),(49,1,'ADMISSION_COMPLETED','student',151,'Student admission completed for Concurrency A',NULL,'{\"student_id\":\"151\",\"room_allocation_enabled\":true,\"room_id\":150,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 18:02:47'),(50,1,'ADMISSION_COMPLETED','student',153,'Student admission completed for Accounting Validation Student',NULL,'{\"student_id\":\"153\",\"room_allocation_enabled\":true,\"room_id\":151,\"bed_number\":1,\"initial_payment\":7000,\"total_charges\":35000}',NULL,NULL,'2026-08-30 18:02:48'),(51,1,'ADMISSION_COMPLETED','student',154,'Student admission completed for Phase 1 Validation Student',NULL,'{\"student_id\":\"154\",\"room_allocation_enabled\":true,\"room_id\":152,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":45000}',NULL,NULL,'2026-08-30 18:02:48'),(52,1,'ADMISSION_COMPLETED','student',163,'Student admission completed for Full Room A',NULL,'{\"student_id\":\"163\",\"room_allocation_enabled\":true,\"room_id\":161,\"bed_number\":0,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 18:11:48'),(53,1,'ADMISSION_COMPLETED','student',165,'Student admission completed for Concurrency A',NULL,'{\"student_id\":\"165\",\"room_allocation_enabled\":true,\"room_id\":162,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 18:11:48'),(54,1,'ADMISSION_COMPLETED','student',175,'Student admission completed for Full Room A',NULL,'{\"student_id\":\"175\",\"room_allocation_enabled\":true,\"room_id\":172,\"bed_number\":0,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 18:12:09'),(55,1,'ADMISSION_COMPLETED','student',177,'Student admission completed for Concurrency A',NULL,'{\"student_id\":\"177\",\"room_allocation_enabled\":true,\"room_id\":173,\"bed_number\":1,\"initial_payment\":0,\"total_charges\":15000}',NULL,NULL,'2026-08-30 18:12:09'),(56,1,'ADMISSION_COMPLETED','student',190,'Student admission completed for Accounting Validation Student',NULL,'{\"student_id\":\"190\",\"room_allocation_enabled\":true,\"room_id\":174,\"bed_number\":1,\"initial_payment\":7000,\"total_charges\":35000}',NULL,NULL,'2026-08-30 18:27:53'),(57,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','2026-08-30 18:33:58'),(58,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 18:33:58'),(59,1,'PAYMENT_RECORDED','fee',49,'Payment recorded for student 113 amount Rs. 15,000.00','{\"remaining_before\":35000}','{\"amount\":15000,\"payment_method\":\"Cash\",\"status\":\"Partial\",\"receipt_number\":\"PAY-2026-955261\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','2026-08-30 18:34:50'),(60,1,'Pay Fee',NULL,NULL,'Recorded payment of Rs. 15000 for fee ID: 49',NULL,NULL,'127.0.0.1',NULL,'2026-08-30 18:34:50'),(61,1,'STUDENT_CREATED','student',191,'Student profile created: STU-0142',NULL,'{\"student_id_str\":\"STU-0142\",\"full_name\":\"Test Student One\",\"cnic\":\"9999911111111\",\"phone\":\"03001111111\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 1\",\"guardian_phone\":\"03001111112\",\"guardian_cnic\":\"9999911111112\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 15:57:14'),(62,1,'Create Student',NULL,NULL,'Created student ID: STU-0142',NULL,NULL,NULL,NULL,'2026-08-31 15:57:14'),(63,1,'STUDENT_CREATED','student',192,'Student profile created: STU-0142',NULL,'{\"student_id_str\":\"STU-0142\",\"full_name\":\"Test Student One\",\"cnic\":\"9999911111111\",\"phone\":\"03001111111\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 1\",\"guardian_phone\":\"03001111112\",\"guardian_cnic\":\"9999911111112\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 16:01:53'),(64,1,'Create Student',NULL,NULL,'Created student ID: STU-0142',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:01:53'),(65,1,'STUDENT_CREATED','student',193,'Student profile created: STU-0193',NULL,'{\"student_id_str\":\"STU-0193\",\"full_name\":\"Test Student Two\",\"cnic\":\"9999922222222\",\"phone\":\"03002222222\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 2\",\"guardian_phone\":\"03002222223\",\"guardian_cnic\":\"9999922222223\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 16:01:53'),(66,1,'Create Student',NULL,NULL,'Created student ID: STU-0193',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:01:53'),(67,1,'STUDENT_CREATED','student',194,'Student profile created: STU-0194',NULL,'{\"student_id_str\":\"STU-0194\",\"full_name\":\"Test Student Three\",\"cnic\":\"9999933333333\",\"phone\":\"03003333333\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 3\",\"guardian_phone\":\"03003333334\",\"guardian_cnic\":\"9999933333334\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 16:01:53'),(68,1,'Create Student',NULL,NULL,'Created student ID: STU-0194',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:01:53'),(69,1,'STUDENT_CREATED','student',195,'Student profile created: STU-0195',NULL,'{\"student_id_str\":\"STU-0195\",\"full_name\":\"Test Student Four\",\"cnic\":\"9999944444444\",\"phone\":\"03004444444\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 4\",\"guardian_phone\":\"03004444445\",\"guardian_cnic\":\"9999944444445\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 16:01:53'),(70,1,'Create Student',NULL,NULL,'Created student ID: STU-0195',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:01:53'),(71,1,'ROOM_CREATED','room',175,'Room created: TEST-101 in block Wing Alpha',NULL,'{\"room_number\":\"TEST-101\",\"block\":\"Wing Alpha\",\"floor\":\"1st\",\"room_type\":\"Dormitory\",\"total_beds\":4,\"monthly_fee\":8000,\"security_deposit\":10000,\"status\":\"Available\"}',NULL,NULL,'2026-08-31 16:01:53'),(72,1,'Create Room',NULL,NULL,'Created room TEST-101 in Block Wing Alpha',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:01:53'),(73,1,'STUDENT_CREATED','student',196,'Student profile created: STU-0142',NULL,'{\"student_id_str\":\"STU-0142\",\"full_name\":\"Test Student One\",\"cnic\":\"9999911111111\",\"phone\":\"03001111111\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 1\",\"guardian_phone\":\"03001111112\",\"guardian_cnic\":\"9999911111112\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 16:03:29'),(74,1,'Create Student',NULL,NULL,'Created student ID: STU-0142',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(75,1,'STUDENT_CREATED','student',197,'Student profile created: STU-0197',NULL,'{\"student_id_str\":\"STU-0197\",\"full_name\":\"Test Student Two\",\"cnic\":\"9999922222222\",\"phone\":\"03002222222\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 2\",\"guardian_phone\":\"03002222223\",\"guardian_cnic\":\"9999922222223\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 16:03:29'),(76,1,'Create Student',NULL,NULL,'Created student ID: STU-0197',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(77,1,'STUDENT_CREATED','student',198,'Student profile created: STU-0198',NULL,'{\"student_id_str\":\"STU-0198\",\"full_name\":\"Test Student Three\",\"cnic\":\"9999933333333\",\"phone\":\"03003333333\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 3\",\"guardian_phone\":\"03003333334\",\"guardian_cnic\":\"9999933333334\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 16:03:29'),(78,1,'Create Student',NULL,NULL,'Created student ID: STU-0198',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(79,1,'STUDENT_CREATED','student',199,'Student profile created: STU-0199',NULL,'{\"student_id_str\":\"STU-0199\",\"full_name\":\"Test Student Four\",\"cnic\":\"9999944444444\",\"phone\":\"03004444444\",\"email\":null,\"blood_group\":null,\"address\":\"Test Address\",\"guardian_name\":\"Guardian 4\",\"guardian_phone\":\"03004444445\",\"guardian_cnic\":\"9999944444445\",\"relation\":\"Father\",\"status\":\"Active\"}',NULL,NULL,'2026-08-31 16:03:29'),(80,1,'Create Student',NULL,NULL,'Created student ID: STU-0199',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(81,1,'ROOM_CREATED','room',176,'Room created: TEST-101 in block Wing Alpha',NULL,'{\"room_number\":\"TEST-101\",\"block\":\"Wing Alpha\",\"floor\":\"1st\",\"room_type\":\"Dormitory\",\"total_beds\":4,\"monthly_fee\":8000,\"security_deposit\":10000,\"status\":\"Available\"}',NULL,NULL,'2026-08-31 16:03:29'),(82,1,'Create Room',NULL,NULL,'Created room TEST-101 in Block Wing Alpha',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(83,1,'ROOM_ALLOCATED','allocation',112,'Student STU-0142 allocated to room TEST-101 (Bed 2)',NULL,'{\"student_id\":196,\"room_id\":176,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 16:03:29'),(84,1,'Room Allocation',NULL,NULL,'Allocated STU-0142 to Room TEST-101 Bed 2',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(85,1,'ROOM_ALLOCATED','allocation',113,'Student STU-0197 allocated to room TEST-101 (Bed 4)',NULL,'{\"student_id\":197,\"room_id\":176,\"bed_number\":4,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 16:03:29'),(86,1,'Room Allocation',NULL,NULL,'Allocated STU-0197 to Room TEST-101 Bed 4',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(87,1,'ROOM_ALLOCATION_CLOSED','allocation',112,'Allocation closed for student ID 196 from room TEST-101 Bed 2','{\"room_id\":176,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}','{\"leaving_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 16:03:29'),(88,1,'Room Deallocation',NULL,NULL,'Deallocated ID: 112',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(89,1,'ROOM_ALLOCATED','allocation',114,'Student STU-0142 allocated to room TEST-101 (Bed 1)',NULL,'{\"student_id\":196,\"room_id\":176,\"bed_number\":1,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 16:03:29'),(90,1,'Room Allocation',NULL,NULL,'Allocated STU-0142 to Room TEST-101 Bed 1',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(91,1,'ROOM_ALLOCATED','allocation',115,'Student STU-0198 allocated to room TEST-101 (Bed 3)',NULL,'{\"student_id\":198,\"room_id\":176,\"bed_number\":3,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 16:03:29'),(92,1,'Room Allocation',NULL,NULL,'Allocated STU-0198 to Room TEST-101 Bed 3',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(93,1,'ROOM_CREATED','room',177,'Room created: TEST-102 in block Wing Beta',NULL,'{\"room_number\":\"TEST-102\",\"block\":\"Wing Beta\",\"floor\":\"2nd\",\"room_type\":\"Double\",\"total_beds\":2,\"monthly_fee\":9000,\"security_deposit\":10000,\"status\":\"Available\"}',NULL,NULL,'2026-08-31 16:03:29'),(94,1,'Create Room',NULL,NULL,'Created room TEST-102 in Block Wing Beta',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(95,1,'ROOM_ALLOCATED','allocation',117,'Student STU-0199 allocated to room TEST-101 (Bed 2)',NULL,'{\"student_id\":199,\"room_id\":176,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 16:03:29'),(96,1,'Room Allocation',NULL,NULL,'Allocated STU-0199 to Room TEST-101 Bed 2',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 16:03:29'),(97,1,'ROOM_CREATED','room',178,'Room created: TEST-999 in block T-BLOCK',NULL,'{\"room_number\":\"TEST-999\",\"block\":\"T-BLOCK\",\"floor\":\"1st\",\"room_type\":\"Standard Quad\",\"total_beds\":4,\"monthly_fee\":10000,\"security_deposit\":10000,\"status\":\"Available\"}',NULL,NULL,'2026-08-31 17:33:55'),(98,1,'Create Room',NULL,NULL,'Created room TEST-999 in Block T-BLOCK',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(99,1,'STUDENT_ONBOARDED','student',200,'Student STU-0142 onboarded.',NULL,'{\"student_id_str\":\"STU-0142\",\"monthly_fee\":8000,\"security_deposit\":10000}',NULL,NULL,'2026-08-31 17:33:55'),(100,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0142',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(101,1,'ROOM_ALLOCATED','allocation',118,'Student STU-0142 allocated to room TEST-999 (Bed 1)',NULL,'{\"student_id\":200,\"room_id\":178,\"bed_number\":1,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:33:55'),(102,1,'Room Allocation',NULL,NULL,'Allocated STU-0142 to Room TEST-999 Bed 1',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(103,1,'STUDENT_ONBOARDED','student',201,'Student STU-0201 onboarded.',NULL,'{\"student_id_str\":\"STU-0201\",\"monthly_fee\":7500,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:33:55'),(104,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0201',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(105,1,'ROOM_ALLOCATED','allocation',119,'Student STU-0201 allocated to room TEST-999 (Bed 2)',NULL,'{\"student_id\":201,\"room_id\":178,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:33:55'),(106,1,'Room Allocation',NULL,NULL,'Allocated STU-0201 to Room TEST-999 Bed 2',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(107,1,'ROOM_ALLOCATION_CLOSED','allocation',118,'Allocation closed for student ID 200 from room TEST-999 Bed 1','{\"room_id\":178,\"bed_number\":1,\"joining_date\":\"2026-08-31\"}','{\"leaving_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:33:55'),(108,1,'Room Deallocation',NULL,NULL,'Deallocated ID: 118',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(109,1,'ROOM_ALLOCATION_CLOSED','allocation',119,'Allocation closed for student ID 201 from room TEST-999 Bed 2','{\"room_id\":178,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}','{\"leaving_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:33:55'),(110,1,'Room Deallocation',NULL,NULL,'Deallocated ID: 119',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(111,1,'STUDENT_ONBOARDED','student',203,'Student STU-0202 onboarded. Room 178 Bed 1.',NULL,'{\"student_id_str\":\"STU-0202\",\"monthly_fee\":8500,\"security_deposit\":10000}',NULL,NULL,'2026-08-31 17:33:55'),(112,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0202',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(113,1,'STUDENT_ONBOARDED','student',204,'Student STU-0204 onboarded.',NULL,'{\"student_id_str\":\"STU-0204\",\"monthly_fee\":8000,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:33:55'),(114,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0204',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(115,1,'STUDENT_ONBOARDED','student',205,'Student STU-0205 onboarded.',NULL,'{\"student_id_str\":\"STU-0205\",\"monthly_fee\":7500,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:33:55'),(116,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0205',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(117,1,'STUDENT_ONBOARDED','student',206,'Student STU-0206 onboarded.',NULL,'{\"student_id_str\":\"STU-0206\",\"monthly_fee\":9000,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:33:55'),(118,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0206',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(119,1,'ROOM_UPDATED','room',178,'Room configuration updated: TEST-999','{\"monthly_fee\":\"10000.00\",\"security_deposit\":\"10000.00\"}','{\"monthly_fee\":15000,\"security_deposit\":12000}',NULL,NULL,'2026-08-31 17:33:55'),(120,1,'Update Room',NULL,NULL,'Updated room ID: 178',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(121,1,'INVOICE_CREATED','fee',94,'Invoice created for STU-0204 — 8/2026 — Rs. 8,000.00',NULL,'{\"student_id\":\"204\",\"billing_month\":8,\"billing_year\":2026,\"amount\":8000,\"due_date\":\"2026-09-07\",\"status\":\"Pending\",\"charge_type\":\"MONTHLY_FEE\",\"remarks\":\"\"}',NULL,NULL,'2026-08-31 17:33:55'),(122,1,'Create Fee Invoice',NULL,NULL,'Invoice for STU-0204',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(123,1,'PAYMENT_RECORDED','fee',94,'Payment Rs. 3,000.00 recorded for STU-0204 (Invoice #INV-B03E4514) via Cash. Status: Partial.','{\"paid_before\":\"0.00\"}','{\"amount\":3000,\"method\":\"Cash\",\"receipt\":\"RCP-B03E87FE\"}',NULL,NULL,'2026-08-31 17:33:55'),(124,1,'Fee Payment',NULL,NULL,'Rs. 3000 for invoice #94. Receipt: RCP-B03E87FE',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:55'),(125,1,'PAYMENT_RECORDED','fee',94,'Payment Rs. 2,000.00 recorded for STU-0204 (Invoice #INV-B03E4514) via JazzCash. Status: Partial.','{\"paid_before\":\"3000.00\"}','{\"amount\":2000,\"method\":\"JazzCash\",\"receipt\":\"RCP-B03F1B38\"}',NULL,NULL,'2026-08-31 17:33:55'),(126,1,'Fee Payment',NULL,NULL,'Rs. 2000 for invoice #94. Receipt: RCP-B03F1B38',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:56'),(127,1,'PAYMENT_RECORDED','fee',94,'Payment Rs. 3,000.00 recorded for STU-0204 (Invoice #INV-B03E4514) via Bank Transfer. Status: Paid.','{\"paid_before\":\"5000.00\"}','{\"amount\":3000,\"method\":\"Bank Transfer\",\"receipt\":\"RCP-B040A07C\"}',NULL,NULL,'2026-08-31 17:33:56'),(128,1,'Fee Payment',NULL,NULL,'Rs. 3000 for invoice #94. Receipt: RCP-B040A07C',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:56'),(129,1,'INVOICE_CREATED','fee',95,'Invoice created for STU-0205 — 8/2026 — Rs. 7,500.00',NULL,'{\"student_id\":\"205\",\"billing_month\":8,\"billing_year\":2026,\"amount\":7500,\"due_date\":\"2026-09-07\",\"status\":\"Pending\",\"charge_type\":\"MONTHLY_FEE\",\"remarks\":\"\"}',NULL,NULL,'2026-08-31 17:33:56'),(130,1,'Create Fee Invoice',NULL,NULL,'Invoice for STU-0205',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:33:56'),(131,1,'STUDENT_ONBOARDED','student',207,'Student STU-0207 onboarded.',NULL,'{\"student_id_str\":\"STU-0207\",\"monthly_fee\":8000,\"security_deposit\":10000}',NULL,NULL,'2026-08-31 17:34:50'),(132,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0207',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:34:50'),(133,1,'ROOM_CREATED','room',179,'Room created: TEST-999 in block T-BLOCK',NULL,'{\"room_number\":\"TEST-999\",\"block\":\"T-BLOCK\",\"floor\":\"1st\",\"room_type\":\"Standard Quad\",\"total_beds\":4,\"monthly_fee\":10000,\"security_deposit\":10000,\"status\":\"Available\"}',NULL,NULL,'2026-08-31 17:42:39'),(134,1,'Create Room',NULL,NULL,'Created room TEST-999 in Block T-BLOCK',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(135,1,'STUDENT_ONBOARDED','student',208,'Student STU-0142 onboarded.',NULL,'{\"student_id_str\":\"STU-0142\",\"monthly_fee\":8000,\"security_deposit\":10000}',NULL,NULL,'2026-08-31 17:42:39'),(136,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0142',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(137,1,'ROOM_ALLOCATED','allocation',121,'Student STU-0142 allocated to room TEST-999 (Bed 1)',NULL,'{\"student_id\":208,\"room_id\":179,\"bed_number\":1,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:42:39'),(138,1,'Room Allocation',NULL,NULL,'Allocated STU-0142 to Room TEST-999 Bed 1',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(139,1,'STUDENT_ONBOARDED','student',209,'Student STU-0209 onboarded.',NULL,'{\"student_id_str\":\"STU-0209\",\"monthly_fee\":7500,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:42:39'),(140,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0209',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(141,1,'ROOM_ALLOCATED','allocation',122,'Student STU-0209 allocated to room TEST-999 (Bed 2)',NULL,'{\"student_id\":209,\"room_id\":179,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:42:39'),(142,1,'Room Allocation',NULL,NULL,'Allocated STU-0209 to Room TEST-999 Bed 2',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(143,1,'ROOM_ALLOCATION_CLOSED','allocation',121,'Allocation closed for student ID 208 from room TEST-999 Bed 1','{\"room_id\":179,\"bed_number\":1,\"joining_date\":\"2026-08-31\"}','{\"leaving_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:42:39'),(144,1,'Room Deallocation',NULL,NULL,'Deallocated ID: 121',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(145,1,'ROOM_ALLOCATION_CLOSED','allocation',122,'Allocation closed for student ID 209 from room TEST-999 Bed 2','{\"room_id\":179,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}','{\"leaving_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:42:39'),(146,1,'Room Deallocation',NULL,NULL,'Deallocated ID: 122',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(147,1,'STUDENT_ONBOARDED','student',211,'Student STU-0210 onboarded. Room 179 Bed 1.',NULL,'{\"student_id_str\":\"STU-0210\",\"monthly_fee\":8500,\"security_deposit\":10000}',NULL,NULL,'2026-08-31 17:42:39'),(148,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0210',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(149,1,'STUDENT_ONBOARDED','student',212,'Student STU-0212 onboarded.',NULL,'{\"student_id_str\":\"STU-0212\",\"monthly_fee\":8000,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:42:39'),(150,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0212',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(151,1,'STUDENT_ONBOARDED','student',213,'Student STU-0213 onboarded.',NULL,'{\"student_id_str\":\"STU-0213\",\"monthly_fee\":7500,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:42:39'),(152,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0213',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(153,1,'STUDENT_ONBOARDED','student',214,'Student STU-0214 onboarded.',NULL,'{\"student_id_str\":\"STU-0214\",\"monthly_fee\":9000,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:42:39'),(154,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0214',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(155,1,'ROOM_UPDATED','room',179,'Room configuration updated: TEST-999','{\"monthly_fee\":\"10000.00\",\"security_deposit\":\"10000.00\"}','{\"monthly_fee\":15000,\"security_deposit\":12000}',NULL,NULL,'2026-08-31 17:42:39'),(156,1,'Update Room',NULL,NULL,'Updated room ID: 179',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(157,1,'INVOICE_CREATED','fee',97,'Invoice created for STU-0212 — 8/2026 — Rs. 8,000.00',NULL,'{\"student_id\":\"212\",\"billing_month\":8,\"billing_year\":2026,\"amount\":8000,\"due_date\":\"2026-09-07\",\"status\":\"Pending\",\"charge_type\":\"MONTHLY_FEE\",\"remarks\":\"\"}',NULL,NULL,'2026-08-31 17:42:39'),(158,1,'Create Fee Invoice',NULL,NULL,'Invoice for STU-0212',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(159,1,'PAYMENT_RECORDED','fee',97,'Payment Rs. 3,000.00 recorded for STU-0212 (Invoice #INV-D0F8697F) via Cash. Status: Partial.','{\"paid_before\":\"0.00\"}','{\"amount\":3000,\"method\":\"Cash\",\"receipt\":\"RCP-D0F8859A\"}',NULL,NULL,'2026-08-31 17:42:39'),(160,1,'Fee Payment',NULL,NULL,'Rs. 3000 for invoice #97. Receipt: RCP-D0F8859A',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(161,1,'PAYMENT_RECORDED','fee',97,'Payment Rs. 2,000.00 recorded for STU-0212 (Invoice #INV-D0F8697F) via JazzCash. Status: Partial.','{\"paid_before\":\"3000.00\"}','{\"amount\":2000,\"method\":\"JazzCash\",\"receipt\":\"RCP-D0F8AB13\"}',NULL,NULL,'2026-08-31 17:42:39'),(162,1,'Fee Payment',NULL,NULL,'Rs. 2000 for invoice #97. Receipt: RCP-D0F8AB13',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(163,1,'PAYMENT_RECORDED','fee',97,'Payment Rs. 3,000.00 recorded for STU-0212 (Invoice #INV-D0F8697F) via Bank Transfer. Status: Paid.','{\"paid_before\":\"5000.00\"}','{\"amount\":3000,\"method\":\"Bank Transfer\",\"receipt\":\"RCP-D0F8D5DF\"}',NULL,NULL,'2026-08-31 17:42:39'),(164,1,'Fee Payment',NULL,NULL,'Rs. 3000 for invoice #97. Receipt: RCP-D0F8D5DF',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(165,1,'INVOICE_CREATED','fee',98,'Invoice created for STU-0213 — 8/2026 — Rs. 7,500.00',NULL,'{\"student_id\":\"213\",\"billing_month\":8,\"billing_year\":2026,\"amount\":7500,\"due_date\":\"2026-09-07\",\"status\":\"Pending\",\"charge_type\":\"MONTHLY_FEE\",\"remarks\":\"\"}',NULL,NULL,'2026-08-31 17:42:39'),(166,1,'Create Fee Invoice',NULL,NULL,'Invoice for STU-0213',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(167,1,'STUDENT_CHECKOUT','alumni',1,'Student STU-0210 checked out and converted to alumni. Room released: T-BLOCK-TEST-999','{\"status\":\"Active\",\"room_allocation\":{\"room\":\"T-BLOCK-TEST-999\",\"bed\":1},\"security_deposit\":{\"status\":\"HELD\",\"remaining\":\"10000.00\"}}','{\"status\":\"Inactive\",\"alumni_id\":\"1\",\"leaving_date\":\"2026-08-31\",\"security_settlement\":{\"original_amount\":10000,\"deducted\":1500,\"refunded\":8500,\"status\":\"REFUNDED\",\"remarks\":\"Rs. 1500 deducted for room repairs\"}}',NULL,NULL,'2026-08-31 17:42:39'),(168,1,'Error',NULL,NULL,'Alumni conversion failed: Only active students can be converted to alumni.',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:42:39'),(169,1,'ROOM_CREATED','room',180,'Room created: TEST-999 in block T-BLOCK',NULL,'{\"room_number\":\"TEST-999\",\"block\":\"T-BLOCK\",\"floor\":\"1st\",\"room_type\":\"Standard Quad\",\"total_beds\":4,\"monthly_fee\":10000,\"security_deposit\":10000,\"status\":\"Available\"}',NULL,NULL,'2026-08-31 17:46:16'),(170,1,'Create Room',NULL,NULL,'Created room TEST-999 in Block T-BLOCK',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(171,1,'STUDENT_ONBOARDED','student',215,'Student STU-0142 onboarded.',NULL,'{\"student_id_str\":\"STU-0142\",\"monthly_fee\":8000,\"security_deposit\":10000}',NULL,NULL,'2026-08-31 17:46:16'),(172,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0142',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(173,1,'ROOM_ALLOCATED','allocation',124,'Student STU-0142 allocated to room TEST-999 (Bed 1)',NULL,'{\"student_id\":215,\"room_id\":180,\"bed_number\":1,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:46:16'),(174,1,'Room Allocation',NULL,NULL,'Allocated STU-0142 to Room TEST-999 Bed 1',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(175,1,'STUDENT_ONBOARDED','student',216,'Student STU-0216 onboarded.',NULL,'{\"student_id_str\":\"STU-0216\",\"monthly_fee\":7500,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:46:16'),(176,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0216',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(177,1,'ROOM_ALLOCATED','allocation',125,'Student STU-0216 allocated to room TEST-999 (Bed 2)',NULL,'{\"student_id\":216,\"room_id\":180,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:46:16'),(178,1,'Room Allocation',NULL,NULL,'Allocated STU-0216 to Room TEST-999 Bed 2',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(179,1,'ROOM_ALLOCATION_CLOSED','allocation',124,'Allocation closed for student ID 215 from room TEST-999 Bed 1','{\"room_id\":180,\"bed_number\":1,\"joining_date\":\"2026-08-31\"}','{\"leaving_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:46:16'),(180,1,'Room Deallocation',NULL,NULL,'Deallocated ID: 124',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(181,1,'ROOM_ALLOCATION_CLOSED','allocation',125,'Allocation closed for student ID 216 from room TEST-999 Bed 2','{\"room_id\":180,\"bed_number\":2,\"joining_date\":\"2026-08-31\"}','{\"leaving_date\":\"2026-08-31\"}',NULL,NULL,'2026-08-31 17:46:16'),(182,1,'Room Deallocation',NULL,NULL,'Deallocated ID: 125',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(183,1,'STUDENT_ONBOARDED','student',218,'Student STU-0217 onboarded. Room 180 Bed 1.',NULL,'{\"student_id_str\":\"STU-0217\",\"monthly_fee\":8500,\"security_deposit\":10000}',NULL,NULL,'2026-08-31 17:46:16'),(184,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0217',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(185,1,'STUDENT_ONBOARDED','student',219,'Student STU-0219 onboarded.',NULL,'{\"student_id_str\":\"STU-0219\",\"monthly_fee\":8000,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:46:16'),(186,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0219',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(187,1,'STUDENT_ONBOARDED','student',220,'Student STU-0220 onboarded.',NULL,'{\"student_id_str\":\"STU-0220\",\"monthly_fee\":7500,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:46:16'),(188,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0220',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(189,1,'STUDENT_ONBOARDED','student',221,'Student STU-0221 onboarded.',NULL,'{\"student_id_str\":\"STU-0221\",\"monthly_fee\":9000,\"security_deposit\":0}',NULL,NULL,'2026-08-31 17:46:16'),(190,1,'Student Onboarding',NULL,NULL,'Onboarded STU-0221',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(191,1,'ROOM_UPDATED','room',180,'Room configuration updated: TEST-999','{\"monthly_fee\":\"10000.00\",\"security_deposit\":\"10000.00\"}','{\"monthly_fee\":15000,\"security_deposit\":12000}',NULL,NULL,'2026-08-31 17:46:16'),(192,1,'Update Room',NULL,NULL,'Updated room ID: 180',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(193,1,'INVOICE_CREATED','fee',100,'Invoice created for STU-0219 — 8/2026 — Rs. 8,000.00',NULL,'{\"student_id\":\"219\",\"billing_month\":8,\"billing_year\":2026,\"amount\":8000,\"due_date\":\"2026-09-07\",\"status\":\"Pending\",\"charge_type\":\"MONTHLY_FEE\",\"remarks\":\"\"}',NULL,NULL,'2026-08-31 17:46:16'),(194,1,'Create Fee Invoice',NULL,NULL,'Invoice for STU-0219',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(195,1,'PAYMENT_RECORDED','fee',100,'Payment Rs. 3,000.00 recorded for STU-0219 (Invoice #INV-DE879029) via Cash. Status: Partial.','{\"paid_before\":\"0.00\"}','{\"amount\":3000,\"method\":\"Cash\",\"receipt\":\"RCP-DE87AE6B\"}',NULL,NULL,'2026-08-31 17:46:16'),(196,1,'Fee Payment',NULL,NULL,'Rs. 3000 for invoice #100. Receipt: RCP-DE87AE6B',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(197,1,'PAYMENT_RECORDED','fee',100,'Payment Rs. 2,000.00 recorded for STU-0219 (Invoice #INV-DE879029) via JazzCash. Status: Partial.','{\"paid_before\":\"3000.00\"}','{\"amount\":2000,\"method\":\"JazzCash\",\"receipt\":\"RCP-DE87D3DD\"}',NULL,NULL,'2026-08-31 17:46:16'),(198,1,'Fee Payment',NULL,NULL,'Rs. 2000 for invoice #100. Receipt: RCP-DE87D3DD',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(199,1,'PAYMENT_RECORDED','fee',100,'Payment Rs. 3,000.00 recorded for STU-0219 (Invoice #INV-DE879029) via Bank Transfer. Status: Paid.','{\"paid_before\":\"5000.00\"}','{\"amount\":3000,\"method\":\"Bank Transfer\",\"receipt\":\"RCP-DE87F5A3\"}',NULL,NULL,'2026-08-31 17:46:16'),(200,1,'Fee Payment',NULL,NULL,'Rs. 3000 for invoice #100. Receipt: RCP-DE87F5A3',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(201,1,'INVOICE_CREATED','fee',101,'Invoice created for STU-0220 — 8/2026 — Rs. 7,500.00',NULL,'{\"student_id\":\"220\",\"billing_month\":8,\"billing_year\":2026,\"amount\":7500,\"due_date\":\"2026-09-07\",\"status\":\"Pending\",\"charge_type\":\"MONTHLY_FEE\",\"remarks\":\"\"}',NULL,NULL,'2026-08-31 17:46:16'),(202,1,'Create Fee Invoice',NULL,NULL,'Invoice for STU-0220',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(203,1,'STUDENT_CHECKOUT','alumni',2,'Student STU-0217 checked out and converted to alumni. Room released: T-BLOCK-TEST-999','{\"status\":\"Active\",\"room_allocation\":{\"room\":\"T-BLOCK-TEST-999\",\"bed\":1},\"security_deposit\":{\"status\":\"HELD\",\"remaining\":\"10000.00\"}}','{\"status\":\"Inactive\",\"alumni_id\":\"2\",\"leaving_date\":\"2026-08-31\",\"security_settlement\":{\"original_amount\":10000,\"deducted\":1500,\"refunded\":8500,\"status\":\"REFUNDED\",\"remarks\":\"Rs. 1500 deducted for room repairs\"}}',NULL,NULL,'2026-08-31 17:46:16'),(204,1,'Error',NULL,NULL,'Alumni conversion failed: Only active students can be converted to alumni.',NULL,NULL,'127.0.0.1',NULL,'2026-08-31 17:46:16'),(205,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:24:58'),(206,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 11:24:58'),(207,1,'LOGOUT','admin',1,'Admin logged out.',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:24:59'),(208,1,'Logout',NULL,NULL,'Admin logged out',NULL,NULL,'::1',NULL,'2026-09-01 11:24:59'),(209,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:25:26'),(210,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 11:25:26'),(211,1,'LOGOUT','admin',1,'Admin logged out.',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:25:27'),(212,1,'Logout',NULL,NULL,'Admin logged out',NULL,NULL,'::1',NULL,'2026-09-01 11:25:27'),(213,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:27:14'),(214,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 11:27:14'),(215,1,'LOGOUT','admin',1,'Admin logged out.',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:27:15'),(216,1,'Logout',NULL,NULL,'Admin logged out',NULL,NULL,'::1',NULL,'2026-09-01 11:27:15'),(217,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:27:30'),(218,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 11:27:30'),(219,1,'LOGOUT','admin',1,'Admin logged out.',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:27:31'),(220,1,'Logout',NULL,NULL,'Admin logged out',NULL,NULL,'::1',NULL,'2026-09-01 11:27:31'),(221,1,'LOGIN_FAILED','admin',1,'Failed login attempt for username: admin',NULL,'{\"username\":\"admin\"}','::1',NULL,'2026-09-01 11:28:06'),(222,1,'Failed Login',NULL,NULL,'Failed login attempt',NULL,NULL,'::1',NULL,'2026-09-01 11:28:06'),(223,1,'LOGIN_FAILED','admin',1,'Failed login attempt for username: admin',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456','2026-09-01 11:44:46'),(224,1,'Failed Login',NULL,NULL,'Failed login attempt',NULL,NULL,'::1',NULL,'2026-09-01 11:44:46'),(225,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456','2026-09-01 11:46:46'),(226,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 11:46:46'),(227,1,'LOGIN_FAILED','admin',1,'Failed login attempt for username: admin',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456','2026-09-01 11:47:39'),(228,1,'Failed Login',NULL,NULL,'Failed login attempt',NULL,NULL,'::1',NULL,'2026-09-01 11:47:39'),(229,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456','2026-09-01 11:58:57'),(230,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 11:58:57'),(231,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456','2026-09-01 11:59:48'),(232,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 11:59:48'),(233,1,'LOGOUT','admin',1,'Admin logged out.',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456','2026-09-01 11:59:48'),(234,1,'Logout',NULL,NULL,'Admin logged out',NULL,NULL,'::1',NULL,'2026-09-01 11:59:48'),(235,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456','2026-09-01 11:59:49'),(236,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 11:59:49'),(237,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.135.0 Chrome/148.0.7778.280 Electron/42.8.1 Safari/537.36','2026-09-01 12:09:27'),(238,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 12:09:27'),(239,1,'LOGIN_SUCCESS','admin',1,'Admin logged in successfully.',NULL,'{\"username\":\"admin\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 12:10:49'),(240,1,'Login',NULL,NULL,'Admin logged in successfully',NULL,NULL,'::1',NULL,'2026-09-01 12:10:50');
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

-- Dump completed on 2026-09-01 23:36:50
