<?php
// partner/dashboard.php
$page_title = "Driver Console | QuickShip";
$required_role = "Delivery Partner";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$partner_id = $_SESSION['user_id'];
$partner_name = $_SESSION['username'];

$pending_count = 0;
$completed_count = 0;
$pending_orders = [];
$completed_orders = [];
$error_msg = "";
$success_msg = "";

if (isset($_GET['success'])) {
    $success_msg = "Order status updated successfully!";
}

// Sequential statuses
$status_sequence = [
    'Order Preparing',
    'Order Ready',
    'Pick Order',
    'In Travel',
    'Order Reached',
    'Order Delivered'
];

try {
    // Counts
    $stmtCountPen = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `delivery_partner_id` = ? AND `status` != 'Order Delivered'");
    $stmtCountPen->execute([$partner_id]);
    $pending_count = $stmtCountPen->fetchColumn();

    $stmtCountComp = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `delivery_partner_id` = ? AND `status` = 'Order Delivered'");
    $stmtCountComp->execute([$partner_id]);
    $completed_count = $stmtCountComp->fetchColumn();

    // Fetch Pending Orders
    $stmtPending = $pdo->prepare("
        SELECT o.*, c.name AS customer_name, c.address AS customer_address, c.contact_number AS customer_phone 
        FROM `orders` o 
        JOIN `customers` c ON o.customer_id = c.id
        WHERE o.delivery_partner_id = ? AND o.status != 'Order Delivered'
        ORDER BY o.id DESC
    ");
    $stmtPending->execute([$partner_id]);
    $pending_orders = $stmtPending->fetchAll();

    // Fetch Completed Orders
    $stmtCompleted = $pdo->prepare("
        SELECT o.*, c.name AS customer_name, c.address AS customer_address, c.contact_number AS customer_phone 
        FROM `orders` o 
        JOIN `customers` c ON o.customer_id = c.id
        WHERE o.delivery_partner_id = ? AND o.status = 'Order Delivered'
        ORDER BY o.id DESC LIMIT 10
    ");
    $stmtCompleted->execute([$partner_id]);
    $completed_orders = $stmtCompleted->fetchAll();

} catch (PDOException $e) {
    $error_msg = "Error loading driver shipments: " . $e->getMessage();
}
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-circle-check text-success me-2"></i>Driver Console</h2>
        <p class="text-muted">Manage your assigned shipments and update delivery milestones.</p>
    </div>
</div>

<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>
        <div><?php echo htmlspecialchars($success_msg); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <div><?php echo htmlspecialchars($error_msg); ?></div>
    </div>
<?php endif; ?>

<!-- Stats cards -->
<div class="row g-4 mb-4">
    <!-- Pending tasks -->
    <div class="col-md-6">
        <div class="card stat-card travel-card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Pending Shipments</h5>
                    <div class="card-value"><?php echo $pending_count; ?></div>
                </div>
                <div class="stat-icon-wrapper bg-cyan-light">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed tasks -->
    <div class="col-md-6">
        <div class="card stat-card delivered-card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Completed Shipments</h5>
                    <div class="card-value"><?php echo $completed_count; ?></div>
                </div>
                <div class="stat-icon-wrapper bg-emerald-light">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active/Pending Shipments Table -->
<div class="card main-card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0 text-success"><i class="fa-solid fa-truck-ramp-box me-2"></i>Active Shipments Queue</h5>
    </div>
    <div class="card-body">
        <?php if (count($pending_orders) > 0): ?>
            <div class="table-responsive table-responsive-custom">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer / Delivery Address</th>
                            <th>Package Specs</th>
                            <th>Current State</th>
                            <th>Sequential Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_orders as $o): 
                            // Determine current index and calculate next index
                            $curr_idx = array_search($o['status'], $status_sequence);
                            $next_status = ($curr_idx !== false && $curr_idx < count($status_sequence) - 1) ? $status_sequence[$curr_idx + 1] : null;
                            
                            // Color scheme for next button
                            $btn_color = 'btn-success';
                            $btn_icon = 'fa-solid fa-angles-right';
                            if ($next_status === 'Order Ready') { $btn_color = 'btn-info text-dark'; $btn_icon = 'fa-solid fa-boxes-packing'; }
                            elseif ($next_status === 'Pick Order') { $btn_color = 'btn-primary'; $btn_icon = 'fa-solid fa-people-carry-box'; }
                            elseif ($next_status === 'In Travel') { $btn_color = 'btn-primary bg-gradient'; $btn_icon = 'fa-solid fa-motorcycle'; }
                            elseif ($next_status === 'Order Reached') { $btn_color = 'btn-info'; $btn_icon = 'fa-solid fa-location-dot'; }
                            elseif ($next_status === 'Order Delivered') { $btn_color = 'btn-success'; $btn_icon = 'fa-solid fa-circle-check'; }
                        ?>
                            <tr>
                                <td class="fw-bold text-success font-monospace">#<?php echo $o['id']; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                                    <div class="text-muted" style="font-size: 0.85rem;"><i class="fa-solid fa-location-pin me-1"></i><?php echo htmlspecialchars($o['customer_address']); ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($o['customer_phone']); ?></div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($o['order_details']); ?>">
                                        <?php echo htmlspecialchars($o['order_details']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $badge = 'bg-secondary';
                                    if ($o['status'] === 'Order Preparing') $badge = 'bg-warning text-dark';
                                    elseif ($o['status'] === 'Order Ready') $badge = 'bg-info text-dark';
                                    elseif ($o['status'] === 'Pick Order') $badge = 'bg-primary';
                                    elseif ($o['status'] === 'In Travel') $badge = 'bg-primary bg-gradient';
                                    elseif ($o['status'] === 'Order Reached') $badge = 'bg-info';
                                    ?>
                                    <span class="badge badge-custom <?php echo $badge; ?>">
                                        <?php echo htmlspecialchars($o['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($next_status): ?>
                                        <form action="order_update.php" method="POST" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo htmlspecialchars($next_status); ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $btn_color; ?> d-flex align-items-center gap-1">
                                                <i class="<?php echo $btn_icon; ?>"></i> <?php echo htmlspecialchars($next_status); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-success font-monospace" style="font-size: 0.8rem;"><i class="fa-solid fa-circle-check"></i> Delivered</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fa-solid fa-house-circle-check fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Excellent work! You have no pending deliveries assigned.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Completed Shipments Table -->
<div class="card main-card">
    <div class="card-header bg-light">
        <h5 class="mb-0 text-success"><i class="fa-solid fa-clock-rotate-left me-2"></i>Completed Shipments History (Last 10)</h5>
    </div>
    <div class="card-body">
        <?php if (count($completed_orders) > 0): ?>
            <div class="table-responsive table-responsive-custom">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Address</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Delivered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completed_orders as $o): ?>
                            <tr>
                                <td class="fw-bold text-success font-monospace">#<?php echo $o['id']; ?></td>
                                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($o['customer_address']); ?>">
                                        <?php echo htmlspecialchars($o['customer_address']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($o['order_details']); ?></td>
                                <td>
                                    <span class="badge badge-custom bg-success">
                                        <i class="fa-solid fa-circle-check me-1"></i>Delivered
                                    </span>
                                </td>
                                <td style="font-size: 0.85rem;" class="text-muted">
                                    <?php echo date("M d, Y h:i A", strtotime($o['updated_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fa-regular fa-folder-open fa-3x text-muted mb-2"></i>
                <p class="text-muted mb-0">No completed orders yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
