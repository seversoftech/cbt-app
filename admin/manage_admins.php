<?php
require '../config/db.php';


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

<!-- Content Wrapper -->
<div class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="background: var(--primary-light); color: var(--primary); padding: 0.75rem; border-radius: 50%; font-size: 1.5rem;">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h2 style="margin: 0;">Manage Admins</h2>
            </div>
            <a href="dashboard.php" class="btn" style="background: var(--text-light); box-shadow: none;">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div style="background-color: #ecfdf5; border-left: 4px solid var(--secondary); color: #065f46; padding: 1rem; margin-bottom: 2rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $success ?></span>
                </div>
                <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; color:inherit; font-size:1.2rem;">&times;</button>
            </div>
        <?php elseif ($error): ?>
            <div style="background-color: #fee2e2; border-left: 4px solid var(--danger); color: #b91c1c; padding: 1rem; margin-bottom: 2rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; color:inherit; font-size:1.2rem;">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Add Admin Button -->
        <button class="btn" onclick="openModal('addAdminModal')">
            <i class="fa-solid fa-user-plus"></i> Add New Admin
        </button>

        <!-- Admins Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="10%">ID</th>
                        <th width="35%">Username</th>
                        <th width="35%">Created</th>
                        <th width="20%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($admins): ?>
                        <?php foreach ($admins as $a): ?>
                            <tr>
                                <td>#<?= $a['id'] ?></td>
                                <td style="font-weight: 600; color: var(--dark);"><?= htmlspecialchars($a['username']) ?></td>
                                <td style="color: var(--text-light);"><?= htmlspecialchars($a['created_at']) ?></td>
                                <td>
                                    <?php if ($a['id'] != ($_SESSION['admin_id'] ?? 1)): ?>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn" style="padding: 0.5rem 1rem; font-size: 0.85rem; background: var(--warning); box-shadow: none;"
                                                onclick="openEditModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['username']) ?>')">
                                                <i class="fa-solid fa-key"></i> Edit
                                            </button>
                                            <a href="?delete=<?= $a['id'] ?>" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                                               onclick="return confirm('Delete this admin? This cannot be undone.');">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span style="display: inline-block; padding: 0.25rem 0.75rem; background: var(--secondary); color: white; border-radius: 1rem; font-size: 0.85rem; font-weight: 600;">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 2rem; color: var(--text-light);">No admins found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="modal">
    <div class="modal-content">
        <form method="POST">
            <div class="modal-header">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--primary);">
                    <i class="fa-solid fa-user-plus"></i> Add New Admin
                </h3>
                <span class="close" onclick="closeModal('addAdminModal')">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="add_admin">
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="new_username"><i class="fa-solid fa-user me-1"></i> Username</label>
                    <input type="text" id="new_username" name="username" required placeholder="Enter username">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label for="new_password"><i class="fa-solid fa-lock me-1"></i> Password</label>
                    <input type="password" id="new_password" name="password" required placeholder="Enter password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: var(--text-light); box-shadow: none;" onclick="closeModal('addAdminModal')">Cancel</button>
                <button type="submit" class="btn">Add Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Password Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <form method="POST">
            <div class="modal-header">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--warning);">
                    <i class="fa-solid fa-key"></i> Edit Password
                </h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="edit_password">
                <input type="hidden" name="edit_id" id="editId">
                
                <div style="margin-bottom: 1rem;">
                    <label for="edit_password_input">
                        <i class="fa-solid fa-user me-1"></i> New Password for <span id="editUser" style="color: var(--primary); font-weight: 700;"></span>
                    </label>
                    <input type="password" id="edit_password_input" name="new_password" required placeholder="Enter new password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: var(--text-light); box-shadow: none;" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);">Update Password</button>
            </div>
        </form>
    </div>
</div>

<?php 
include '../includes/footer.php'; 
?>

<script>
// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.querySelector('.modal-content').style.transform = 'scale(1)';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.opacity = '0';
        modal.querySelector('.modal-content').style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

function openEditModal(id, username) {
    document.getElementById('editId').value = id;
    document.getElementById('editUser').textContent = username;
    openModal('editModal');
}

// Close modal if clicked outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
}
</script>

<script src="../assets/js/script.js"></script>
</body>
</html>