<?php
// config/db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db   = 'delivery_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Connect without db first to check/create if needed (or assume it was created by schema.sql)
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `$db`");
    
    // Now establish connection with db in DSN to be safe
    $dsn_with_db = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn_with_db, $user, $pass, $options);

    // Auto-seed default admin if table users exists and is empty
    // First, check if users table exists
    $tableExists = false;
    try {
        $result = $pdo->query("SELECT 1 FROM `users` LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        // Table doesn't exist yet, we will let schema.sql handle it, or we can create it dynamically
    }

    if ($tableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
        $count = $stmt->fetchColumn();
        if ($count == 0) {
            // Seed default admin
            $admin_user = 'admin';
            $admin_pass = password_hash('123', PASSWORD_DEFAULT);
            $stmtInsert = $pdo->prepare("INSERT INTO `users` (username, password, role) VALUES (?, ?, 'Admin')");
            $stmtInsert->execute([$admin_user, $admin_pass]);
        }
    }

} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
