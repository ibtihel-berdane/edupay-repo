CREATE DATABASE IF NOT EXISTS edupay_plus
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE edupay_plus;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_code CHAR(6) UNIQUE NOT NULL,
    matricule VARCHAR(50) UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    full_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'accounting_agent') DEFAULT 'accounting_agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CHECK (admin_code REGEXP '^ADM[0-9]{3}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE admins ADD COLUMN IF NOT EXISTS matricule VARCHAR(50) UNIQUE AFTER admin_code;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER matricule;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER first_name;
UPDATE admins SET matricule = admin_code WHERE matricule IS NULL OR matricule = '';
UPDATE admins SET first_name = 'Admin', last_name = 'Principal' WHERE admin_code = 'ADM001' AND (first_name IS NULL OR last_name IS NULL);

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code CHAR(12) UNIQUE NOT NULL,
    matricule VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    program VARCHAR(100) NOT NULL,
    level VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    phone VARCHAR(30),
    stipend_enabled TINYINT(1) NOT NULL DEFAULT 0,
    password VARCHAR(255) DEFAULT NULL,
    account_status ENUM('not_registered', 'registered') DEFAULT 'not_registered',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    CHECK (student_code REGEXP '^20[0-9]{2}[0-9]{8}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_name ENUM('registration', 'transport', 'housing') NOT NULL,
    program VARCHAR(100) NOT NULL,
    level VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE DEFAULT NULL,
    fee_type ENUM('obligatory', 'optional') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fee_scope (fee_name, program, level, academic_year),
    CHECK (
        (fee_name = 'registration' AND fee_type = 'obligatory') OR
        (fee_name IN ('transport', 'housing') AND fee_type = 'optional')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE fees ADD COLUMN IF NOT EXISTS due_date DATE DEFAULT NULL AFTER amount;
UPDATE fees SET due_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE due_date IS NULL;

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('unpaid', 'partially_paid', 'paid', 'late') DEFAULT 'unpaid',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    fee_id INT NOT NULL,
    fee_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    fee_type ENUM('obligatory', 'optional') NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_id) REFERENCES fees(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_reference VARCHAR(100) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NOT NULL,
    invoice_item_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'validated', 'rejected') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validated_by INT DEFAULT NULL,
    validated_at TIMESTAMP NULL,
    rejection_reason TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
    FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(id) ON DELETE SET NULL,
    FOREIGN KEY (validated_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    payment_id INT NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_receipt_payment (payment_id),
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_type ENUM('admin', 'student') NOT NULL,
    recipient_id INT NOT NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_notifications_recipient (recipient_type, recipient_id, is_read, created_at),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_stipends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    stipend_month DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    level_group ENUM('licence', 'master') NOT NULL,
    status ENUM('disbursed') NOT NULL DEFAULT 'disbursed',
    disbursed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_stipend_month (student_id, stipend_month),
    INDEX idx_stipends_month (stipend_month),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admins (admin_code, matricule, first_name, last_name, full_name, password, role)
SELECT 'ADM001', 'ADM001', 'Admin', 'Principal', 'Default Admin', '$2y$10$1C31nJcH9WCbrqU4Uo6psuN12f24LY6HOdo2VHpVT/wcAGeK7jnlW', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE admin_code = 'ADM001');
