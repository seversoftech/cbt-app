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

<header class="admin-header">
    <div class="header-content">
        <div class="header-left">
            <h1>
                <i class="fas fa-layer-group"></i>
                Admin Panel
            </h1>
        </div>
        <div class="header-right">
            <a href="../logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

<main class="admin-main">
    <div class="container">
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-question-circle stat-icon"></i>
                    <p class="stat-value"><?php echo $total_questions; ?></p>
                    <h3>Total Questions</h3>
                </div>
                <div class="stat-card clickable" id="subjectsCard" style="cursor: pointer;">
                    <i class="fas fa-book stat-icon"></i>
                    <p class="stat-value"><?php echo $total_categories; ?></p>
                    <h3>Subjects <i class="fas fa-chevron-right" style="font-size: 0.8rem; margin-left: 0.3rem; opacity: 0.5;"></i></h3>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clipboard-list stat-icon"></i>
                    <p class="stat-value"><?php echo $total_tests; ?></p>
                    <h3>Tests Taken</h3>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line stat-icon"></i>
                    <p class="stat-value"><?php echo $avg_score; ?>%</p>
                    <h3>Avg Score</h3>
                </div>
            </div>
        </section>

        <section class="actions-section">
            <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Quick Actions</h2>
            <div class="actions-grid">
                <a href="add_question.php" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Question</span>
                </a>
                <a href="upload_excel.php" class="action-btn">
                    <i class="fas fa-file-csv"></i>
                    <span>Upload Excel</span>
                </a>
                <a href="view_questions.php" class="action-btn">
                    <i class="fas fa-edit"></i>
                    <span>Manage Q's</span>
                </a>
                <a href="view_results.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <span>Results</span>
                </a>
                <a href="manage_admins.php" class="action-btn">
                    <i class="fas fa-user-cog"></i>
                    <span>Admins</span>
                </a>
            </div>
        </section>
    </div>
</main>

<!-- Subjects Modal -->
<div id="subjectsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Subjects Overview</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="subjectsList">Loading subjects...</div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<?php 
include '../includes/footer.php'; 
?>

<script src="../assets/js/script.js"></script>
<script>
    // Modal functionality for subjects
    document.addEventListener('DOMContentLoaded', function() {
        const subjectsCard = document.getElementById('subjectsCard');
        const modal = document.getElementById('subjectsModal');
        const closeBtn = document.querySelector('.close');

        if (subjectsCard) {
            subjectsCard.addEventListener('click', loadSubjectsModal);
        }
        
        // Close on outside click is handled by CSS/JS combination usually, but ensuring inline script works
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    });

    async function loadSubjectsModal() {
        const modal = document.getElementById('subjectsModal');
        const list = document.getElementById('subjectsList');
        list.innerHTML = '<p>Loading subjects...</p>';

        try {
            // Fetch subjects with counts (update get_categories.php to return with counts if needed)
            const response = await fetch('../config/get_categories.php?with_counts=1');
            if (!response.ok) throw new Error('Failed to load subjects');
            const subjects = await response.json();

            if (subjects.length === 0) {
                list.innerHTML = '<p>No subjects available.</p>';
            } else {
                let html = '<ul class="subjects-ul" style="list-style: none; padding: 0;">';
                subjects.forEach(subject => {
                    html += `<li style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6;">
                                <strong>${subject.category}</strong> 
                                <span class="subject-count" style="color: #6b7280; font-size: 0.9rem;">(${subject.count} Qs)</span>
                             </li>`;
                });
                html += '</ul>';
                list.innerHTML = html;
            }
        } catch (error) {
            console.error('Error loading subjects:', error);
            list.innerHTML = '<p>Error loading subjects. Please try again.</p>';
        }

        modal.style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('subjectsModal').style.display = 'none';
    }
</script>
</body>
</html>