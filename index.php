<?php
// index.php
require_once __DIR__ . '/config/db.php';

// Redirect to respective dashboard if already logged in
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'Customer') {
        header("Location: customer/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'Delivery Partner') {
        header("Location: partner/dashboard.php");
        exit;
    }
}

$error_msg = "";
$active_tab = "admin"; // Default tab

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portal = $_POST['portal'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $active_tab = $portal;

    if (empty($username) || empty($password)) {
        $error_msg = "Please enter both username and password.";
    } else {
        try {
            if ($portal === 'admin') {
                $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'Admin';
                    header("Location: admin/dashboard.php");
                    exit;
                } else {
                    $error_msg = "Invalid admin credentials.";
                }
            } elseif ($portal === 'customer') {
                $stmt = $pdo->prepare("SELECT * FROM `customers` WHERE `username` = ?");
                $stmt->execute([$username]);
                $customer = $stmt->fetch();

                if ($customer && password_verify($password, $customer['password'])) {
                    $_SESSION['user_id'] = $customer['id'];
                    $_SESSION['username'] = $customer['name'];
                    $_SESSION['role'] = 'Customer';
                    header("Location: customer/dashboard.php");
                    exit;
                } else {
                    $error_msg = "Invalid customer credentials.";
                }
            } elseif ($portal === 'partner') {
                $stmt = $pdo->prepare("SELECT * FROM `delivery_partners` WHERE `username` = ?");
                $stmt->execute([$username]);
                $partner = $stmt->fetch();

                if ($partner && password_verify($password, $partner['password'])) {
                    $_SESSION['user_id'] = $partner['id'];
                    $_SESSION['username'] = $partner['name'];
                    $_SESSION['role'] = 'Delivery Partner';
                    header("Location: partner/dashboard.php");
                    exit;
                } else {
                    $error_msg = "Invalid delivery partner credentials.";
                }
            } else {
                $error_msg = "Unknown login portal chosen.";
            }
        } catch (PDOException $e) {
            $error_msg = "Authentication system error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShip | Login Portal</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body d-flex align-items-center min-vh-100">

<div class="container">
    <div class="card login-card">
        <div class="row g-0">
            <!-- Branding Column -->
            <div class="col-lg-5 login-branding d-none d-lg-flex">
                <i class="fa-solid fa-truck-ramp-box fa-4x mb-4 text-white"></i>
                <h2>QuickShip</h2>
                <p class="text-white-50 mt-2 px-3">
                    Fast, secure, and modern logistics management system. Easily coordinate orders, customers, and delivery partners under one dashboard.
                </p>
                <div class="mt-4 text-white-50 font-monospace" style="font-size: 0.8rem;">
                    v1.0.0 Stable
                </div>
            </div>
            
            <!-- Login Form Column -->
            <div class="col-lg-7 login-form-area">
                <div class="d-flex align-items-center mb-4">
                    <span class="d-lg-none me-3"><i class="fa-solid fa-truck-ramp-box fa-2x text-success"></i></span>
                    <h3 class="mb-0 fw-bold text-success font-monospace">Portal Login</h3>
                </div>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        <div><?php echo htmlspecialchars($error_msg); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Tab Navigation for the three portals -->
                <ul class="nav nav-tabs login-tabs mb-4" id="loginTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $active_tab === 'admin' ? 'active' : ''; ?>" 
                                id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-panel" 
                                type="button" role="tab" aria-controls="admin-panel" 
                                aria-selected="<?php echo $active_tab === 'admin' ? 'true' : 'false'; ?>">
                            <i class="fa-solid fa-lock me-1"></i> Admin
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $active_tab === 'customer' ? 'active' : ''; ?>" 
                                id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer-panel" 
                                type="button" role="tab" aria-controls="customer-panel" 
                                aria-selected="<?php echo $active_tab === 'customer' ? 'true' : 'false'; ?>">
                            <i class="fa-solid fa-user me-1"></i> Customer
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $active_tab === 'partner' ? 'active' : ''; ?>" 
                                id="partner-tab" data-bs-toggle="tab" data-bs-target="#partner-panel" 
                                type="button" role="tab" aria-controls="partner-panel" 
                                aria-selected="<?php echo $active_tab === 'partner' ? 'true' : 'false'; ?>">
                            <i class="fa-solid fa-motorcycle me-1"></i> Driver
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="loginTabContent">
                    <!-- Admin Login Portal -->
                    <div class="tab-pane fade <?php echo $active_tab === 'admin' ? 'show active' : ''; ?>" id="admin-panel" role="tabpanel" aria-labelledby="admin-tab">
                        <form action="index.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="portal" value="admin">
                            <div class="mb-3">
                                <label for="admin_user" class="form-label form-label-custom">Admin Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                                    <input type="text" class="form-control form-control-custom border-start-0" id="admin_user" name="username" placeholder="e.g. admin" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="admin_pass" class="form-label form-label-custom">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                                    <input type="password" class="form-control form-control-custom border-start-0" id="admin_pass" name="password" placeholder="••••••••" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-custom btn-custom-primary w-100">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Enter Admin Control
                            </button>
                        </form>
                    </div>

                    <!-- Customer Login Portal -->
                    <div class="tab-pane fade <?php echo $active_tab === 'customer' ? 'show active' : ''; ?>" id="customer-panel" role="tabpanel" aria-labelledby="customer-tab">
                        <form action="index.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="portal" value="customer">
                            <div class="mb-3">
                                <label for="customer_user" class="form-label form-label-custom">Customer Login Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                                    <input type="text" class="form-control form-control-custom border-start-0" id="customer_user" name="username" placeholder="e.g. customer1" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="customer_pass" class="form-label form-label-custom">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                                    <input type="password" class="form-control form-control-custom border-start-0" id="customer_pass" name="password" placeholder="••••••••" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-custom btn-custom-primary w-100">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Customer Access
                            </button>
                        </form>
                    </div>

                    <!-- Delivery Partner Login Portal -->
                    <div class="tab-pane fade <?php echo $active_tab === 'partner' ? 'show active' : ''; ?>" id="partner-panel" role="tabpanel" aria-labelledby="partner-tab">
                        <form action="index.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="portal" value="partner">
                            <div class="mb-3">
                                <label for="partner_user" class="form-label form-label-custom">Partner Login Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                                    <input type="text" class="form-control form-control-custom border-start-0" id="partner_user" name="username" placeholder="e.g. partner1" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="partner_pass" class="form-label form-label-custom">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                                    <input type="password" class="form-control form-control-custom border-start-0" id="partner_pass" name="password" placeholder="••••••••" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-custom btn-custom-primary w-100">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Driver Access
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Form Validation Script -->
<script src="assets/js/main.js"></script>
</body>
</html>
