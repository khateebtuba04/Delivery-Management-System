<?php
// config/db.php
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('VERCEL') || isset($_ENV['VERCEL'])) {
        // Vercel serverless containers only have writable access to /tmp
        ini_set('session.save_path', '/tmp');
    }
    session_start();
}

// Database credentials: use environment variables if deployed to cloud (Vercel, Render, Railway)
// otherwise fall back to standard local development values (XAMPP / WAMP)
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'delivery_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 1. Try connecting directly to the target database
    $dsn_with_db = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn_with_db, $user, $pass, $options);
} catch (\PDOException $e) {
    // 2. If target DB doesn't exist yet, try creating it (common on local MySQL)
    try {
        $dsn = "mysql:host=$host;port=$port;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$db`");
    } catch (\PDOException $inner) {
        // Output a graceful setup banner for cloud deployments instead of a harsh crash
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Setup Required - QuickShip Delivery</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
            <style>
                body { background: #0b0f19; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
                .setup-card { background: rgba(17, 24, 39, 0.95); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 2.5rem; max-width: 650px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); }
                .code-box { background: #030712; border: 1px solid #1f2937; border-radius: 8px; padding: 1rem; font-family: monospace; font-size: 0.9rem; color: #38bdf8; text-align: left; }
            </style>
        </head>
        <body>
            <div class="setup-card text-center">
                <div class="mb-3 text-warning">
                    <i class="fa-solid fa-triangle-exclamation fa-3x"></i>
                </div>
                <h3 class="fw-bold mb-2">Cloud Database Setup Required</h3>
                <p class="text-secondary mb-4">
                    Your Delivery Management System frontend is deployed on Vercel, but it needs a remote MySQL database to store users and deliveries.
                </p>
                <div class="text-start mb-4">
                    <h6 class="text-light fw-bold mb-2"><i class="fa-solid fa-sliders me-2"></i>Add Environment Variables in Vercel Dashboard:</h6>
                    <div class="code-box">
                        DB_HOST = your-cloud-mysql-host<br>
                        DB_PORT = 3306<br>
                        DB_USER = your_database_username<br>
                        DB_PASS = your_database_password<br>
                        DB_NAME = delivery_db
                    </div>
                </div>
                <div class="alert alert-danger text-start py-2 px-3 small">
                    <strong>Connection Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
                </div>
                <p class="text-muted small mb-0">Free cloud MySQL providers: TiDB Cloud Serverless, Aiven, or Railway.</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Auto-seed database tables if they do not exist
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
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
    }

    // Auto-seed default admin if users table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $admin_user = 'admin';
        $admin_pass = password_hash('123', PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("INSERT INTO `users` (username, password, role) VALUES (?, ?, 'Admin')");
        $stmtInsert->execute([$admin_user, $admin_pass]);
    }
} catch (\Exception $ex) {
    // If auto-schema fails due to restricted privileges, continue gracefully
}
?>
