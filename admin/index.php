<?php
require '../config/db.php';


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

<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <i class="fas fa-user-shield"></i>
                <h2>Admin Login</h2>
                <p>Secure access to the dashboard</p>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; color: #b91c1c; padding: 1rem; margin-bottom: 2rem; border-radius: 0.5rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label for="username">Username</label>
                    <div style="position: relative;">
                         <input type="text" id="username" name="username" placeholder="Enter admin username" required
                           style="padding-left: 3rem;">
                         <i class="fas fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <label for="password">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" placeholder="Enter password" required
                               style="padding-left: 3rem;">
                        <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <span id="togglePassword" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light);">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%; justify-content: center; padding: 1rem; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    Sign In <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                </button>
            </form>

            <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: var(--text-light);">
                <p>Copyright © <?php echo date('Y'); ?> Seversoft CBT</p>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Toggle show/hide password
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const icon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            // Toggle icon class
            if (type === 'password') {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
    </script>