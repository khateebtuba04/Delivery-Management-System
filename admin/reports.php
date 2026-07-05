<?php
// admin/reports.php
$page_title = "Operational Reports | QuickShip";
$required_role = "Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch lists for filter dropdowns
try {
    $all_customers = $pdo->query("SELECT `id`, `name` FROM `customers` ORDER BY `name` ASC")->fetchAll();
    $all_partners = $pdo->query("SELECT `id`, `name` FROM `delivery_partners` ORDER BY `name` ASC")->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Filter options load failed: " . $e->getMessage() . "</div>";
}

// All status options
$status_options = [
    'Order Preparing',
    'Order Ready',
    'Pick Order',
    'In Travel',
    'Order Reached',
    'Order Delivered'
];

// Read filters
$filter_customer = $_GET['customer_id'] ?? '';
$filter_partner = $_GET['partner_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

// Build Query dynamically
$where_clauses = [];
$params = [];

if (!empty($filter_customer)) {
    $where_clauses[] = "o.customer_id = :customer_id";
    $params[':customer_id'] = $filter_customer;
}
if (!empty($filter_partner)) {
    $where_clauses[] = "o.delivery_partner_id = :delivery_partner_id";
    $params[':delivery_partner_id'] = $filter_partner;
}
if (!empty($filter_status)) {
    $where_clauses[] = "o.status = :status";
    $params[':status'] = $filter_status;
}
if (!empty($filter_start_date)) {
    $where_clauses[] = "DATE(o.created_at) >= :start_date";
    $params[':start_date'] = $filter_start_date;
}
if (!empty($filter_end_date)) {
    $where_clauses[] = "DATE(o.created_at) <= :end_date";
    $params[':end_date'] = $filter_end_date;
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Fetch matching orders
try {
    $query = "
        SELECT o.*, c.name AS customer_name, p.name AS partner_name 
        FROM `orders` o 
        JOIN `customers` c ON o.customer_id = c.id
        JOIN `delivery_partners` p ON o.delivery_partner_id = p.id
        $where_sql
        ORDER BY o.id DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Calculate report statistics on the fetched rows
    $total_orders = count($orders);
    $delivered_orders = 0;
    $pending_orders = 0;
    
    // Status distribution counter
    $status_counts = array_fill_keys($status_options, 0);

    foreach ($orders as $order) {
        if ($order['status'] === 'Order Delivered') {
            $delivered_orders++;
        } else {
            $pending_orders++;
        }

        if (array_key_exists($order['status'], $status_counts)) {
            $status_counts[$order['status']]++;
        }
    }

    // General total registry stats (independent of filters as specified in report prompt)
    $reg_customers_count = $pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
    $reg_partners_count = $pdo->query("SELECT COUNT(*) FROM `delivery_partners`")->fetchColumn();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Report Execution Error: " . $e->getMessage() . "</div>";
    $orders = [];
    $total_orders = $delivered_orders = $pending_orders = 0;
    $status_counts = array_fill_keys($status_options, 0);
    $reg_customers_count = $reg_partners_count = 0;
}
?>

<!-- Print-only styling overrides -->
<style>
@media print {
    #sidebar, .navbar-custom, .filter-card, .btn-print, .btn-reset, .sidebar-collapse-btn, .navbar, .mt-4.d-flex {
        display: none !important;
    }
    #wrapper {
        display: block !important;
    }
    #content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
    }
    .main-card {
        border: none !important;
        box-shadow: none !important;
    }
    .table-responsive-custom {
        overflow: visible !important;
        border: none !important;
    }
    .table-custom td, .table-custom th {
        padding: 8px 10px !important;
        font-size: 0.8rem !important;
    }
}
</style>

<div class="row mb-4">
    <div class="col d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-file-invoice-dollar text-success me-2"></i>Operational Reports</h2>
            <p class="text-muted">Analyze business performance, order status distributions, and active filters.</p>
        </div>
        <button class="btn btn-outline-success btn-print" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- Filters Panel -->
