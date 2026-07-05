<?php
// admin/customer_add.php
$page_title = "Manage Customer | QuickShip";
$required_role = "Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$success_msg = "";
$error_msg = "";

// Form fields initialization
$id = "";
$name = "";
$address = "";
$customer_type = "";
$contact_number = "";
$username = "";
$is_edit = false;

// Check if we are in Edit mode
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_edit = true;
    $id = intval($_GET['id']);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM `customers` WHERE `id` = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        
        if ($customer) {
            $name = $customer['name'];
            $address = $customer['address'];
            $customer_type = $customer['customer_type'];
            $contact_number = $customer['contact_number'];
            $username = $customer['username'];
        } else {
            $error_msg = "Customer not found.";
            $is_edit = false;
        }
    } catch (PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $customer_type = $_POST['customer_type'] ?? '';
    $contact_number = trim($_POST['contact_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $is_edit = !empty($id);

    // Basic Validation
    if (empty($name) || empty($address) || empty($customer_type) || empty($contact_number) || empty($username)) {
        $error_msg = "All fields except password are required.";
    } elseif (!$is_edit && empty($password)) {
        $error_msg = "Password is required for new customers.";
    } else {
        try {
            // Check for duplicate username in customers table
            if ($is_edit) {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `customers` WHERE `username` = ? AND `id` != ?");
                $stmtCheck->execute([$username, $id]);
            } else {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `customers` WHERE `username` = ?");
                $stmtCheck->execute([$username]);
            }
            
            $duplicateCount = $stmtCheck->fetchColumn();
            
            if ($duplicateCount > 0) {
                $error_msg = "The login name '$username' is already taken by another customer.";
            } else {
                if ($is_edit) {
                    // Update Customer
                    if (!empty($password)) {
                        // Password is being updated
                        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                        $stmtUpdate = $pdo->prepare("
                            UPDATE `customers` 
                            SET `name` = ?, `address` = ?, `customer_type` = ?, `contact_number` = ?, `username` = ?, `password` = ? 
                            WHERE `id` = ?
                        ");
                        $stmtUpdate->execute([$name, $address, $customer_type, $contact_number, $username, $hashed_pass, $id]);
                    } else {
                        // Password is not being updated
                        $stmtUpdate = $pdo->prepare("
                            UPDATE `customers` 
                            SET `name` = ?, `address` = ?, `customer_type` = ?, `contact_number` = ?, `username` = ? 
                            WHERE `id` = ?
                        ");
                        $stmtUpdate->execute([$name, $address, $customer_type, $contact_number, $username, $id]);
                    }
                    $success_msg = "Customer updated successfully.";
                } else {
                    // Create Customer
                    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO `customers` (`name`, `address`, `customer_type`, `contact_number`, `username`, `password`) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->execute([$name, $address, $customer_type, $contact_number, $username, $hashed_pass]);
                    $success_msg = "Customer added successfully.";
                    
                    // Reset fields for new entry
                    $name = "";
                    $address = "";
                    $customer_type = "";
                    $contact_number = "";
                    $username = "";
                }
            }
        } catch (PDOException $e) {
            $error_msg = "SQL Error: " . $e->getMessage();
        }
    }
}
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="h3 mb-0 text-gray-800">
            <i class="fa-solid <?php echo $is_edit ? 'fa-user-pen text-success' : 'fa-user-plus text-success'; ?> me-2"></i>
            <?php echo $is_edit ? 'Edit Customer' : 'Add New Customer'; ?>
        </h2>
        <p class="text-muted"><?php echo $is_edit ? 'Update customer profile information and system credentials.' : 'Register a new customer portal user.'; ?></p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card main-card">
            <div class="card-header">
                <h5 class="mb-0 text-success">
                    <i class="fa-solid fa-address-card me-2"></i>Customer Profile Details
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i>
                        <div>
                            <?php echo htmlspecialchars($success_msg); ?> 
                            <a href="customer_view.php" class="alert-link ms-2">View Customer List</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        <div><?php echo htmlspecialchars($error_msg); ?></div>
                    </div>
                <?php endif; ?>

                <form action="customer_add.php<?php echo $is_edit ? '?id=' . $id : ''; ?>" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                    
                    <!-- Customer Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label form-label-custom">Customer Name</label>
                        <input type="text" class="form-control form-control-custom" id="name" name="name" 
                               placeholder="e.g. John Doe Enterprises" value="<?php echo htmlspecialchars($name); ?>" required>
                        <div class="invalid-feedback">Please enter the customer name.</div>
                    </div>

                    <!-- Address -->
                    <div class="mb-3">
                        <label for="address" class="form-label form-label-custom">Address</label>
                        <textarea class="form-control form-control-custom" id="address" name="address" rows="3" 
                                  placeholder="Enter complete delivery address..." required><?php echo htmlspecialchars($address); ?></textarea>
                        <div class="invalid-feedback">Please enter the delivery address.</div>
                    </div>

                    <div class="row">
                        <!-- Customer Type Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="customer_type" class="form-label form-label-custom">Customer Type</label>
                            <select class="form-select form-control-custom" id="customer_type" name="customer_type" required>
                                <option value="" disabled <?php echo empty($customer_type) ? 'selected' : ''; ?>>Select Type...</option>
                                <option value="Food" <?php echo $customer_type === 'Food' ? 'selected' : ''; ?>>Food</option>
                                <option value="Snacks" <?php echo $customer_type === 'Snacks' ? 'selected' : ''; ?>>Snacks</option>
                                <option value="Stationery" <?php echo $customer_type === 'Stationery' ? 'selected' : ''; ?>>Stationery</option>
                                <option value="Grocery" <?php echo $customer_type === 'Grocery' ? 'selected' : ''; ?>>Grocery</option>
                            </select>
                            <div class="invalid-feedback">Please select a customer type.</div>
                        </div>

                        <!-- Contact Number -->
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label form-label-custom">Contact Number</label>
                            <input type="tel" class="form-control form-control-custom" id="contact_number" name="contact_number" 
                                   placeholder="e.g. +1 (555) 019-2834" value="<?php echo htmlspecialchars($contact_number); ?>" required>
                            <div class="invalid-feedback">Please enter the contact number.</div>
                        </div>
                    </div>

                    <hr class="my-4 text-muted">
                    <h5 class="mb-3 text-success font-monospace" style="font-size: 1rem;"><i class="fa-solid fa-key me-2"></i>Login Credentials</h5>

                    <div class="row">
                        <!-- Login Name -->
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label form-label-custom">Login Username</label>
                            <input type="text" class="form-control form-control-custom" id="username" name="username" 
                                   placeholder="e.g. johndoe" value="<?php echo htmlspecialchars($username); ?>" required>
                            <div class="invalid-feedback">Please enter the login username.</div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label form-label-custom">
                                Password 
                                <?php if ($is_edit): ?>
                                    <small class="text-muted">(leave blank to keep current)</small>
                                <?php endif; ?>
                            </label>
                            <input type="password" class="form-control form-control-custom" id="password" name="password" 
                                   placeholder="••••••••" <?php echo $is_edit ? '' : 'required'; ?>>
                            <div class="invalid-feedback">Please enter a password.</div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-custom btn-custom-primary">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            <?php echo $is_edit ? 'Update Profile' : 'Save Customer'; ?>
                        </button>
                        <a href="customer_view.php" class="btn btn-custom btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back to View List
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
