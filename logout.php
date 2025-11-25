<?php
session_start();          // Start the session
session_unset();          // Clear all session variables
session_destroy();        // Destroy the session

// Optional: delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: admin/index.php');
exit;
