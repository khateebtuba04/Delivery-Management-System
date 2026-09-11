<?php
// config/db.php
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('VERCEL') || isset($_ENV['VERCEL'])) {
        ini_set('session.save_path', '/tmp');
    }
    session_start();
}

$host = getenv('DB_HOST');
$db   = getenv('DB_NAME') ?: 'delivery_db';
$user = getenv('DB_USER');
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : null);
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;

// 1. Try MySQL if DB_HOST is explicitly provided (or local development)
if ($host && $host !== 'localhost' || (!getenv('VERCEL') && !isset($_ENV['VERCEL']) && $host)) {
    $mysqlHost = $host ?: 'localhost';
    $mysqlUser = $user ?: 'root';
    $mysqlPass = $pass !== null ? $pass : '';
    try {
        $dsn_with_db = "mysql:host=$mysqlHost;port=$port;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn_with_db, $mysqlUser, $mysqlPass, $options);
    } catch (\PDOException $e) {
        try {
            $dsn = "mysql:host=$mysqlHost;port=$port;charset=$charset";
            $pdo = new PDO($dsn, $mysqlUser, $mysqlPass, $options);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `$db`");
        } catch (\PDOException $inner) {
            $pdo = null;
        }
    }
}

// 2. Fallback to Embedded SQLite (Zero-config out of the box on Vercel)
if (!$pdo) {
    $sqlitePath = (getenv('VERCEL') || isset($_ENV['VERCEL'])) ? '/tmp/delivery.sqlite' : __DIR__ . '/delivery.sqlite';
    
    $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create SQLite schema
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `username` TEXT UNIQUE NOT NULL,
          `password` TEXT NOT NULL,
          `role` TEXT DEFAULT 'Admin',
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS `customers` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `name` TEXT NOT NULL,
          `address` TEXT NOT NULL,
          `customer_type` TEXT NOT NULL,
          `contact_number` TEXT NOT NULL,
          `username` TEXT UNIQUE NOT NULL,
          `password` TEXT NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS `delivery_partners` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `name` TEXT NOT NULL,
          `address` TEXT NOT NULL,
          `contact_number` TEXT NOT NULL,
          `username` TEXT UNIQUE NOT NULL,
          `password` TEXT NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS `orders` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `customer_id` INTEGER NOT NULL,
          `delivery_partner_id` INTEGER NOT NULL,
          `order_details` TEXT NOT NULL,
          `status` TEXT DEFAULT 'Order Preparing',
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS `order_status_history` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `order_id` INTEGER NOT NULL,
          `status` TEXT NOT NULL,
          `changed_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
} else {
    // MySQL table creation
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `username` VARCHAR(50) UNIQUE NOT NULL,
              `password` VARCHAR(255) NOT NULL,
              `role` VARCHAR(20) DEFAULT 'Admin',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `customers` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `name` VARCHAR(100) NOT NULL,
              `address` TEXT NOT NULL,
              `customer_type` ENUM('Food', 'Snacks', 'Stationery', 'Grocery') NOT NULL,
              `contact_number` VARCHAR(20) NOT NULL,
              `username` VARCHAR(50) UNIQUE NOT NULL,
              `password` VARCHAR(255) NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `delivery_partners` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `name` VARCHAR(100) NOT NULL,
              `address` TEXT NOT NULL,
              `contact_number` VARCHAR(20) NOT NULL,
              `username` VARCHAR(50) UNIQUE NOT NULL,
              `password` VARCHAR(255) NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `orders` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `customer_id` INT NOT NULL,
              `delivery_partner_id` INT NOT NULL,
              `order_details` TEXT NOT NULL,
              `status` ENUM('Order Preparing','Order Ready','Pick Order','In Travel','Order Reached','Order Delivered') DEFAULT 'Order Preparing',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
              FOREIGN KEY (`delivery_partner_id`) REFERENCES `delivery_partners`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `order_status_history` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `order_id` INT NOT NULL,
              `status` VARCHAR(50) NOT NULL,
              `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Exception $ex) {}
}

// 3. Auto-seed default accounts and demo data if database is fresh
try {
    $count = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    if ($count == 0) {
        // Seed default Admin (admin / 123)
        $admin_pass = password_hash('123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `role`) VALUES (?, ?, 'Admin')");
        $stmt->execute(['admin', $admin_pass]);
        
        // Seed demo Customer (customer1 / 123)
        $cust_pass = password_hash('123', PASSWORD_DEFAULT);
        $stmtCust = $pdo->prepare("INSERT INTO `customers` (`name`, `address`, `customer_type`, `contact_number`, `username`, `password`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtCust->execute(['John Doe', '123 Baker Street, Downtown', 'Food', '+1 555-0199', 'customer1', $cust_pass]);
        $custId = $pdo->lastInsertId();
        
        // Seed demo Delivery Partner (partner1 / 123)
        $part_pass = password_hash('123', PASSWORD_DEFAULT);
        $stmtPart = $pdo->prepare("INSERT INTO `delivery_partners` (`name`, `address`, `contact_number`, `username`, `password`) VALUES (?, ?, ?, ?, ?)");
        $stmtPart->execute(['Alex Rider', '45 Speed Avenue, Metro', '+1 555-0188', 'partner1', $part_pass]);
        $partId = $pdo->lastInsertId();
        
        // Seed demo Order
        $stmtOrd = $pdo->prepare("INSERT INTO `orders` (`customer_id`, `delivery_partner_id`, `order_details`, `status`) VALUES (?, ?, ?, ?)");
        $stmtOrd->execute([$custId, $partId, '2x Gourmet Meal Combo, 1x Fresh Juice', 'Order Preparing']);
        $ordId = $pdo->lastInsertId();
        
        // Seed demo Order History
        $stmtHist = $pdo->prepare("INSERT INTO `order_status_history` (`order_id`, `status`) VALUES (?, ?)");
        $stmtHist->execute([$ordId, 'Order Preparing']);
    }
} catch (\Exception $ex) {
    // If already seeded or table exists
}
?>
