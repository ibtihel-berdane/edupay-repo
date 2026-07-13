<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'edupay_plus';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// Read database configuration from environment variables first, then fallback constants.
function db_config(string $key): string
{
    $envKey = 'EDUPAY_' . $key;
    $value = getenv($envKey);
    if ($value !== false) {
        return $value;
    }

    return match ($key) {
        'DB_HOST' => DB_HOST,
        'DB_NAME' => DB_NAME,
        'DB_USER' => DB_USER,
        'DB_PASS' => DB_PASS,
        'DB_CHARSET' => DB_CHARSET,
        default => '',
    };
}

function db(): PDO
{
    // Reuse one PDO connection per request so schema initialization and queries share state.
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = db_config('DB_HOST');
    $name = db_config('DB_NAME');
    $user = db_config('DB_USER');
    $pass = db_config('DB_PASS');
    $charset = db_config('DB_CHARSET');

    try {
        $server = new PDO("mysql:host={$host};charset={$charset}", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");

        $pdo = new PDO("mysql:host={$host};dbname={$name};charset={$charset}", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        initialize_database($pdo);
        seed_default_admin($pdo);

        return $pdo;
    } catch (PDOException $exception) {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'EduPay+ database connection failed: ' . $exception->getMessage() . PHP_EOL);
        } else {
            http_response_code(500);
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Database error</title>';
            echo '<style>body{font-family:Arial,sans-serif;background:#f5f7fb;color:#1f2937;padding:40px}.box{max-width:720px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px}</style>';
            echo '</head><body><div class="box"><h1>EduPay+ database connection failed</h1>';
            echo '<p>Check MySQL in XAMPP and the credentials in <code>config/database.php</code>.</p>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
        }
        exit(1);
    }
}

function initialize_database(PDO $pdo): void
{
    // Create or migrate all tables the application needs before any page queries them.
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $statements = [
        "CREATE TABLE IF NOT EXISTS admins (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS students (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS fees (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS invoices (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS invoice_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            fee_id INT NOT NULL,
            fee_name VARCHAR(100) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            fee_type ENUM('obligatory', 'optional') NOT NULL,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
            FOREIGN KEY (fee_id) REFERENCES fees(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS payments (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS receipts (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS notifications (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS student_stipends (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
    ensure_stipends_table_usable($pdo);

    ensure_column($pdo, 'payments', 'invoice_item_id', "ALTER TABLE payments ADD COLUMN invoice_item_id INT DEFAULT NULL AFTER invoice_id");
    ensure_column($pdo, 'fees', 'due_date', "ALTER TABLE fees ADD COLUMN due_date DATE DEFAULT NULL AFTER amount");
    ensure_column($pdo, 'students', 'stipend_enabled', "ALTER TABLE students ADD COLUMN stipend_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER phone");
    ensure_column($pdo, 'admins', 'matricule', "ALTER TABLE admins ADD COLUMN matricule VARCHAR(50) UNIQUE AFTER admin_code");
    ensure_column($pdo, 'admins', 'first_name', "ALTER TABLE admins ADD COLUMN first_name VARCHAR(100) AFTER matricule");
    ensure_column($pdo, 'admins', 'last_name', "ALTER TABLE admins ADD COLUMN last_name VARCHAR(100) AFTER first_name");
    ensure_index($pdo, 'fees', 'unique_fee_scope', "ALTER TABLE fees ADD UNIQUE KEY unique_fee_scope (fee_name, program, level, academic_year)");
    ensure_index($pdo, 'receipts', 'unique_receipt_payment', "ALTER TABLE receipts ADD UNIQUE KEY unique_receipt_payment (payment_id)");
    ensure_index($pdo, 'student_stipends', 'unique_student_stipend_month', "ALTER TABLE student_stipends ADD UNIQUE KEY unique_student_stipend_month (student_id, stipend_month)");

    $pdo->exec("UPDATE admins SET matricule = admin_code WHERE matricule IS NULL OR matricule = ''");
    $pdo->exec("UPDATE fees SET due_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE due_date IS NULL");
    $pdo->exec("UPDATE admins SET first_name = 'Admin', last_name = 'Principal' WHERE admin_code = 'ADM001' AND (first_name IS NULL OR last_name IS NULL)");

    $initialized = true;
}

function stipends_table_sql(): string
{
    // Dedicated DDL for the stipend table because it may need targeted repair.
    return "CREATE TABLE student_stipends (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

function recreate_stipends_table(PDO $pdo): void
{
    // Rebuild only the stipend table if MySQL reports a broken table stub.
    try {
        $pdo->exec('DROP TABLE IF EXISTS student_stipends');
    } catch (PDOException) {
        $pdo->exec('DROP TABLE student_stipends');
    }
    $pdo->exec(stipends_table_sql());
}

function ensure_stipends_table_usable(PDO $pdo): void
{
    // MySQL can keep orphaned metadata; a read probe catches that before stipend inserts fail.
    try {
        $pdo->query('SELECT 1 FROM student_stipends LIMIT 1');
    } catch (PDOException $exception) {
        if ((string)$exception->getCode() !== '42S02' && !str_contains($exception->getMessage(), '1932')) {
            throw $exception;
        }
        recreate_stipends_table($pdo);
    }
}

function ensure_column(PDO $pdo, string $table, string $column, string $alterSql): void
{
    // Lightweight migration helper for adding columns to existing installations.
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec($alterSql);
    }
}

function ensure_index(PDO $pdo, string $table, string $index, string $alterSql): void
{
    // Lightweight migration helper for adding indexes when historical data allows it.
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);
    if ((int)$stmt->fetchColumn() === 0) {
        try {
            $pdo->exec($alterSql);
        } catch (PDOException) {
            // Duplicate historical data can prevent adding a unique key; pages still validate before writes.
        }
    }
}

function seed_default_admin(PDO $pdo): void
{
    // First-run bootstrap account so the app is usable after schema creation.
    $count = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO admins (admin_code, matricule, first_name, last_name, full_name, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        'ADM001',
        'ADM001',
        'Admin',
        'Principal',
        'Default Admin',
        password_hash('admin123', PASSWORD_DEFAULT),
        'admin',
    ]);
}
