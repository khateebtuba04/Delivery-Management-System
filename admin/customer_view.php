<?php
// admin/customer_view.php
$page_title = "Customers List | QuickShip";
$required_role = "Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$success_msg = "";
$error_msg = "";

// Handle Customer Deletion
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM `customers` WHERE `id` = ?");
        $stmtDelete->execute([$delete_id]);
        $success_msg = "Customer deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Cannot delete customer because they have active orders associated with them.";
    }
}

// Fetch all customers, optional search filtering
$search = trim($_GET['search'] ?? '');
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT * FROM `customers` 
            WHERE `name` LIKE ? OR `contact_number` LIKE ? OR `customer_type` LIKE ? 
            ORDER BY `id` DESC
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM `customers` ORDER BY `id` DESC");
    }
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Failed to load customers: " . $e->getMessage();
    $customers = [];
}
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-users text-success me-2"></i>Customers Registry</h2>
        <p class="text-muted">View, search, edit, and delete system customers.</p>
    </div>
</div>

<div class="card main-card">
    <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <h5 class="mb-0 text-success"><i class="fa-solid fa-address-book me-2"></i>Customer Directory</h5>
        
        <!-- Search bar & Add Customer Button -->
        <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto justify-content-sm-end">
            <form action="customer_view.php" method="GET" class="d-flex align-items-center gap-1">
                <div class="input-group">
                    <input type="text" name="search" class="form-control form-control-custom py-1" 
                           placeholder="Search customer..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if (!empty($search)): ?>
                        <a href="customer_view.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-success py-1" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </form>
            <a href="customer_add.php" class="btn btn-success d-flex align-items-center gap-1">
                <i class="fa-solid fa-user-plus"></i> Add
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>
                <div><?php echo htmlspecialchars($success_msg); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <div><?php echo htmlspecialchars($error_msg); ?></div>
            </div>
        <?php endif; ?>

        <?php if (count($customers) > 0): ?>
            <div class="table-responsive table-responsive-custom">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Contact Number</th>
                            <th>Customer Type</th>
                            <th>Address</th>
                            <th>Login User</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($c['contact_number']); ?></td>
                                <td>
                                    <?php 
                                    $badge = 'bg-secondary';
                                    if ($c['customer_type'] === 'Food') $badge = 'bg-danger text-white';
                                    elseif ($c['customer_type'] === 'Snacks') $badge = 'bg-warning text-dark';
                                    elseif ($c['customer_type'] === 'Grocery') $badge = 'bg-success text-white';
                                    elseif ($c['customer_type'] === 'Stationery') $badge = 'bg-primary text-white';
                                    ?>
                                    <span class="badge badge-custom <?php echo $badge; ?>">
                                        <?php echo htmlspecialchars($c['customer_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($c['address']); ?>">
                                        <?php echo htmlspecialchars($c['address']); ?>
                                    </div>
                                </td>
                                <td class="font-monospace text-muted"><?php echo htmlspecialchars($c['username']); ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="customer_add.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-success" title="Edit Customer">
                                            <i class="fa-solid fa-user-pen"></i> Edit
                                        </a>
                                        <a href="customer_view.php?delete_id=<?php echo $c['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="btn btn-sm btn-outline-danger confirm-action" 
                                           data-confirm-message="Are you sure you want to delete customer '<?php echo htmlspecialchars($c['name']); ?>'?" 
                                           title="Delete Customer">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fa-regular fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">No customers found. <?php echo !empty($search) ? 'Try expanding your search query.' : 'Click "Add" to create one.'; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
