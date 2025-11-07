<?php
require '../config/db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // Query for admin user
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin'] = true;
            $_SESSION['admin_id'] = $admin['id']; // Optional: Store ID for future use
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

include '../includes/header.php';
?>

<div class="card" style="max-width: 400px; margin: 2rem auto; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px;">
    <h2 style="text-align:center; margin-bottom: 1.5rem;">Admin Login</h2>

    <?php if (!empty($error)): ?>
        <p style="color: #dc2626; background:#fee2e2; border:1px solid #fecaca; padding:0.75rem; border-radius:5px; text-align:center;">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <label for="username" style="font-weight:600;">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter admin username" required
                   style="width: 100%; padding: 0.5rem; border:1px solid #d1d5db; border-radius:4px;">
        </div>

        <div>
            <label for="password" style="font-weight:600;">Password</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" placeholder="Enter password" required
                       style="width: 100%; padding: 0.5rem; border:1px solid #d1d5db; border-radius:4px;">
                <span id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6b7280;">🙈</span>
            </div>
        </div>

        <button type="submit" class="btn" style="padding: 0.75rem; font-size: 1rem;">Login</button>
    </form>

    <p style="text-align:center; color:#6b7280; margin-top:1rem; font-size:0.9rem;">
        © <?php echo date('Y'); ?> Seversoft CBT Admin Panel (Seversoft)
    </p>
</div>
<?php 
include '../includes/footer.php'; 
?>
<script>
// Toggle show/hide password
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

togglePassword.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    togglePassword.textContent = type === 'password' ? '🙈' : '👁️';
});
</script>

<script src="../assets/js/script.js"></script>
</body></html>