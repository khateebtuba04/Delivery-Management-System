<?php
// admin/users.php
$page_title = "User Management | QuickShip";
$required_role = "Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$success_msg = "";
$error_msg = "";

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Admin';
        
        if (empty($username) || empty($password)) {
            $error_msg = "Username and password are required.";
        } else {
            try {
                // Check if username already exists in users table
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
                $stmtCheck->execute([$username]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $error_msg = "Username '$username' is already taken.";
                } else {
                    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                    $stmtInsert = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `role`) VALUES (?, ?, ?)");
                    $stmtInsert->execute([$username, $hashed_pass, $role]);
                    $success_msg = "System user '$username' added successfully.";
                }
            } catch (PDOException $e) {
                $error_msg = "Error adding user: " . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Admin';
        
        if (empty($username) || $id <= 0) {
            $error_msg = "Username is required and user must be valid.";
        } else {
            try {
                // Check for duplicate username
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ? AND `id` != ?");
                $stmtCheck->execute([$username, $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $error_msg = "Username '$username' is already taken by another user.";
                } else {
                    if (!empty($password)) {
                        // Update including password
                        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                        $stmtUpdate = $pdo->prepare("UPDATE `users` SET `username` = ?, `password` = ?, `role` = ? WHERE `id` = ?");
                        $stmtUpdate->execute([$username, $hashed_pass, $role, $id]);
                    } else {
                        // Update excluding password
                        $stmtUpdate = $pdo->prepare("UPDATE `users` SET `username` = ?, `role` = ? WHERE `id` = ?");
                        $stmtUpdate->execute([$username, $role, $id]);
                    }
                    
                    // If the logged-in user edited their own username, update session
                    if ($_SESSION['user_id'] == $id && $_SESSION['role'] === 'Admin') {
                        $_SESSION['username'] = $username;
                    }
                    
                    $success_msg = "User updated successfully.";
                }
            } catch (PDOException $e) {
                $error_msg = "Error updating user: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id === intval($_SESSION['user_id'])) {
            $error_msg = "You cannot delete your own logged-in administrator account.";
        } else {
            try {
                $stmtDelete = $pdo->prepare("DELETE FROM `users` WHERE `id` = ?");
                $stmtDelete->execute([$id]);
                $success_msg = "User deleted successfully.";
            } catch (PDOException $e) {
                $error_msg = "Error deleting user: " . $e->getMessage();
            }
        }
    }
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT `id`, `username`, `role`, `created_at` FROM `users` ORDER BY `username` ASC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Failed to load users: " . $e->getMessage();
    $users = [];
}
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-user-gear text-success me-2"></i>User Management</h2>
        <p class="text-muted">Configure administrative credentials and system staff roles.</p>
    </div>
</div>

<div class="card main-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-success"><i class="fa-solid fa-users-gear me-2"></i>System Users</h5>
        <button class="btn btn-success d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa-solid fa-user-plus"></i> Add Admin
        </button>
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

        <div class="table-responsive table-responsive-custom">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>User Role</th>
                        <th>Created At</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="font-monospace">#<?php echo $u['id']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td>
                                <span class="badge badge-custom bg-success text-white">
                                    <i class="fa-solid fa-shield-halved me-1" style="font-size: 0.65rem;"></i>
                                    <?php echo htmlspecialchars($u['role']); ?>
                                </span>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem;">
                                <?php echo date("M d, Y", strtotime($u['created_at'])); ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-success edit-user-btn" 
                                            data-id="<?php echo $u['id']; ?>" 
                                            data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                            data-role="<?php echo htmlspecialchars($u['role']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            title="Edit User">
                                        <i class="fa-solid fa-user-pen"></i> Edit
                                    </button>
                                    
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <form action="users.php" method="POST" class="d-inline confirm-action" 
                                              data-confirm-message="Are you sure you want to delete user '<?php echo htmlspecialchars($u['username']); ?>'?">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="users.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="fa-solid fa-user-plus me-2"></i>Add Admin User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_username" class="form-label form-label-custom">Username</label>
                        <input type="text" class="form-control form-control-custom" id="add_username" name="username" placeholder="e.g. operations_lead" required>
                        <div class="invalid-feedback">Please enter a username.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label form-label-custom">Password</label>
                        <input type="password" class="form-control form-control-custom" id="add_password" name="password" placeholder="••••••••" required>
                        <div class="invalid-feedback">Please enter a password.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_role" class="form-label form-label-custom">User Role</label>
                        <select class="form-select form-control-custom" id="add_role" name="role" required>
                            <option value="Admin" selected>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="users.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editUserModalLabel"><i class="fa-solid fa-user-pen me-2"></i>Edit System User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label form-label-custom">Username</label>
                        <input type="text" class="form-control form-control-custom" id="edit_username" name="username" required>
                        <div class="invalid-feedback">Please enter a username.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label form-label-custom">Password <small class="text-muted">(leave blank to keep current)</small></label>
                        <input type="password" class="form-control form-control-custom" id="edit_password" name="password" placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label form-label-custom">User Role</label>
                        <select class="form-select form-control-custom" id="edit_role" name="role" required>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script to populate Edit Modal dynamically -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const editButtons = document.querySelectorAll(".edit-user-btn");
    
    editButtons.forEach(button => {
        button.addEventListener("click", function () {
            const id = this.getAttribute("data-id");
            const username = this.getAttribute("data-username");
            const role = this.getAttribute("data-role");
            
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_username").value = username;
            document.getElementById("edit_role").value = role;
            // Clear password field in case it had entries
            document.getElementById("edit_password").value = "";
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
