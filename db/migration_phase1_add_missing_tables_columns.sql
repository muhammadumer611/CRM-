-- ============================================================================
-- HMS Database Migration Phase 1: Add Missing Tables and Columns
-- ============================================================================
-- Database: hms_db
-- Date: 2026-08-13
-- Purpose: Add tables and columns required by new features:
--   - Reports & Analytics
--   - Notifications
--   - Audit Logs
--   - Complaints
--
-- SAFETY NOTES:
-- - Uses CREATE TABLE IF NOT EXISTS (will not overwrite existing tables)
-- - Uses ALTER TABLE IF NOT EXISTS (will not add existing columns)
-- - NO destructive operations (DROP, TRUNCATE, DELETE)
-- - All existing data is preserved
-- - New tables are fully compatible with existing schema
-- ============================================================================

USE hms_db;

-- ============================================================================
-- PHASE 1.1: Add Missing Columns to system_logs
-- ============================================================================
-- Required by: AuditLogger.php, AuditLogService.php, AuditLogRepository.php
-- These columns enable audit logging for entity-level changes with history tracking

ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS entity_type VARCHAR(50) NULL AFTER action;
ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS entity_id INT NULL AFTER entity_type;
ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS old_values JSON NULL AFTER description;
ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS new_values JSON NULL AFTER old_values;
ALTER TABLE system_logs ADD COLUMN IF NOT EXISTS user_agent VARCHAR(255) NULL AFTER ip_address;

-- Add indexes for query performance
ALTER TABLE system_logs ADD INDEX IF NOT EXISTS idx_entity_type (entity_type);
ALTER TABLE system_logs ADD INDEX IF NOT EXISTS idx_entity_id (entity_id);

-- ============================================================================
-- PHASE 1.2: Add Missing Columns to fee_records
-- ============================================================================
-- Required by: FeeService.php, ReportsRepository.php
-- These columns are needed for invoice management and financial reporting

ALTER TABLE fee_records ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(50) NULL UNIQUE AFTER id;
ALTER TABLE fee_records ADD COLUMN IF NOT EXISTS invoice_date DATE NOT NULL DEFAULT CURRENT_DATE AFTER billing_year;
ALTER TABLE fee_records ADD COLUMN IF NOT EXISTS additional_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount;
ALTER TABLE fee_records ADD COLUMN IF NOT EXISTS discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER additional_charges;

-- Add index for invoice lookup
ALTER TABLE fee_records ADD INDEX IF NOT EXISTS idx_invoice_number (invoice_number);

-- ============================================================================
-- PHASE 1.3: Create fee_payments Table
-- ============================================================================
-- Required by: FeeService.php, ReportsRepository.php
-- Purpose: Track individual payment transactions for each fee invoice
-- This separates invoices from payments (partial payments support)

CREATE TABLE IF NOT EXISTS fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Online', 'Other') NOT NULL,
    transaction_ref VARCHAR(100) NULL,
    remarks TEXT NULL,
    received_by_admin INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES fee_records(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by_admin) REFERENCES admins(id) ON DELETE SET NULL,
    KEY idx_fee_payments_invoice (invoice_id, payment_date),
    KEY idx_fee_payments_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PHASE 1.4: Create notifications Table
-- ============================================================================
-- Required by: NotificationService.php
-- Purpose: System-wide notifications for fees, payments, rooms, allocations, students

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'system',
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    notification_key VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    UNIQUE KEY uk_notifications_key (notification_key),
    KEY idx_notifications_is_read (is_read),
    KEY idx_notifications_type (type),
    KEY idx_notifications_priority (priority),
    KEY idx_notifications_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PHASE 1.5: Create complaints Table
-- ============================================================================
-- Required by: ComplaintService.php, ComplaintRepository.php, ComplaintController.php
-- Purpose: Track student complaints and responses from administration

CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'Open',
    priority VARCHAR(30) DEFAULT 'Normal',
    admin_response TEXT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    KEY idx_student_id (student_id),
    KEY idx_status (status),
    KEY idx_priority (priority),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PHASE 1.6: Insert Default Admin (if not exists)
-- ============================================================================
-- Default credentials for first login:
-- Username: admin
-- Password: password (bcrypt hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)

INSERT IGNORE INTO admins (id, username, email, password_hash) 
VALUES (1, 'admin', 'admin@hostel.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================================
-- VERIFICATION
-- ============================================================================
-- After applying this migration, verify using:
-- 
-- SHOW TABLES;
-- -- Should show 11 tables:
-- -- admins, alumni, complaints, fee_payments, fee_records, 
-- -- notifications, room_allocations, rooms, student_history, students, system_logs
--
-- DESCRIBE system_logs;
-- -- Should show: id, admin_id, action, entity_type, entity_id, description, 
-- --               old_values, new_values, ip_address, user_agent, created_at
--
-- DESCRIBE fee_records;
-- -- Should show: id, invoice_number, student_id, billing_month, billing_year, 
-- --               invoice_date, amount, additional_charges, discount, paid_amount,
-- --               due_date, payment_date, status, payment_method, transaction_ref, remarks,
-- --               created_at, updated_at
--
-- ============================================================================
