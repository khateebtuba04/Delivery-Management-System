<?php
// includes/sidebar.php
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$current_page = isset($current_page) ? $current_page : '';
?>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-truck-ramp-box"></i> QuickShip</h3>
    </div>

    <ul class="list-unstyled components">
        <li class="px-3 mb-2 text-uppercase font-monospace text-white-50" style="font-size: 0.75rem;">
            Role: <?php echo htmlspecialchars($role); ?>
        </li>
        
        <?php if ($role === 'Admin'): ?>
            <!-- Admin Navigation -->
            <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="../admin/dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
            </li>
            <li class="<?php echo ($current_page === 'customer_add.php' && !isset($_GET['id'])) ? 'active' : ''; ?>">
                <a href="../admin/customer_add.php"><i class="fa-solid fa-user-plus"></i> Add Customer</a>
            </li>
            <li class="<?php echo ($current_page === 'customer_view.php' || ($current_page === 'customer_add.php' && isset($_GET['id']))) ? 'active' : ''; ?>">
                <a href="../admin/customer_view.php"><i class="fa-solid fa-users"></i> View Customer</a>
            </li>
            <li class="<?php echo ($current_page === 'partner_add.php' && !isset($_GET['id'])) ? 'active' : ''; ?>">
                <a href="../admin/partner_add.php"><i class="fa-solid fa-truck-field"></i> Add Delivery Partner</a>
            </li>
            <li class="<?php echo ($current_page === 'partner_view.php' || ($current_page === 'partner_add.php' && isset($_GET['id']))) ? 'active' : ''; ?>">
                <a href="../admin/partner_view.php"><i class="fa-solid fa-truck-fast"></i> View Delivery Partner</a>
            </li>
            <li class="<?php echo $current_page === 'order_place.php' ? 'active' : ''; ?>">
                <a href="../admin/order_place.php"><i class="fa-solid fa-cart-plus"></i> Place Order</a>
            </li>
            <li class="<?php echo $current_page === 'order_track.php' ? 'active' : ''; ?>">
                <a href="../admin/order_track.php"><i class="fa-solid fa-map-pin"></i> Track Order</a>
            </li>
            <li class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                <a href="../admin/users.php"><i class="fa-solid fa-user-gear"></i> Users</a>
            </li>
            <li class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                <a href="../admin/reports.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reports</a>
            </li>

        <?php elseif ($role === 'Customer'): ?>
            <!-- Customer Navigation -->
            <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="../customer/dashboard.php"><i class="fa-solid fa-house"></i> Customer Dashboard</a>
            </li>
            <li class="<?php echo $current_page === 'order_place.php' ? 'active' : ''; ?>">
                <a href="../customer/order_place.php"><i class="fa-solid fa-cart-shopping"></i> Place Order</a>
            </li>
            <li class="<?php echo $current_page === 'order_track.php' ? 'active' : ''; ?>">
                <a href="../customer/order_track.php"><i class="fa-solid fa-route"></i> Track Order</a>
            </li>

        <?php elseif ($role === 'Delivery Partner'): ?>
            <!-- Delivery Partner Navigation -->
            <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="../partner/dashboard.php"><i class="fa-solid fa-circle-check"></i> Manage Tasks</a>
            </li>
        <?php endif; ?>
        
        <li class="mt-4 border-top border-secondary pt-2">
            <a href="../logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </li>
    </ul>

    <div class="sidebar-footer">
        Logged in as:<br>
        <strong><?php echo htmlspecialchars($username); ?></strong>
    </div>
</nav>

<!-- Content Area Container (Matches header and sidebar) -->
<div id="content">
    <!-- Navbar with Sidebar Toggle -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
        <div class="container-fluid">
            <button type="button" id="sidebarCollapse" class="sidebar-collapse-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted d-none d-md-inline">Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-power-off"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Body -->
    <div class="container-fluid p-4">
