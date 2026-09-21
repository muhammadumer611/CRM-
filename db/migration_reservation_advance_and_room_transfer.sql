-- Preserve reservation advances when a reservation is converted to a student.
-- Apply after taking a database backup. This migration is safe to re-run on MariaDB.
DELIMITER //
CREATE PROCEDURE migrate_reservation_advance_tracking()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_payments' AND COLUMN_NAME = 'status'
    ) THEN
        ALTER TABLE reservation_payments ADD COLUMN status ENUM('Completed', 'Reversed') NOT NULL DEFAULT 'Completed' AFTER notes;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_payments' AND COLUMN_NAME = 'applied_to_student_id'
    ) THEN
        ALTER TABLE reservation_payments ADD COLUMN applied_to_student_id INT NULL AFTER status;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_payments' AND COLUMN_NAME = 'applied_at'
    ) THEN
        ALTER TABLE reservation_payments ADD COLUMN applied_at TIMESTAMP NULL AFTER applied_to_student_id;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_payments' AND INDEX_NAME = 'idx_reservation_payments_applied'
    ) THEN
        ALTER TABLE reservation_payments ADD KEY idx_reservation_payments_applied (applied_to_student_id, status);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_payments' AND CONSTRAINT_NAME = 'fk_reservation_payments_applied_student'
    ) THEN
        ALTER TABLE reservation_payments
            ADD CONSTRAINT fk_reservation_payments_applied_student
            FOREIGN KEY (applied_to_student_id) REFERENCES students(id) ON DELETE SET NULL;
    END IF;
END//
DELIMITER ;

CALL migrate_reservation_advance_tracking();
DROP PROCEDURE migrate_reservation_advance_tracking;
