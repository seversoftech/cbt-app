<?php require '../config/db.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

// Stats queries
$q_stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
$total_questions = $q_stmt->fetch()['total'];

$c_stmt = $pdo->query("SELECT COUNT(DISTINCT category) as total_categories FROM questions");
$total_categories = $c_stmt->fetch()['total_categories'];

$r_stmt = $pdo->query("SELECT COUNT(*) as total, AVG(percentage) as avg_score FROM results");
$results = $r_stmt->fetch();
$total_tests = $results['total'];
$avg_score = round($results['avg_score'] ?? 0, 2);
?>
<?php include '../includes/header.php'; ?>
<!-- Add Font Awesome for icons (add to header.php if not: <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> -->

<header class="admin-header">
    <div class="header-content">
        <div class="header-left">
            <i class="fas fa-user-shield"></i>
            <h1>Admin Panel</h1>
        </div>
        <div class="header-right">
            <a href="../logout.php" class="btn btn-danger" style="margin-left: auto;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

<main class="admin-main">
    <div class="container">
        <section class="stats-section">
            <h2>Dashboard Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-question-circle stat-icon questions"></i>
                    <h3>Total Questions</h3>
                    <p class="stat-value"><?php echo $total_questions; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-book stat-icon subjects"></i>
                    <h3>Total Subjects</h3>
                    <p class="stat-value"><?php echo $total_categories; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clipboard-list stat-icon tests"></i>
                    <h3>Total Tests Taken</h3>
                    <p class="stat-value"><?php echo $total_tests; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line stat-icon score"></i>
                    <h3>Average Score</h3>
                    <p class="stat-value"><?php echo $avg_score; ?>%</p>
                </div>
            </div>
        </section>

        <section class="actions-section">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="add_question.php" class="action-btn">
                    <i class="fas fa-plus"></i>
                    <span>Add New Question</span>
                </a>
                <a href="upload_excel.php" class="action-btn">
                    <i class="fas fa-file-excel"></i>
                    <span>Upload Excel</span>
                </a>
                <a href="view_questions.php" class="action-btn">
                    <i class="fas fa-list"></i>
                    <span>Manage Questions</span>
                </a>
                <a href="view_results.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Results</span>
                </a>
                <a href="manage_admins.php" class="action-btn">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Admins</span>
                </a>
            </div>
        </section>
    </div>
</main>

<script src="../assets/js/script.js"></script>
</body>
</html>