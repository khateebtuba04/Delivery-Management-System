<?php
// admin/order_place.php
$page_title = "Place Order | QuickShip";
$required_role = "Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$success_msg = "";
$error_msg = "";

// Fetch customers and delivery partners for the dropdowns
try {
    $customers = $pdo->query("SELECT `id`, `name`, `contact_number`, `customer_type` FROM `customers` ORDER BY `name` ASC")->fetchAll();
    $partners = $pdo->query("SELECT `id`, `name`, `contact_number` FROM `delivery_partners` ORDER BY `name` ASC")->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Failed to load customers or delivery partners: " . $e->getMessage();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? '';
    $delivery_partner_id = $_POST['delivery_partner_id'] ?? '';
    $order_details = trim($_POST['order_details'] ?? '');

    // Validation
    if (empty($customer_id) || empty($delivery_partner_id) || empty($order_details)) {
        $error_msg = "Please select a customer, a delivery partner, and enter order details.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insert into orders table
            $stmtInsertOrder = $pdo->prepare("
                INSERT INTO `orders` (`customer_id`, `delivery_partner_id`, `order_details`, `status`) 
                VALUES (?, ?, ?, 'Order Preparing')
            ");
            $stmtInsertOrder->execute([$customer_id, $delivery_partner_id, $order_details]);
            
            $order_id = $pdo->lastInsertId();

            // Insert initial history record into order_status_history table
            $stmtInsertHistory = $pdo->prepare("
                INSERT INTO `order_status_history` (`order_id`, `status`) 
                VALUES (?, 'Order Preparing')
            ");
            $stmtInsertHistory->execute([$order_id]);

            $pdo->commit();
            $success_msg = "Order #" . $order_id . " placed successfully and assigned to delivery partner.";
            $order_details = ""; // Reset details field
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Failed to place order: " . $e->getMessage();
        }
    }
}
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-cart-plus text-success me-2"></i>Create Delivery Order</h2>
        <p class="text-muted">Register a new package or food delivery request for a client.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card main-card">
            <div class="card-header">
                <h5 class="mb-0 text-success"><i class="fa-solid fa-file-invoice me-2"></i>Order Placement Form</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i>
                        <div>
                            <?php echo htmlspecialchars($success_msg); ?> 
                            <a href="order_track.php" class="alert-link ms-2">Track Orders</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        <div><?php echo htmlspecialchars($error_msg); ?></div>
                    </div>
                <?php endif; ?>

                <form action="order_place.php" method="POST" class="needs-validation" novalidate>
                    <!-- Customer Dropdown -->
                    <div class="mb-3">
                        <label for="customer_id" class="form-label form-label-custom">Select Customer</label>
                        <select class="form-select form-control-custom" id="customer_id" name="customer_id" required>
                            <option value="" disabled selected>-- Choose Customer --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['name']); ?> 
                                    (<?php echo htmlspecialchars($c['customer_type']); ?> - <?php echo htmlspecialchars($c['contact_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>

                    <!-- Delivery Partner Dropdown -->
                    <div class="mb-3">
                        <label for="delivery_partner_id" class="form-label form-label-custom">Assign Delivery Partner</label>
                        <select class="form-select form-control-custom" id="delivery_partner_id" name="delivery_partner_id" required>
                            <option value="" disabled selected>-- Choose Partner --</option>
                            <?php foreach ($partners as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name']); ?> 
                                    (<?php echo htmlspecialchars($p['contact_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please assign a delivery partner.</div>
                    </div>

                    <!-- Order Details -->
                    <div class="mb-4">
                        <label for="order_details" class="form-label form-label-custom">Order Details / Package Contents</label>
                        <textarea class="form-control form-control-custom" id="order_details" name="order_details" rows="4" 
                                  placeholder="Describe the items, weight, instructions, etc..." required><?php echo isset($order_details) ? htmlspecialchars($order_details) : ''; ?></textarea>
                        <div class="invalid-feedback">Please enter the details of the order.</div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-custom btn-custom-primary">
                            <i class="fa-solid fa-paper-plane me-1"></i> Place & Dispatch Order
                        </button>
                        <a href="dashboard.php" class="btn btn-custom btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
