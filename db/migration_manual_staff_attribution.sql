-- Additive migration for manually entered operator attribution.
-- Existing financial and operational rows are preserved.

ALTER TABLE students
    ADD COLUMN added_by_name VARCHAR(100) NULL AFTER monthly_fee,
    ADD COLUMN added_at TIMESTAMP NULL AFTER added_by_name;

ALTER TABLE room_allocations
    ADD COLUMN allocated_by_name VARCHAR(100) NULL AFTER remarks;

ALTER TABLE reservations
    ADD COLUMN reserved_by_name VARCHAR(100) NULL AFTER notes,
    ADD COLUMN converted_by_name VARCHAR(100) NULL AFTER converted_at,
    ADD COLUMN cancelled_by_name VARCHAR(100) NULL AFTER cancelled_at;

ALTER TABLE fee_payments
    ADD COLUMN received_by_name VARCHAR(100) NULL AFTER received_by_admin,
    ADD COLUMN received_at TIMESTAMP NULL AFTER received_by_name;

ALTER TABLE security_deposits
    ADD COLUMN processed_by_name VARCHAR(100) NULL AFTER status,
    ADD COLUMN processed_at TIMESTAMP NULL AFTER processed_by_name;

ALTER TABLE security_deposit_transactions
    ADD COLUMN processed_by_name VARCHAR(100) NULL AFTER created_by_admin,
    ADD COLUMN processed_at TIMESTAMP NULL AFTER processed_by_name;

ALTER TABLE alumni
    ADD COLUMN checked_out_by_name VARCHAR(100) NULL AFTER remarks,
    ADD COLUMN checked_out_at TIMESTAMP NULL AFTER checked_out_by_name,
    ADD COLUMN processed_by_name VARCHAR(100) NULL AFTER checked_out_at,
    ADD COLUMN processed_at TIMESTAMP NULL AFTER processed_by_name;
