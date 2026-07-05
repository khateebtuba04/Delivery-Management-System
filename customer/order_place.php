<?php
// customer/order_place.php
$page_title = "Place New Order | QuickShip";
$required_role = "Customer";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$success_msg = "";
$error_msg = "";

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['username'];

// Fetch available delivery partners for assignment
try {
    $partners = $pdo->query("SELECT `id`, `name`, `contact_number` FROM `delivery_partners` ORDER BY `name` ASC")->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Failed to load delivery partners: " . $e->getMessage();
}

// Handle Order Placement Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_partner_id = $_POST['delivery_partner_id'] ?? '';
    $order_details = trim($_POST['order_details'] ?? '');

    if (empty($delivery_partner_id) || empty($order_details)) {
        $error_msg = "Please select a delivery partner and enter your package details.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insert order record (Default status is 'Order Preparing')
            $stmtInsert = $pdo->prepare("
                INSERT INTO `orders` (`customer_id`, `delivery_partner_id`, `order_details`, `status`) 
                VALUES (?, ?, ?, 'Order Preparing')
            ");
            $stmtInsert->execute([$customer_id, $delivery_partner_id, $order_details]);
            
            $order_id = $pdo->lastInsertId();

            // Insert initial status milestone in history
            $stmtHistory = $pdo->prepare("
                INSERT INTO `order_status_history` (`order_id`, `status`) 
                VALUES (?, 'Order Preparing')
            ");
            $stmtHistory->execute([$order_id]);

            $pdo->commit();
            $success_msg = "Your delivery order #" . $order_id . " has been placed successfully!";
            $order_details = ""; // Reset
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Failed to submit order: " . $e->getMessage();
        }
    }
}
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-cart-shopping text-success me-2"></i>Place New Delivery Order</h2>
        <p class="text-muted">Fill out the delivery package specifications and request shipment.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card main-card">
            <div class="card-header">
                <h5 class="mb-0 text-success"><i class="fa-solid fa-clipboard-check me-2"></i>New Order Request</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i>
                        <div>
                            <?php echo htmlspecialchars($success_msg); ?> 
                            <a href="dashboard.php" class="alert-link ms-2">Back to Dashboard</a>
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
                    <!-- Customer Name (Read Only) -->
                    <div class="mb-3">
                        <label for="customer_name" class="form-label form-label-custom">Customer Name (Sender)</label>
                        <input type="text" class="form-control form-control-custom bg-light" id="customer_name" 
                               value="<?php echo htmlspecialchars($customer_name); ?>" readonly>
                        <div class="form-text">This profile name matches your logged-in customer account.</div>
                    </div>

                    <!-- Delivery Partner Dropdown -->
                    <div class="mb-3">
                        <label for="delivery_partner_id" class="form-label form-label-custom">Choose Delivery Partner</label>
                        <select class="form-select form-control-custom" id="delivery_partner_id" name="delivery_partner_id" required>
                            <option value="" disabled selected>-- Select Courier Service / Agent --</option>
                            <?php foreach ($partners as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name']); ?> 
                                    (<?php echo htmlspecialchars($p['contact_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please choose a delivery partner.</div>
                    </div>

                    <!-- Order Details -->
                    <div class="mb-4">
                        <label for="order_details" class="form-label form-label-custom">Package Details & Destination Address</label>
                        <textarea class="form-control form-control-custom" id="order_details" name="order_details" rows="4" 
                                  placeholder="Describe the items, receiver's contact info, special instructions, and destination details..." required><?php echo isset($order_details) ? htmlspecialchars($order_details) : ''; ?></textarea>
                        <div class="invalid-feedback">Please enter the details of the package.</div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-custom btn-custom-primary">
                            <i class="fa-solid fa-truck-fast me-1"></i> Place Order
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
