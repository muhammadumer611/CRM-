
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id_str VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    cnic VARCHAR(15) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    blood_group VARCHAR(5) NULL,
    address TEXT NOT NULL,
    profile_img VARCHAR(255) NULL,
    cnic_front VARCHAR(255) NULL,
    cnic_back VARCHAR(255) NULL,
    guardian_name VARCHAR(100) NOT NULL,
    guardian_phone VARCHAR(20) NOT NULL,
    guardian_cnic VARCHAR(15) NOT NULL,
    relation VARCHAR(50) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL,
    block VARCHAR(50) NOT NULL,
    floor VARCHAR(20) NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    total_beds INT NOT NULL,
    occupied_beds INT DEFAULT 0,
    monthly_fee DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2) NOT NULL,
    status ENUM('Available', 'Partially Occupied', 'Occupied', 'Disabled') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (room_number, block)
);

CREATE TABLE room_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    bed_number INT NOT NULL,
    joining_date DATE NOT NULL,
    leaving_date DATE NULL,
    status ENUM('Active', 'Closed') DEFAULT 'Active',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active_bed_flag VARCHAR(64) AS (IF(status = 'Active', CONCAT(room_id, '_', bed_number), NULL)) VIRTUAL,
    active_student_flag VARCHAR(64) AS (IF(status = 'Active', CAST(student_id AS CHAR), NULL)) VIRTUAL,
    UNIQUE KEY uk_active_room_bed (active_bed_flag),
    UNIQUE KEY uk_active_student (active_student_flag),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS room_occupants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_allocation_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    cnic VARCHAR(15) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    relation VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_allocation_id) REFERENCES room_allocations(id) ON DELETE CASCADE,
    UNIQUE KEY uk_room_occupant_cnic (cnic),
    KEY idx_room_occupants_allocation (room_allocation_id)
);

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    cnic VARCHAR(15) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    district VARCHAR(80) NOT NULL,
    room_id INT NOT NULL,
    bed_number INT NOT NULL,
    reservation_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reservation_date DATE NOT NULL,
    expected_arrival_date DATE NULL,
    status ENUM('PENDING', 'CONFIRMED', 'ARRIVED', 'CANCELLED', 'EXPIRED') NOT NULL DEFAULT 'PENDING',
    notes TEXT NULL,
    reserved_by_name VARCHAR(100) NULL,
    converted_student_id INT NULL,
    converted_at TIMESTAMP NULL,
    converted_by_name VARCHAR(100) NULL,
    cancelled_at TIMESTAMP NULL,
    cancelled_by_name VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (converted_student_id) REFERENCES students(id) ON DELETE SET NULL,
    KEY idx_reservations_room_bed (room_id, bed_number),
    KEY idx_reservations_status (status),
    KEY idx_reservations_cnic (cnic),
    KEY idx_reservations_date (reservation_date)
);

CREATE TABLE IF NOT EXISTS reservation_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Online', 'Card', 'Other') NOT NULL DEFAULT 'Cash',
    transaction_ref VARCHAR(100) NOT NULL,
    notes TEXT NULL,
    status ENUM('Completed', 'Reversed') NOT NULL DEFAULT 'Completed',
    applied_to_student_id INT NULL,
    applied_at TIMESTAMP NULL,
    created_by_admin INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (applied_to_student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_admin) REFERENCES admins(id) ON DELETE SET NULL,
    UNIQUE KEY uk_reservation_payment_ref (transaction_ref),
    KEY idx_reservation_payments_reservation (reservation_id, payment_date),
    KEY idx_reservation_payments_applied (applied_to_student_id, status)
);

CREATE TABLE fee_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NULL,
    student_id INT NOT NULL,
    billing_month INT NOT NULL,
    billing_year INT NOT NULL,
    invoice_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    amount DECIMAL(10,2) NOT NULL,
    additional_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    due_date DATE NOT NULL,
    payment_date DATE NULL,
    status ENUM('Paid', 'Pending', 'Partial', 'Overdue') DEFAULT 'Pending',
    payment_method VARCHAR(50) NULL,
    transaction_ref VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    UNIQUE KEY (student_id, billing_month, billing_year),
    UNIQUE KEY (invoice_number),
    KEY idx_fee_records_student_period (student_id, billing_year, billing_month),
    KEY idx_fee_records_status_due (status, due_date)
);

CREATE TABLE fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    receipt_number VARCHAR(50) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Online', 'Card', 'Other') NOT NULL,
    transaction_ref VARCHAR(100) NULL,
    remarks TEXT NULL,
    received_by_admin INT NULL,
    status ENUM('Completed','Reversed') NOT NULL DEFAULT 'Completed',
    reversed_by_admin INT NULL,
    reversed_at TIMESTAMP NULL,
    reversal_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES fee_records(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by_admin) REFERENCES admins(id) ON DELETE SET NULL,
    FOREIGN KEY (reversed_by_admin) REFERENCES admins(id) ON DELETE SET NULL,
    KEY idx_fee_payments_invoice (invoice_id, payment_date),
    KEY idx_fee_payments_date (payment_date),
    KEY idx_fee_payments_receipt_number (receipt_number)
);

CREATE TABLE IF NOT EXISTS payment_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    invoice_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES fee_payments(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES fee_records(id) ON DELETE CASCADE,
    KEY idx_payment_allocations_payment (payment_id),
    KEY idx_payment_allocations_invoice (invoice_id)
);

ALTER TABLE fee_records
    ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(50) NULL AFTER id,
    ADD COLUMN IF NOT EXISTS additional_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount,
    ADD COLUMN IF NOT EXISTS discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER additional_charges,
    ADD COLUMN IF NOT EXISTS invoice_date DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER billing_year;

ALTER TABLE fee_records
    ADD UNIQUE KEY IF NOT EXISTS uk_fee_invoice_number (invoice_number);

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
);

CREATE TABLE student_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NULL,
    event_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    performed_by_admin INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by_admin) REFERENCES admins(id) ON DELETE SET NULL
);

CREATE TABLE alumni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_student_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    cnic VARCHAR(15) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    guardian_info JSON NOT NULL,
    previous_room VARCHAR(50) NULL,
    previous_bed INT NULL,
    joining_date DATE NULL,
    leaving_date DATE NULL,
    leaving_reason TEXT NULL,
    final_fee_status VARCHAR(50) NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    description TEXT NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- Insert a default admin account (username: admin, password: password)
INSERT INTO admins (username, email, password_hash) VALUES ('admin', 'admin@hostel.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
