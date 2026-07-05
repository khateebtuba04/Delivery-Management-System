<?php
// customer/dashboard.php
$page_title = "Customer Portal | QuickShip";
$required_role = "Customer";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['username'];

$total_orders = 0;
$preparing_orders = 0;
$delivered_orders = 0;
$orders = [];
$error_msg = "";

try {
    // Count total orders placed by this customer
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `customer_id` = ?");
    $stmtCount->execute([$customer_id]);
    $total_orders = $stmtCount->fetchColumn();

    // Count pending preparing orders
    $stmtPrep = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `customer_id` = ? AND `status` != 'Order Delivered'");
    $stmtPrep->execute([$customer_id]);
    $preparing_orders = $stmtPrep->fetchColumn();

    // Count completed orders
    $stmtDelivered = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `customer_id` = ? AND `status` = 'Order Delivered'");
    $stmtDelivered->execute([$customer_id]);
    $delivered_orders = $stmtDelivered->fetchColumn();

    // Fetch all customer orders
    $stmtOrders = $pdo->prepare("
        SELECT o.*, p.name AS partner_name, p.contact_number AS partner_phone 
        FROM `orders` o 
        JOIN `delivery_partners` p ON o.delivery_partner_id = p.id
        WHERE o.customer_id = ?
        ORDER BY o.id DESC
    ");
    $stmtOrders->execute([$customer_id]);
    $orders = $stmtOrders->fetchAll();

} catch (PDOException $e) {
    $error_msg = "Failed to load dashboard data: " . $e->getMessage();
}
?>

<div class="row mb-4">
    <div class="col d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-house text-success me-2"></i>Welcome Back, <?php echo htmlspecialchars($customer_name); ?></h2>
            <p class="text-muted">Place new orders or track your packages in real time.</p>
        </div>
        <a href="order_place.php" class="btn btn-success btn-custom d-flex align-items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i> Place New Order
        </a>
    </div>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <div><?php echo htmlspecialchars($error_msg); ?></div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-4 mb-5">
    <!-- Total Orders Placed -->
    <div class="col-md-4">
        <div class="card stat-card orders-card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Orders Placed</h5>
                    <div class="card-value"><?php echo $total_orders; ?></div>
                </div>
                <div class="stat-icon-wrapper bg-indigo-light">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ongoing Deliveries -->
    <div class="col-md-4">
        <div class="card stat-card travel-card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Ongoing Shipments</h5>
                    <div class="card-value"><?php echo $preparing_orders; ?></div>
                </div>
                <div class="stat-icon-wrapper bg-cyan-light">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Deliveries -->
    <div class="col-md-4">
        <div class="card stat-card delivered-card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Completed Orders</h5>
                    <div class="card-value"><?php echo $delivered_orders; ?></div>
                </div>
                <div class="stat-icon-wrapper bg-emerald-light">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer Orders History -->
<div class="card main-card">
    <div class="card-header">
        <h5 class="mb-0 text-success"><i class="fa-solid fa-history me-2"></i>Your Order History</h5>
    </div>
    <div class="card-body">
        <?php if (count($orders) > 0): ?>
            <div class="table-responsive table-responsive-custom">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Details / Items</th>
                            <th>Assigned Driver</th>
                            <th>Status</th>
                            <th>Date Placed</th>
                            <th style="width: 130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="fw-bold text-success font-monospace">#<?php echo $o['id']; ?></td>
                                <td>
                                    <div class="text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($o['order_details']); ?>">
                                        <?php echo htmlspecialchars($o['order_details']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($o['partner_name']); ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($o['partner_phone']); ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = 'bg-secondary';
                                    if ($o['status'] === 'Order Preparing') $badge_class = 'bg-warning text-dark';
                                    elseif ($o['status'] === 'Order Ready') $badge_class = 'bg-info text-dark';
                                    elseif ($o['status'] === 'Pick Order') $badge_class = 'bg-primary';
                                    elseif ($o['status'] === 'In Travel') $badge_class = 'bg-primary bg-gradient';
                                    elseif ($o['status'] === 'Order Reached') $badge_class = 'bg-info';
                                    elseif ($o['status'] === 'Order Delivered') $badge_class = 'bg-success';
                                    ?>
                                    <span class="badge badge-custom <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($o['status']); ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.85rem;" class="text-muted">
                                    <?php echo date("M d, Y h:i A", strtotime($o['created_at'])); ?>
                                </td>
                                <td>
                                    <a href="order_track.php?order_id=<?php echo $o['id']; ?>" class="btn btn-sm btn-success rounded-pill px-3">
                                        <i class="fa-solid fa-location-crosshairs me-1"></i> Track
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fa-regular fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-2">You haven't placed any orders yet!</p>
                <a href="order_place.php" class="btn btn-success btn-sm rounded-pill px-3 mt-1">
                    Place Your First Order
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
