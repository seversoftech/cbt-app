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
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
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
/* --- BUTTON STYLING --- */
.btn-modern {
    border: none;
    padding: 0.45rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.2s ease-in-out;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

.btn-modern i { font-size: 0.9rem; }

/* Primary */
.btn-modern-primary {
    background: linear-gradient(90deg, #2563eb, #3b82f6);
    color: #fff;
}
.btn-modern-primary:hover {
    background: linear-gradient(90deg, #1d4ed8, #2563eb);
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
}

/* Warning */
.btn-modern-warning {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
    color: #fff;
}
.btn-modern-warning:hover {
    background: linear-gradient(90deg, #d97706, #f59e0b);
    box-shadow: 0 4px 10px rgba(245, 158, 11, 0.4);
}

/* Danger */
.btn-modern-danger {
    background: linear-gradient(90deg, #dc2626, #ef4444);
    color: #fff;
}
.btn-modern-danger:hover {
    background: linear-gradient(90deg, #b91c1c, #dc2626);
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
}

/* Secondary */
.btn-modern-secondary {
    background: #e5e7eb;
    color: #374151;
}
.btn-modern-secondary:hover {
    background: #d1d5db;
    box-shadow: 0 3px 8px rgba(156, 163, 175, 0.3);
}

/* Small buttons (table) */
.btn-modern-sm {
    font-size: 0.85rem;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
}

.btn-group-custom {
    display: flex;
    gap: 0.4rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-modern:active {
    transform: scale(0.97);
}

/* Table tweaks */
.table thead {
    background-color: #f8fafc;
    font-weight: 600;
}
.table td, .table th {
    vertical-align: middle;
}

/* Card enhancement */
.card {
    border: none;
    border-radius: 12px;
}
.card-body {
    padding: 2rem;
}

/* Mobile Responsive Improvements */
@media (max-width: 768px) {
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .btn-modern {
        padding: 0.6rem 1.2rem;
        font-size: 1rem;
    }
    
    .btn-modern-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    .btn-group-custom {
        flex-direction: column;
        gap: 0.25rem;
        width: 100%;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .table th, .table td {
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
    }
    
    /* Stack table cells for better mobile view */
    .table {
        border: 0;
    }
    
    .table thead {
        display: none;
    }
    
    .table tr {
        display: block;
        margin-bottom: 0.625rem;
        background: #f8f9fa;
        border-radius: 6px;
        padding: 0.75rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .table td {
        display: block;
        text-align: right;
        font-size: 0.875rem;
        border-bottom: 1px dotted #dee2e6;
        padding: 0.25rem 0;
        position: relative;
        padding-left: 50%;
    }
    
    .table td::before {
        content: attr(data-label);
        position: absolute;
        left: 0.5rem;
        width: 45%;
        font-weight: 600;
        text-align: left;
        color: #6c757d;
    }
    
    .table td:last-child {
        border-bottom: 0;
    }
    
    /* Modal adjustments */
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 0.75rem 1rem;
    }
    
    .btn-group-custom .btn-modern-sm {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .card-title {
        text-align: center;
    }
    
    .btn-modern {
        width: 100%;
        justify-content: center;
    }
}
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
</div>

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