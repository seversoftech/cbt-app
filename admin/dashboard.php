<?php 
require '../config/db.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

// Stats queries
$q_stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
$total_questions = $q_stmt->fetch()['total'];

$c_stmt = $pdo->query("SELECT COUNT(DISTINCT category) as total_categories FROM questions");
$total_categories = $c_stmt->fetch()['total_categories'];

$r_stmt = $pdo->query("SELECT COUNT(*) as total, AVG(percentage) as avg_score FROM results");
$results = $r_stmt->fetch();
$total_tests = $results['total'];
$avg_score = round($results['avg_score'] ?? 0, 1);

// Recent Activity Query
$recent_stmt = $pdo->query("SELECT * FROM results ORDER BY completed_at DESC LIMIT 5");
$recent_activities = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php'; 
?>

<!-- Include Unified Admin Navbar -->
<?php include '../includes/admin_nav.php'; ?>

<main style="padding-bottom: 4rem;">
    <div class="container">
        
        <!-- Welcome Hero -->
        <div class="card" style="margin-top: 0; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%); color: white; border: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="color: white; margin-bottom: 0.5rem; font-size: 1.75rem;">Dashboard Overview</h2>
                    <p style="color: rgba(255,255,255,0.9); margin-bottom: 0;">Welcome back, Admin. Here is your academy's performance.</p>
                </div>
                <div>
                     <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.9rem; font-weight: 600;">
                        <?php echo date('F j, Y'); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                 <div class="stat-icon">
                    <i class="fas fa-question"></i>
                </div>
                <p class="stat-value"><?php echo number_format($total_questions); ?></p>
                <h3>Total Questions</h3>
            </div>

            <div class="stat-card clickable" id="subjectsCard" style="cursor: pointer;">
                 <div class="stat-icon" style="color: var(--secondary); background: #ecfdf5;">
                    <i class="fas fa-book"></i>
                </div>
                <p class="stat-value"><?php echo $total_categories; ?></p>
                <h3>Active Subjects</h3>
            </div>

            <div class="stat-card">
                 <div class="stat-icon" style="color: var(--warning); background: #fffbeb;">
                    <i class="fas fa-users"></i>
                </div>
                <p class="stat-value"><?php echo number_format($total_tests); ?></p>
                <h3>Tests Taken</h3>
            </div>

            <div class="stat-card clickable" onclick="window.location.href='analytics.php'" style="cursor: pointer;">
                 <div class="stat-icon" style="color: #db2777; background: #fce7f3;">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <p class="stat-value"><?php echo $avg_score; ?>%</p>
                <h3>Avg. Score (Analytics)</h3>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            
            <!-- Quick Actions -->
            <section>
                 <div class="card" style="margin: 0; height: 100%;">
                    <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                        <i class="fas fa-rocket text-primary"></i> Quick Actions
                    </h3>
                    <div class="actions-grid" style="grid-template-columns: 1fr 1fr;">
                         <a href="add_question.php" class="action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span style="font-weight: 600;">Add Question</span>
                        </a>
                         <a href="upload_excel.php" class="action-btn">
                            <i class="fas fa-file-excel" style="color: var(--secondary);"></i>
                            <span style="font-weight: 600;">Import Excel</span>
                        </a>
                         <a href="view_results.php" class="action-btn">
                            <i class="fas fa-chart-bar" style="color: #0ea5e9;"></i>
                            <span style="font-weight: 600;">View Results</span>
                        </a>
                         <a href="manage_admins.php" class="action-btn">
                            <i class="fas fa-user-plus" style="color: var(--text-light);"></i>
                            <span style="font-weight: 600;">Manage Admins</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Recent Activity -->
             <section>
                <div class="card" style="margin: 0; padding: 0; overflow: hidden; height: 100%;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--glass-border); display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin: 0;"><i class="fas fa-history text-secondary"></i> Recent Activity</h3>
                        <a href="view_results.php" style="font-size:0.9rem; color:var(--primary); text-decoration:none;">View All</a>
                    </div>
                    
                    <?php if(empty($recent_activities)): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-light);">No recent activity found.</div>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-body);">
                                    <th style="font-size: 0.8rem; padding: 1rem; text-align: left;">Student</th>
                                    <th style="font-size: 0.8rem; padding: 1rem; text-align: left;">Subject</th>
                                    <th style="font-size: 0.8rem; padding: 1rem; text-align: center;">Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_activities as $act): 
                                    $scoreClass = $act['percentage'] >= 50 ? 'color: var(--secondary);' : 'color: var(--danger);';
                                    $bgClass = $act['percentage'] >= 50 ? 'background: rgba(16, 185, 129, 0.1);' : 'background: rgba(239, 68, 68, 0.1);';
                                ?>
                                <tr style="border-bottom: 1px solid var(--glass-border);">
                                    <td style="padding: 1rem;">
                                        <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($act['student_id']); ?></div>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <div style="font-size: 0.85rem; font-weight: 500; color: var(--text-main);"><?php echo htmlspecialchars($act['subject']); ?></div>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 700; <?php echo $scoreClass . $bgClass; ?>">
                                            <?php echo $act['percentage']; ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

        </div>

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
// Standard footer with scripts
include '../includes/footer.php'; 
?>

<script>
    // Modal script
    document.addEventListener('DOMContentLoaded', function() {
        const subjectsCard = document.getElementById('subjectsCard');
        const modal = document.getElementById('subjectsModal');
        
        if (subjectsCard) {
            subjectsCard.addEventListener('click', loadSubjectsModal);
        }
        
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        const closeBtn = modal.querySelector('.close');
        if(closeBtn) closeBtn.addEventListener('click', closeModal);
    });

    async function loadSubjectsModal() {
        const list = document.getElementById('subjectsList');
        document.getElementById('subjectsModal').style.display = 'flex';
        list.innerHTML = '<div style="text-align:center; padding: 2rem;"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>';
        
        try {
            const response = await fetch('../config/get_categories.php?with_counts=1');
            const subjects = await response.json();
            
            if (subjects.length === 0) list.innerHTML = '<p>No subjects.</p>';
            else {
                let html = '<div style="display: grid; gap: 0.5rem; max-height: 400px; overflow-y: auto; padding-right: 0.5rem;">';
                subjects.forEach(s => {
                    html += `<a href="view_questions.php?category=${encodeURIComponent(s.category)}" 
                        style="display: flex; justify-content: space-between; padding: 1rem; background: var(--bg-body); border-radius: 0.5rem; text-decoration: none; color: var(--text-main); transition: var(--transition); border: 1px solid transparent;"
                        onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateX(5px)'"
                        onmouseout="this.style.borderColor='transparent'; this.style.transform='translateX(0)'">
                        <strong style="color: var(--primary);">${s.category}</strong> 
                        <span style="background: var(--primary-light); padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 700;">${s.count} Qs</span>
                    </a>`;
                });
                list.innerHTML = html + '</div>';
            }
        } catch (e) { list.innerHTML = 'Error loading.'; }
    }
    function closeModal() { document.getElementById('subjectsModal').style.display = 'none'; }
</script>
</body>
</html>