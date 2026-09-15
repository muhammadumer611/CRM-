-- ============================================================
-- HMS Phase 3+ Migration: student monthly_fee, guardian_address
-- Safe idempotent: only adds columns if they don't exist.
-- ============================================================

-- Add monthly_fee to students (student-specific, set at onboarding)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hms_db' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'monthly_fee'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE students ADD COLUMN monthly_fee DECIMAL(10,2) NULL DEFAULT NULL AFTER status',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add guardian_address to students
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hms_db' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'guardian_address'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE students ADD COLUMN guardian_address TEXT NULL DEFAULT NULL AFTER relation',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add resident metadata for student onboarding (resident type + conditional references)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hms_db' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'resident_type'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE students ADD COLUMN resident_type VARCHAR(50) NOT NULL DEFAULT "Student" AFTER relation',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hms_db' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'college_university'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE students ADD COLUMN college_university VARCHAR(255) NULL AFTER resident_type',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hms_db' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'job_workplace'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE students ADD COLUMN job_workplace VARCHAR(255) NULL AFTER college_university',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hms_db' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'vehicle_number'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE students ADD COLUMN vehicle_number VARCHAR(50) NULL AFTER job_workplace',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hms_db' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'vehicle_type'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE students ADD COLUMN vehicle_type VARCHAR(80) NULL AFTER vehicle_number',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'hms_db' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'note'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE students ADD COLUMN note TEXT NULL AFTER vehicle_type',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE students MODIFY COLUMN guardian_cnic VARCHAR(15) NULL;
UPDATE students SET resident_type = 'Student' WHERE resident_type IS NULL OR resident_type = '';

-- Verify fee_payments has all required payment_method enum values (JazzCash, EasyPaisa)
-- The existing enum is: 'Cash','Bank Transfer','Online','Card','Other'
-- Extend it to include JazzCash and EasyPaisa
ALTER TABLE fee_payments MODIFY COLUMN payment_method ENUM(
    'Cash','Bank Transfer','Online','Card','JazzCash','EasyPaisa','Other'
) NOT NULL;

-- Ensure fee_records has invoice_number auto-generated (already has it, just verify)
-- No changes needed to fee_records schema

SELECT 'Migration complete.' AS status;
