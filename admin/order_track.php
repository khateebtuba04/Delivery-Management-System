<?php
// admin/order_track.php
$page_title = "Track Order | QuickShip";
$required_role = "Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$selected_order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? intval($_GET['order_id']) : null;
$order = null;
$status_history = [];
$error_msg = "";

// Sequential list of all statuses
$all_statuses = [
    'Order Preparing',
    'Order Ready',
    'Pick Order',
    'In Travel',
    'Order Reached',
    'Order Delivered'
];

if ($selected_order_id) {
    try {
        // Fetch order details
        $stmt = $pdo->prepare("
            SELECT o.*, c.name AS customer_name, c.address AS customer_address, c.contact_number AS customer_phone,
                   p.name AS partner_name, p.contact_number AS partner_phone
            FROM `orders` o
            JOIN `customers` c ON o.customer_id = c.id
            JOIN `delivery_partners` p ON o.delivery_partner_id = p.id
            WHERE o.id = ?
        ");
        $stmt->execute([$selected_order_id]);
        $order = $stmt->fetch();

        if ($order) {
            // Fetch status history
            $stmtHist = $pdo->prepare("
                SELECT * FROM `order_status_history` 
                WHERE `order_id` = ? 
                ORDER BY `changed_at` ASC
            ");
            $stmtHist->execute([$selected_order_id]);
            $history_rows = $stmtHist->fetchAll();
            
            // Map status to timestamp
            foreach ($history_rows as $row) {
                $status_history[$row['status']] = $row['changed_at'];
            }
        } else {
            $error_msg = "Order #" . $selected_order_id . " not found.";
            $selected_order_id = null;
        }
    } catch (PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
        $selected_order_id = null;
    }
}

// Fetch all orders if no single order selected
$orders = [];
if (!$selected_order_id) {
    $search = trim($_GET['search'] ?? '');
    try {
        if (!empty($search)) {
            $stmtAll = $pdo->prepare("
                SELECT o.*, c.name AS customer_name, p.name AS partner_name 
                FROM `orders` o 
                JOIN `customers` c ON o.customer_id = c.id
                JOIN `delivery_partners` p ON o.delivery_partner_id = p.id
                WHERE o.id = ? OR c.name LIKE ? OR p.name LIKE ? OR o.status LIKE ?
                ORDER BY o.id DESC
            ");
            $stmtAll->execute([$search, "%$search%", "%$search%", "%$search%"]);
        } else {
            $stmtAll = $pdo->query("
                SELECT o.*, c.name AS customer_name, p.name AS partner_name 
                FROM `orders` o 
                JOIN `customers` c ON o.customer_id = c.id
                JOIN `delivery_partners` p ON o.delivery_partner_id = p.id
                ORDER BY o.id DESC
            ");
        }
        $orders = $stmtAll->fetchAll();
    } catch (PDOException $e) {
        $error_msg = "Failed to load orders: " . $e->getMessage();
    }
}
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="h3 mb-0 text-gray-800">
            <i class="fa-solid fa-map-pin text-success me-2"></i>
            <?php echo $selected_order_id ? "Tracking Order #$selected_order_id" : "Track Delivery Orders"; ?>
        </h2>
        <p class="text-muted">
            <?php echo $selected_order_id ? "Visual dispatch status timeline and delivery history." : "Search and track any order registered in the system."; ?>
        </p>
    </div>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <div><?php echo htmlspecialchars($error_msg); ?></div>
    </div>
<?php endif; ?>

<?php if ($selected_order_id && $order): ?>
    <!-- Mode 1: Detailed Order Timeline Tracking -->
    <div class="row">
        <!-- Order details block -->
        <div class="col-lg-5 mb-4">
            <div class="card main-card mb-4 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-success"><i class="fa-solid fa-circle-info me-2"></i>Order Metadata</h5>
                    <span class="badge bg-success font-monospace">Order ID: #<?php echo $order['id']; ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label form-label-custom text-muted mb-0">Customer Details</label>
                        <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                        <div class="text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                        <div class="text-muted"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($order['customer_address']); ?></div>
                    </div>
                    
                    <hr class="text-muted">

                    <div class="mb-3">
                        <label class="form-label form-label-custom text-muted mb-0">Assigned Delivery Partner</label>
                        <div class="fw-bold text-dark"><i class="fa-solid fa-motorcycle me-1 text-success"></i><?php echo htmlspecialchars($order['partner_name']); ?></div>
                        <div class="text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($order['partner_phone']); ?></div>
                    </div>

                    <hr class="text-muted">

                    <div class="mb-3">
                        <label class="form-label form-label-custom text-muted mb-0">Order Specs / Instructions</label>
                        <div class="bg-light p-3 rounded border text-secondary font-monospace" style="white-space: pre-wrap; font-size: 0.9rem;"><?php echo htmlspecialchars($order['order_details']); ?></div>
                    </div>

                    <div class="mt-4">
                        <a href="order_track.php" class="btn btn-custom btn-outline-secondary w-100">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Tracking Timeline -->
        <div class="col-lg-7 mb-4">
            <div class="card main-card h-100">
                <div class="card-header">
                    <h5 class="mb-0 text-success"><i class="fa-solid fa-route me-2"></i>Live Transit Status</h5>
                </div>
                <div class="card-body">
                    <ul class="tracking-timeline">
                        <?php 
                        // Find current index of active status
                        $current_status_index = array_search($order['status'], $all_statuses);
                        
                        foreach ($all_statuses as $index => $status_name): 
                            $is_completed = $index < $current_status_index;
                            $is_active = $index === $current_status_index;
                            $status_class = $is_completed ? 'completed' : ($is_active ? 'active' : '');
                            
                            // Check if status has a timestamp in history
                            $timestamp = isset($status_history[$status_name]) ? date("M d, Y h:i A", strtotime($status_history[$status_name])) : null;
                            
                            // Map icon
                            $icon = 'fa-solid fa-circle';
                            if ($status_name === 'Order Preparing') $icon = 'fa-solid fa-kitchen-set';
                            elseif ($status_name === 'Order Ready') $icon = 'fa-solid fa-boxes-packing';
                            elseif ($status_name === 'Pick Order') $icon = 'fa-solid fa-people-carry-box';
                            elseif ($status_name === 'In Travel') $icon = 'fa-solid fa-motorcycle';
                            elseif ($status_name === 'Order Reached') $icon = 'fa-solid fa-location-dot';
                            elseif ($status_name === 'Order Delivered') $icon = 'fa-solid fa-circle-check';
                        ?>
                            <li class="timeline-item <?php echo $status_class; ?>">
                                <div class="timeline-icon">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div class="timeline-title"><?php echo htmlspecialchars($status_name); ?></div>
                                        <?php if ($is_active): ?>
                                            <span class="badge bg-warning text-dark font-monospace text-uppercase" style="font-size: 0.65rem;">Active State</span>
                                        <?php elseif ($is_completed): ?>
                                            <span class="badge bg-success font-monospace text-uppercase" style="font-size: 0.65rem;">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($timestamp): ?>
                                        <div class="timeline-time mt-1">
                                            <i class="fa-regular fa-clock me-1"></i><?php echo $timestamp; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="timeline-time text-muted mt-1">Pending step...</div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Mode 2: Browse and Search all Orders -->
    <div class="card main-card">
        <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <h5 class="mb-0 text-success"><i class="fa-solid fa-table-list me-2"></i>Orders Registry</h5>
            
            <form action="order_track.php" method="GET" class="w-100 w-sm-auto">
                <div class="input-group">
                    <input type="text" name="search" class="form-control form-control-custom py-1" 
                           placeholder="Search ID, customer, driver, or status..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <?php if (isset($_GET['search'])): ?>
                        <a href="order_track.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-success py-1" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <?php if (count($orders) > 0): ?>
                <div class="table-responsive table-responsive-custom">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Assigned Partner</th>
                                <th>Details</th>
                                <th>Status</th>
                                <th>Dispatched Date</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td class="fw-bold text-success">#<?php echo $o['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-muted"><i class="fa-solid fa-user-tag me-1" style="font-size: 0.8rem;"></i><?php echo htmlspecialchars($o['partner_name']); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($o['order_details']); ?>">
                                            <?php echo htmlspecialchars($o['order_details']); ?>
                                        </div>
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
                                            <i class="fa-solid fa-magnifying-glass-location me-1"></i> Track
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
                    <p class="text-muted mb-0">No orders matching search criteria found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
