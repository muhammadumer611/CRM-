
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
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT
);

CREATE TABLE fee_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    billing_month INT NOT NULL,
    billing_year INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    due_date DATE NOT NULL,
    payment_date DATE NULL,
    status ENUM('Paid', 'Pending', 'Partial', 'Overdue') DEFAULT 'Pending',
    payment_method VARCHAR(50) NULL,
    transaction_ref VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    UNIQUE KEY (student_id, billing_month, billing_year)
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
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- Insert a default admin account (username: admin, password: password)
INSERT INTO admins (username, email, password_hash) VALUES ('admin', 'admin@hostel.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
