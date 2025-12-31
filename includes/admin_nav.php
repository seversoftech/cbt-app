<?php
// Function to check active state
function isActive($page) {
    if (basename($_SERVER['PHP_SELF']) == $page) {
        return 'active';
    }
    return '';
}
?>
<nav class="admin-navbar">
    <div class="container nav-content">
        <a href="dashboard.php" class="nav-brand">
            <i class="fas fa-layer-group"></i> CBT Admin
        </a>
        <ul class="nav-links">
            <li>
                <a href="dashboard.php" class="nav-item-link <?php echo isActive('dashboard.php'); ?>">
                    <i class="fas fa-chart-pie"></i> <span class="d-none d-md-inline">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="view_questions.php" class="nav-item-link <?php echo isActive('view_questions.php'); ?> <?php echo isActive('add_question.php'); ?> <?php echo isActive('upload_excel.php'); ?>">
                    <i class="fas fa-file-alt"></i> <span class="d-none d-md-inline">Questions</span>
                </a>
            </li>
            <li>
                <a href="view_results.php" class="nav-item-link <?php echo isActive('view_results.php'); ?>">
                    <i class="fas fa-chart-bar"></i> <span class="d-none d-md-inline">Results</span>
                </a>
            </li>
             <li>
                <a href="manage_admins.php" class="nav-item-link <?php echo isActive('manage_admins.php'); ?>">
                    <i class="fas fa-user-shield"></i> <span class="d-none d-md-inline">Admins</span>
                </a>
            </li>
            <li>
                <a href="../logout.php" class="nav-item-link" style="color: var(--danger);">
                    <i class="fas fa-sign-out-alt"></i> <span class="d-none d-md-inline">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
<!-- Responsive helper for hiding text on small screens, can be moved to CSS -->
<style>
@media(max-width: 768px) {
    .d-none { display: none !important; }
    .d-md-inline { display: none !important; }
}
@media(max-width: 600px) {
    .d-md-inline { display: none; }
}
</style>
