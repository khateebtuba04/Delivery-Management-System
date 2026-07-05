<?php
// admin/dashboard.php
$page_title = "Admin Dashboard | QuickShip";
$required_role = "Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch stats counts
try {
    // 1. Customers
    $customers_count = $pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
    // 2. Delivery Partners
    $partners_count = $pdo->query("SELECT COUNT(*) FROM `delivery_partners`")->fetchColumn();
    // 3. Total Orders
    $orders_count = $pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    // 4. Preparing Orders
    $preparing_count = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Order Preparing'")->fetchColumn();
    // 5. Ready Orders
    $ready_count = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Order Ready'")->fetchColumn();
    // 6. Picked Orders
    $picked_count = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Pick Order'")->fetchColumn();
    // 7. Orders in Travel
    $travel_count = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'In Travel'")->fetchColumn();
    // 8. Orders Reached
    $reached_count = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Order Reached'")->fetchColumn();
    // 9. Delivered Orders
    $delivered_count = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Order Delivered'")->fetchColumn();

    // Fetch latest 5 orders for summary display
    $latest_orders_stmt = $pdo->query("
        SELECT o.*, c.name AS customer_name, p.name AS partner_name 
        FROM `orders` o 
        JOIN `customers` c ON o.customer_id = c.id
        JOIN `delivery_partners` p ON o.delivery_partner_id = p.id
        ORDER BY o.id DESC LIMIT 5
    ");
    $latest_orders = $latest_orders_stmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Statistics Error: " . $e->getMessage() . "</div>";
}
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-chart-line text-success me-2"></i>Dashboard Overview</h2>
        <p class="text-muted">Real-time statistics and delivery operational status.</p>
    </div>
</div>

<!-- Operational Counts Row 1 -->
<div class="row g-4 mb-4">
    <!-- Total Customers -->
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card customers-card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Total Customers</h5>
                    <div class="card-value"><?php echo $customers_count; ?></div>
                </div>
                <div class="stat-icon-wrapper bg-blue-light">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Delivery Partners -->
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card partners-card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Delivery Partners</h5>
                    <div class="card-value"><?php echo $partners_count; ?></div>
                </div>
                <div class="stat-icon-wrapper bg-emerald-light">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card orders-card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Total Orders</h5>
                    <div class="card-value"><?php echo $orders_count; ?></div>
                </div>
                <div class="stat-icon-wrapper bg-indigo-light">
                    <i class="fa-solid fa-box-open"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Counts Row 2 -->
<div class="row g-4 mb-5">
    <!-- Preparing -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card preparing-card h-100">
            <div class="card-body p-3 text-center">
                <div class="stat-icon-wrapper bg-orange-light mx-auto mb-2">
                    <i class="fa-solid fa-kitchen-set"></i>
                </div>
                <h5 class="card-title mb-1" style="font-size: 0.75rem;">Preparing</h5>
                <div class="fs-4 fw-bold text-dark"><?php echo $preparing_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Ready -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card ready-card h-100">
            <div class="card-body p-3 text-center">
                <div class="stat-icon-wrapper bg-yellow-light mx-auto mb-2">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
                <h5 class="card-title mb-1" style="font-size: 0.75rem;">Ready</h5>
                <div class="fs-4 fw-bold text-dark"><?php echo $ready_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Picked -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card picked-card h-100">
            <div class="card-body p-3 text-center">
                <div class="stat-icon-wrapper bg-purple-light mx-auto mb-2">
                    <i class="fa-solid fa-people-carry-box"></i>
                </div>
                <h5 class="card-title mb-1" style="font-size: 0.75rem;">Picked</h5>
                <div class="fs-4 fw-bold text-dark"><?php echo $picked_count; ?></div>
            </div>
        </div>
    </div>

    <!-- In Travel -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card travel-card h-100">
            <div class="card-body p-3 text-center">
                <div class="stat-icon-wrapper bg-cyan-light mx-auto mb-2">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <h5 class="card-title mb-1" style="font-size: 0.75rem;">In Travel</h5>
                <div class="fs-4 fw-bold text-dark"><?php echo $travel_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Reached -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card reached-card h-100">
            <div class="card-body p-3 text-center">
                <div class="stat-icon-wrapper bg-blue-light mx-auto mb-2">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <h5 class="card-title mb-1" style="font-size: 0.75rem;">Reached</h5>
                <div class="fs-4 fw-bold text-dark"><?php echo $reached_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Delivered -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card delivered-card h-100">
            <div class="card-body p-3 text-center">
                <div class="stat-icon-wrapper bg-emerald-light mx-auto mb-2">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h5 class="card-title mb-1" style="font-size: 0.75rem;">Delivered</h5>
                <div class="fs-4 fw-bold text-dark"><?php echo $delivered_count; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Latest Orders Section -->
<div class="card main-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-success"><i class="fa-solid fa-list-check me-2"></i>Recent Orders Activity</h5>
        <a href="order_track.php" class="btn btn-sm btn-success rounded-pill px-3">
            <i class="fa-solid fa-eye me-1"></i> Track All
        </a>
    </div>
    <div class="card-body">
        <?php if (count($latest_orders) > 0): ?>
            <div class="table-responsive table-responsive-custom">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Delivery Partner</th>
                            <th>Order Details</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_orders as $order): ?>
                            <tr>
                                <td class="fw-bold text-success">#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['partner_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['order_details']); ?></td>
                                <td>
                                    <?php 
                                    $badge_class = 'bg-secondary';
                                    if ($order['status'] === 'Order Preparing') $badge_class = 'bg-warning text-dark';
                                    elseif ($order['status'] === 'Order Ready') $badge_class = 'bg-info text-dark';
                                    elseif ($order['status'] === 'Pick Order') $badge_class = 'bg-primary';
                                    elseif ($order['status'] === 'In Travel') $badge_class = 'bg-primary bg-gradient';
                                    elseif ($order['status'] === 'Order Reached') $badge_class = 'bg-info';
                                    elseif ($order['status'] === 'Order Delivered') $badge_class = 'bg-success';
                                    ?>
                                    <span class="badge badge-custom <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_track.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fa-solid fa-magnifying-glass-location"></i> Track
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fa-regular fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">No orders found in the database. Head over to <a href="order_place.php">Place Order</a> to register one!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
