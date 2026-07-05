<?php
// partner/order_update.php
require_once __DIR__ . '/../config/db.php';

// Authentication & Role enforcement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Delivery Partner') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$partner_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) && is_numeric($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$new_status = $_POST['new_status'] ?? '';

$status_sequence = [
    'Order Preparing',
    'Order Ready',
    'Pick Order',
    'In Travel',
    'Order Reached',
    'Order Delivered'
];

if ($order_id <= 0 || empty($new_status)) {
    die("Invalid request parameters.");
}

try {
    // 1. Fetch order details to verify existence and ownership
    $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE `id` = ? AND `delivery_partner_id` = ?");
    $stmt->execute([$order_id, $partner_id]);
    $order = $stmt->fetch();

    if (!$order) {
        die("Order not found or not assigned to you.");
    }

    $current_status = $order['status'];
    $current_idx = array_search($current_status, $status_sequence);
    
    // Validate sequential update
    if ($current_idx === false) {
        die("Invalid current order status in database.");
    }

    // Is it already delivered?
    if ($current_idx >= count($status_sequence) - 1) {
        die("Order is already fully delivered.");
    }

    // Check if new status is exactly the next step
    $expected_next_status = $status_sequence[$current_idx + 1];
    
    if ($new_status !== $expected_next_status) {
        die("Invalid status transition. Statuses must be updated sequentially.");
    }

    // 2. Perform sequential update in transaction
    $pdo->beginTransaction();

    // Update order status
    $stmtUpdate = $pdo->prepare("UPDATE `orders` SET `status` = ? WHERE `id` = ?");
    $stmtUpdate->execute([$new_status, $order_id]);

    // Insert history checkpoint
    $stmtHistory = $pdo->prepare("INSERT INTO `order_status_history` (`order_id`, `status`) VALUES (?, ?)");
    $stmtHistory->execute([$order_id, $new_status]);

    $pdo->commit();
    
    // Redirect back with success flag
    header("Location: dashboard.php?success=1");
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Operation failed due to a database error: " . $e->getMessage());
}
?>
