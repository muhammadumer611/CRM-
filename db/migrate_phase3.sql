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

-- Verify fee_payments has all required payment_method enum values (JazzCash, EasyPaisa)
-- The existing enum is: 'Cash','Bank Transfer','Online','Card','Other'
-- Extend it to include JazzCash and EasyPaisa
ALTER TABLE fee_payments MODIFY COLUMN payment_method ENUM(
    'Cash','Bank Transfer','Online','Card','JazzCash','EasyPaisa','Other'
) NOT NULL;

-- Ensure fee_records has invoice_number auto-generated (already has it, just verify)
-- No changes needed to fee_records schema

SELECT 'Migration complete.' AS status;
