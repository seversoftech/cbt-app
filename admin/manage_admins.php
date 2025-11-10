<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$success = $error = null;

// CSRF setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ADD ADMIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_admin') {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));

    $password = trim($_POST['password']);
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request (CSRF).';
    } elseif (empty($username) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $success = "Admin <strong>$username</strong> added successfully.";
        } catch (PDOException $e) {
            $error = 'Username already exists or a database issue occurred.';
        }
    }
}

// DELETE ADMIN
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    if ($delete_id === 1) {
        $error = 'You cannot delete the super admin.';
    } elseif ($delete_id === ($_SESSION['admin_id'] ?? 1)) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = $stmt->rowCount() ? 'Admin deleted successfully.' : 'Error deleting admin.';
    }
}

// EDIT PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_password') {
    $id = (int) $_POST['edit_id'];
    $password = trim($_POST['new_password']);
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request (CSRF).';
    } elseif ($id === ($_SESSION['admin_id'] ?? 1)) {
        $error = 'You cannot update your password here.';
    } elseif (!empty($password)) {
        $stmt = $pdo->prepare("UPDATE admins SET password=? WHERE id=?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
        $success = $stmt->rowCount() ? 'Password updated successfully.' : 'Error updating password.';
    } else {
        $error = 'Password field cannot be empty.';
    }
}

// FETCH ADMINS
$stmt = $pdo->query("SELECT id, username, created_at FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../includes/header.php'; ?>

<!-- Bootstrap 5 + FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">


<style>


</style>

<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="card-title mb-0">
                    <i class="fa-solid fa-user-shield me-2 text-primary"></i>Manage Admins
                </h3>
                <a href="dashboard.php" class="btn-modern btn-modern-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Add Admin Button -->
            <button class="btn-modern btn-modern-primary mb-3" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fa-solid fa-user-plus"></i> Add New Admin
            </button>

            <!-- Admins Table -->
            <div class="table-responsive">
                <table class="table table-striped align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($admins): ?>
                            <?php foreach ($admins as $a): ?>
                                <tr>
                                    <td data-label="ID"><?= $a['id'] ?></td>
                                    <td data-label="Username"><?= htmlspecialchars($a['username']) ?></td>
                                    <td data-label="Created"><?= htmlspecialchars($a['created_at']) ?></td>
                                    <td data-label="Actions">
                                        <div class="btn-group-custom">
                                            <?php if ($a['id'] != ($_SESSION['admin_id'] ?? 1)): ?>
                                                <button class="btn-modern btn-modern-warning btn-modern-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editModal" data-id="<?= $a['id'] ?>" data-user="<?= htmlspecialchars($a['username']) ?>">
                                                    <i class="fa-solid fa-key"></i> Edit
                                                </button>
                                                <a href="?delete=<?= $a['id'] ?>" class="btn-modern btn-modern-danger btn-modern-sm"
                                                   onclick="return confirm('Delete this admin? This cannot be undone.');">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-success">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-muted">No admins found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-sm">
            <form method="POST">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Add New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="mb-3">
                        <label class="form-label"><i class="fa-solid fa-user me-1"></i>Username</label>
                        <input type="text" class="form-control" name="username" required placeholder="Enter username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fa-solid fa-lock me-1"></i>Password</label>
                        <input type="password" class="form-control" name="password" required placeholder="Enter password">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modern btn-modern-primary">
                        <i class="fa-solid fa-check"></i> Add Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Password Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-sm">
            <form method="POST" id="editForm">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fa-solid fa-key me-2 text-warning"></i>Edit Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="edit_password">
                    <input type="hidden" name="edit_id" id="editId">
                    <div class="mb-3">
                        <label class="form-label"><i class="fa-solid fa-user me-1"></i>New Password for <span id="editUser" class="fw-bold"></span></label>
                        <input type="password" name="new_password" class="form-control" required placeholder="Enter new password">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modern btn-modern-warning">
                        <i class="fa-solid fa-save"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div><?php 
include '../includes/footer.php'; 
?>

<script>
const editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const user = button.getAttribute('data-user');
    document.getElementById('editId').value = id;
    document.getElementById('editUser').textContent = user;
});
</script>

</body>
</html>