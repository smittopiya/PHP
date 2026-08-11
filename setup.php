<?php
/**
 * setup.php — Run once to create the database and all tables.
 * Visit: http://localhost/milk-management/setup.php
 */
$host = 'localhost'; $user = 'root'; $pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS smart_dairy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE smart_dairy");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS routes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        route_name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        route_id INT,
        route_sequence INT DEFAULT 0,
        name VARCHAR(150) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        milk_type ENUM('Cow','Buffalo','Mixed') DEFAULT 'Cow',
        default_qty DECIMAL(6,2) DEFAULT 1.00,
        default_rate DECIMAL(8,2) DEFAULT 50.00,
        status ENUM('Active','Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS milk_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        entry_date DATE NOT NULL,
        shift ENUM('Morning','Evening') DEFAULT 'Morning',
        quantity DECIMAL(6,2) NOT NULL DEFAULT 0,
        rate_per_liter DECIMAL(8,2) NOT NULL DEFAULT 0,
        is_absent TINYINT(1) DEFAULT 0,
        milk_type ENUM('Cow','Buffalo','Mixed') DEFAULT 'Cow',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_entry (customer_id, entry_date, shift),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(150) NOT NULL,
        unit VARCHAR(50) DEFAULT 'kg',
        default_price DECIMAL(10,2) DEFAULT 0,
        status ENUM('Active','Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS product_sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        product_id INT NOT NULL,
        sale_date DATE NOT NULL,
        quantity DECIMAL(8,2) NOT NULL,
        price_per_unit DECIMAL(10,2) NOT NULL,
        total_amount DECIMAL(12,2) GENERATED ALWAYS AS (quantity * price_per_unit) STORED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        payment_date DATE NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        payment_mode ENUM('Cash','UPI','Bank Transfer','Cheque') DEFAULT 'Cash',
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS monthly_bills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        bill_month TINYINT NOT NULL,
        bill_year YEAR NOT NULL,
        current_bill DECIMAL(12,2) DEFAULT 0,
        previous_balance DECIMAL(12,2) DEFAULT 0,
        total_paid DECIMAL(12,2) DEFAULT 0,
        final_due DECIMAL(12,2) DEFAULT 0,
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_bill (customer_id, bill_month, bill_year),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ");

    // Default settings
    $defaults = [
        ['dark_mode',       '0'],
        ['dairy_name',      'Smart Dairy'],
        ['owner_name',      'Owner'],
        ['phone',           ''],
        ['address',         ''],
        ['default_rate',    '50'],
        ['default_qty',     '1'],
        ['currency',        '₹'],
    ];
    $ins = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?,?)");
    foreach ($defaults as $d) $ins->execute($d);

    // Sample route
    $pdo->exec("INSERT IGNORE INTO routes (id, route_name, description) VALUES (1,'Main Route','Primary delivery route')");

    // Sample products
    $pdo->exec("INSERT IGNORE INTO products (product_name, unit, default_price) VALUES
        ('Ghee','kg',600),
        ('Paneer','kg',350),
        ('Curd','kg',80),
        ('Butter','kg',500),
        ('Cheese','kg',400)
    ");

    echo '<style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px}
    h2{color:#1B3A5C}.ok{color:green}.err{color:red}
    .btn{display:inline-block;padding:10px 24px;background:#1B3A5C;color:#fff;
    text-decoration:none;border-radius:8px;margin-top:16px}</style>';
    echo '<h2>✅ Smart Dairy — Database Setup Complete!</h2>';
    echo '<ul>';
    $tables = ['routes','customers','milk_entries','products','product_sales','payments','monthly_bills','settings'];
    foreach ($tables as $t) echo "<li class='ok'>✔ Table <b>$t</b> ready</li>";
    echo '</ul>';
    echo '<p class="ok">Default settings, route, and products seeded.</p>';
    echo '<a class="btn" href="index.php">→ Go to Dashboard</a>';

} catch (PDOException $e) {
    echo '<pre style="color:red">Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '<p>Make sure Laragon (MySQL) is running and root user has no password.</p>';
}
