ALTER TABLE system_logs
    ADD COLUMN IF NOT EXISTS entity_type VARCHAR(100) NULL AFTER action,
    ADD COLUMN IF NOT EXISTS entity_id INT NULL AFTER entity_type,
    ADD COLUMN IF NOT EXISTS old_values JSON NULL AFTER description,
    ADD COLUMN IF NOT EXISTS new_values JSON NULL AFTER old_values,
    ADD COLUMN IF NOT EXISTS user_agent VARCHAR(255) NULL AFTER ip_address;

ALTER TABLE fee_payments
    MODIFY payment_method ENUM('Cash', 'Bank Transfer', 'Online', 'Card', 'Other') NOT NULL,
    ADD COLUMN IF NOT EXISTS status ENUM('Completed', 'Reversed') NOT NULL DEFAULT 'Completed' AFTER received_by_admin,
    ADD COLUMN IF NOT EXISTS reversed_by_admin INT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS reversed_at TIMESTAMP NULL AFTER reversed_by_admin,
    ADD COLUMN IF NOT EXISTS reversal_reason TEXT NULL AFTER reversed_at,
    ADD CONSTRAINT fk_fee_payments_reversed_by_admin FOREIGN KEY (reversed_by_admin) REFERENCES admins(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS student_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    source_type VARCHAR(50) NOT NULL,
    source_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reason TEXT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    KEY idx_student_credits_student (student_id)
);

CREATE TABLE IF NOT EXISTS refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    refund_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    refund_date DATE NOT NULL,
    reason TEXT NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Online', 'Card', 'Other') NOT NULL,
    reference_number VARCHAR(100) NULL,
    processed_by_admin INT NULL,
    status ENUM('Pending','Processed','Rejected','Cancelled') NOT NULL DEFAULT 'Processed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by_admin) REFERENCES admins(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS security_deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    original_amount DECIMAL(12,2) NOT NULL,
    remaining_amount DECIMAL(12,2) NOT NULL,
    status ENUM('HELD','ADJUSTED','PARTIALLY_REFUNDED','REFUNDED','FORFEITED') NOT NULL DEFAULT 'HELD',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    KEY idx_security_deposits_student (student_id)
);

CREATE TABLE IF NOT EXISTS security_deposit_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    security_deposit_id INT NOT NULL,
    transaction_type ENUM('ADJUSTMENT','REFUND','FORFEIT','HOLD') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason TEXT NOT NULL,
    reference_number VARCHAR(100) NULL,
    created_by_admin INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (security_deposit_id) REFERENCES security_deposits(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_admin) REFERENCES admins(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS additional_charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    charge_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    charge_date DATE NOT NULL,
    reference_number VARCHAR(100) NULL,
    created_by_admin INT NULL,
    status ENUM('Pending','Paid','Partial','Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_admin) REFERENCES admins(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    discount_type ENUM('PERCENTAGE','FIXED') NOT NULL,
    discount_value DECIMAL(12,2) NOT NULL,
    reason TEXT NOT NULL,
    created_by_admin INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES fee_records(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_admin) REFERENCES admins(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS late_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    grace_period_days INT NOT NULL DEFAULT 0,
    fee_type ENUM('FIXED','PERCENTAGE') NOT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES fee_records(id) ON DELETE CASCADE
);
