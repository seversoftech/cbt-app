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
<?php include '../includes/header.php';
include '../includes/admin_nav.php'; // Unified Admin Navbar
?>

<main style="padding-bottom: 4rem;">
    <div class="container" style="padding-top: 2rem;">
        <div class="card">
            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: var(--primary-light); color: var(--primary); padding: 0.75rem; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-user-shield" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.75rem;">Manage Admins</h2>
                        <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Control system access and administrator accounts</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-primary" onclick="openModal('addAdminModal')">
                        <i class="fa-solid fa-user-plus"></i> New Admin
                    </button>
                    <a href="dashboard.php" class="btn" style="background: var(--text-light); box-shadow: none;">
                        <i class="fa-solid fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div style="margin-bottom: 2rem; background: var(--bg-body); border-left: 4px solid var(--secondary); padding: 1rem; border-radius: var(--radius-md); color: var(--text-main); display: flex; align-items: center; gap: 10px; border: 1px solid var(--glass-border);">
                    <i class="fas fa-check-circle text-secondary"></i>
                    <span><?= $success ?></span>
                    <button onclick="this.parentElement.style.display='none'" style="margin-left: auto; background:none; border:none; cursor:pointer; color:inherit; font-size:1.2rem; opacity:0.5;">&times;</button>
                </div>
            <?php elseif ($error): ?>
                <div style="margin-bottom: 2rem; background: var(--bg-body); border-left: 4px solid var(--danger); padding: 1rem; border-radius: var(--radius-md); color: var(--text-main); display: flex; align-items: center; gap: 10px; border: 1px solid var(--glass-border);">
                    <i class="fas fa-exclamation-circle text-danger"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                    <button onclick="this.parentElement.style.display='none'" style="margin-left: auto; background:none; border:none; cursor:pointer; color:inherit; font-size:1.2rem; opacity:0.5;">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Admins Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="10%">ID</th>
                                <th width="40%">Administrator</th>
                                <th width="30%">Joined Date</th>
                                <th width="20%" style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($admins): ?>
                                <?php foreach ($admins as $a): ?>
                                    <tr>
                                        <td style="color: var(--text-light);">#<?= $a['id'] ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 32px; height: 32px; background: var(--bg-body); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); border: 1px solid var(--glass-border); font-weight: 800; font-size: 0.75rem;">
                                                    <?= strtoupper(substr($a['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <span style="font-weight: 600;"><?= htmlspecialchars($a['username']) ?></span>
                                                    <?php if ($a['id'] == ($_SESSION['admin_id'] ?? 1)): ?>
                                                        <span style="font-size: 0.65rem; background: var(--secondary); color: white; padding: 0.1rem 0.4rem; border-radius: 0.5rem; margin-left: 0.25rem; vertical-align: middle;">YOU</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="color: var(--text-light); font-size: 0.85rem;">
                                            <?= date('M j, Y', strtotime($a['created_at'])) ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($a['id'] != ($_SESSION['admin_id'] ?? 1)): ?>
                                                <div style="display: flex; gap: 0.4rem; justify-content: center;">
                                                    <button class="btn" style="padding: 0.4rem; background: var(--text-light); box-shadow: none;"
                                                        onclick="openEditModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['username']) ?>')" title="Change Password">
                                                        <i class="fa-solid fa-key"></i>
                                                    </button>
                                                    <button onclick="showConfirmDelete(<?= $a['id'] ?>)" class="btn btn-danger" style="padding: 0.4rem;" title="Remove Admin">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--text-light); font-size: 0.75rem; font-style: italic;">Protected</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding: 3rem; color: var(--text-light);">
                                        <i class="fa-solid fa-users-slash fa-3x" style="opacity: 0.2; display: block; margin-bottom: 1rem;"></i>
                                        No administrators found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

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
            <div class="modal-body" style="padding: 2rem;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="add_admin">
                
                <div style="margin-bottom: 1.5rem;">
                    <label><i class="fa-solid fa-user" style="margin-right: 0.5rem; opacity: 0.5;"></i> Username</label>
                    <input type="text" name="username" required placeholder="Pick a unique username">
                </div>
                
                <div style="margin-bottom: 0.5rem;">
                    <label><i class="fa-solid fa-lock" style="margin-right: 0.5rem; opacity: 0.5;"></i> Password</label>
                    <input type="password" name="password" required placeholder="Assign a secure password">
                </div>
                <p style="font-size: 0.75rem; color: var(--text-light); font-style: italic;">New admins will have full access to the control panel.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: var(--text-light); box-shadow: none;" onclick="closeModal('addAdminModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Password Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <form method="POST">
            <div class="modal-header">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--primary);">
                    <i class="fa-solid fa-key"></i> Reset Password
                </h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="edit_password">
                <input type="hidden" name="edit_id" id="editId">
                
                <div style="margin-bottom: 1rem;">
                    <label>
                        New Password for <strong id="editUser" style="color: var(--primary);"></strong>
                    </label>
                    <input type="password" id="edit_password_input" name="new_password" required placeholder="Enter new password">
                </div>
                <p style="font-size: 0.75rem; color: var(--text-light); font-style: italic;">This change will take effect immediately.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: var(--text-light); box-shadow: none;" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('active'); // CSS hook if needed
        modal.style.opacity = '1';
        modal.querySelector('.modal-content').style.transform = 'translateY(0)';
    }, 10);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.style.opacity = '0';
    modal.querySelector('.modal-content').style.transform = 'translateY(-20px)';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.querySelector('.modal-content').style.transform = '';
    }, 300);
}

function openEditModal(id, username) {
    document.getElementById('editId').value = id;
    document.getElementById('editUser').textContent = username;
    openModal('editModal');
}

function showConfirmDelete(id) {
    // We could use a custom modal here, but for now we'll use window.confirm
    // as refined in previous task if standardized.
    if (confirm('Permanently remove this administrator account?')) {
        window.location.href = '?delete=' + id;
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
}
</script>

<script src="../assets/js/script.js"></script>
</body>
</html>