<div class="card main-card filter-card mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0 text-success"><i class="fa-solid fa-filter me-2"></i>Filter Options</h6>
    </div>
    <div class="card-body">
        <form action="reports.php" method="GET" class="row g-3">
            <!-- Customer -->
            <div class="col-md-3">
                <label for="customer_id" class="form-label form-label-custom mb-1">Customer</label>
                <select name="customer_id" id="customer_id" class="form-select form-control-custom">
                    <option value="">-- All Customers --</option>
                    <?php foreach ($all_customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $filter_customer == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Partner -->
            <div class="col-md-3">
                <label for="partner_id" class="form-label form-label-custom mb-1">Delivery Partner</label>
                <select name="partner_id" id="partner_id" class="form-select form-control-custom">
                    <option value="">-- All Partners --</option>
                    <?php foreach ($all_partners as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $filter_partner == $p['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status -->
            <div class="col-md-2">
                <label for="status" class="form-label form-label-custom mb-1">Status</label>
                <select name="status" id="status" class="form-select form-control-custom">
                    <option value="">-- All Statuses --</option>
                    <?php foreach ($status_options as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo $filter_status === $opt ? 'selected' : ''; ?>>
                            <?php echo $opt; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Start Date -->
            <div class="col-md-2 col-sm-6">
                <label for="start_date" class="form-label form-label-custom mb-1">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-custom" value="<?php echo htmlspecialchars($filter_start_date); ?>">
            </div>

            <!-- End Date -->
            <div class="col-md-2 col-sm-6">
                <label for="end_date" class="form-label form-label-custom mb-1">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-custom" value="<?php echo htmlspecialchars($filter_end_date); ?>">
            </div>

            <!-- Form Buttons -->
            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                <a href="reports.php" class="btn btn-outline-secondary btn-reset">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset
                </a>
                <button type="submit" class="btn btn-success px-4">
                    <i class="fa-solid fa-gears me-1"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Registry Totals + Filter Stats Cards -->
<div class="row g-3 mb-4">
    <!-- Total Registered Customers (Fixed) -->
    <div class="col-md-3 col-6">
        <div class="card bg-white border p-3 rounded h-100 text-center shadow-sm">
            <span class="text-uppercase text-muted font-monospace" style="font-size: 0.75rem;">Registered Customers</span>
            <h3 class="fw-bold mt-1 text-success"><?php echo $reg_customers_count; ?></h3>
        </div>
    </div>
    <!-- Total Registered Partners (Fixed) -->
    <div class="col-md-3 col-6">
        <div class="card bg-white border p-3 rounded h-100 text-center shadow-sm">
            <span class="text-uppercase text-muted font-monospace" style="font-size: 0.75rem;">Registered Partners</span>
            <h3 class="fw-bold mt-1 text-success"><?php echo $reg_partners_count; ?></h3>
        </div>
    </div>
    <!-- Total matching orders -->
    <div class="col-md-2 col-4">
        <div class="card bg-success text-white p-3 rounded h-100 text-center shadow-sm border-0">
            <span class="text-uppercase text-white-50 font-monospace" style="font-size: 0.7rem;">Matching Orders</span>
            <h3 class="fw-bold mt-1"><?php echo $total_orders; ?></h3>
        </div>
    </div>
    <!-- Delivered matching orders -->
    <div class="col-md-2 col-4">
        <div class="card bg-primary text-white p-3 rounded h-100 text-center shadow-sm border-0">
            <span class="text-uppercase text-white-50 font-monospace" style="font-size: 0.7rem;">Delivered Orders</span>
            <h3 class="fw-bold mt-1"><?php echo $delivered_orders; ?></h3>
        </div>
    </div>
    <!-- Pending matching orders -->
    <div class="col-md-2 col-4">
        <div class="card bg-warning text-dark p-3 rounded h-100 text-center shadow-sm border-0">
            <span class="text-uppercase text-dark-50 font-monospace" style="font-size: 0.7rem;">Pending Orders</span>
            <h3 class="fw-bold mt-1"><?php echo $pending_orders; ?></h3>
        </div>
    </div>
</div>

<!-- Orders by Status Breakdown Layout -->
<div class="row mb-4">
    <div class="col-lg-4 mb-3 mb-lg-0">
        <div class="card main-card h-100">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-success"><i class="fa-solid fa-chart-pie me-2"></i>Orders by Status</h6>
            </div>
            <div class="card-body">
                <?php foreach ($status_options as $st): 
                    $count = $status_counts[$st];
                    $pct = $total_orders > 0 ? round(($count / $total_orders) * 100) : 0;
                    
                    // ProgressBar Colors
                    $pb_color = 'bg-secondary';
                    if ($st === 'Order Preparing') $pb_color = 'bg-warning text-dark';
                    elseif ($st === 'Order Ready') $pb_color = 'bg-info text-dark';
                    elseif ($st === 'Pick Order') $pb_color = 'bg-primary';
                    elseif ($st === 'In Travel') $pb_color = 'bg-primary bg-gradient';
                    elseif ($st === 'Order Reached') $pb_color = 'bg-info';
                    elseif ($st === 'Order Delivered') $pb_color = 'bg-success';
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between font-monospace" style="font-size: 0.8rem;">
                            <span><?php echo htmlspecialchars($st); ?></span>
                            <strong><?php echo $count; ?> (<?php echo $pct; ?>%)</strong>
                        </div>
                        <div class="progress mt-1" style="height: 8px;">
                            <div class="progress-bar <?php echo $pb_color; ?>" role="progressbar" 
                                 style="width: <?php echo $pct; ?>%;" aria-valuenow="<?php echo $pct; ?>" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="col-lg-8">
        <div class="card main-card h-100">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-success"><i class="fa-solid fa-clipboard-list me-2"></i>Filtered Report Data</h6>
            </div>
            <div class="card-body">
                <?php if (count($orders) > 0): ?>
                    <div class="table-responsive table-responsive-custom">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Partner</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                    <tr>
                                        <td class="fw-bold text-success font-monospace">#<?php echo $o['id']; ?></td>
                                        <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($o['partner_name']); ?></td>
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
                                        <td style="font-size: 0.8rem;" class="text-muted">
                                            <?php echo date("Y-m-d H:i", strtotime($o['created_at'])); ?>
                                        </td>
                                        <td style="font-size: 0.8rem;" class="text-muted">
                                            <?php echo date("Y-m-d H:i", strtotime($o['updated_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No records found matching specified filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
