<?php
require '../config/db.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

// ====== DATA QUERIES ======

// 1. Overall Key Stats
$kpi_stmt = $pdo->query("SELECT 
    COUNT(*) as total_tests, 
    AVG(percentage) as overall_avg,
    SUM(CASE WHEN percentage >= 50 THEN 1 ELSE 0 END) as passed_count,
    MAX(percentage) as max_score,
    MIN(percentage) as min_score
    FROM results");
$kpi = $kpi_stmt->fetch(PDO::FETCH_ASSOC);

$total_tests = $kpi['total_tests'] ?: 0;
$avg_score = round($kpi['overall_avg'] ?: 0, 1);
$pass_count = $kpi['passed_count'] ?: 0;
$fail_count = $total_tests - $pass_count;
$pass_rate = $total_tests > 0 ? round(($pass_count / $total_tests) * 100, 1) : 0;

// 2. Subject Performance (Bar Chart Data)
$sub_stmt = $pdo->query("SELECT 
    subject, 
    COUNT(*) as attempts, 
    AVG(percentage) as avg_pct 
    FROM results 
    GROUP BY subject 
    ORDER BY avg_pct DESC");
$subject_data = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare Chart Arrays
$chart_labels = [];
$chart_scores = [];
$chart_attempts = [];
foreach ($subject_data as $row) {
    // Limit label length for chart cleanliness
    $chart_labels[] = strlen($row['subject']) > 15 ? substr($row['subject'], 0, 15).'...' : $row['subject'];
    $chart_scores[] = round($row['avg_pct'], 1);
    $chart_attempts[] = $row['attempts'];
}

// 3. Top Performers (Leaderboard)
$top_stmt = $pdo->query("SELECT student_id, subject, percentage, completed_at FROM results ORDER BY percentage DESC, completed_at DESC LIMIT 10");
$top_performers = $top_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/admin_nav.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main style="padding-bottom: 4rem;">
    <div class="container" style="padding-top: 2rem;">
        
        <!-- Header -->
        <div class="card" style="margin-top: 0; background: linear-gradient(135deg, #db2777 0%, #be185d 100%); color: white; border: none; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="color: white; margin-bottom: 0.5rem; font-size: 1.75rem;"><i class="fas fa-chart-line"></i> Analytics Dashboard</h2>
                    <p style="color: rgba(255,255,255,0.9); margin-bottom: 0;">Deep dive into student performance and assessment trends.</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- KPI Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card" style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;"><?php echo $avg_score; ?>%</div>
                <div style="color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; font-weight: 700;">Average Score</div>
            </div>
            <div class="card" style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; font-weight: 800; color: var(--secondary); margin-bottom: 0.5rem;"><?php echo $pass_rate; ?>%</div>
                <div style="color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; font-weight: 700;">Pass Rate</div>
            </div>
            <div class="card" style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; font-weight: 800; color: var(--dark); margin-bottom: 0.5rem;"><?php echo number_format($total_tests); ?></div>
                <div style="color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; font-weight: 700;">Total Assessments</div>
            </div>
            <div class="card" style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; font-weight: 800; color: #f59e0b; margin-bottom: 0.5rem;"><?php echo number_format($kpi['max_score'] ?: 0); ?>%</div>
                <div style="color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; font-weight: 700;">Highest Score</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <!-- Subject Performance Chart -->
            <div class="card" style="height: 100%;">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                    <i class="fas fa-flask text-primary"></i> Subject Performance
                </h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="subjectChart"></canvas>
                </div>
            </div>

            <!-- Pass/Fail Chart -->
            <div class="card" style="height: 100%;">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                    <i class="fas fa-adjust text-secondary"></i> Success Ratio
                </h3>
                <div style="position: relative; height: 300px; display: flex; justify-content: center;">
                    <canvas id="passFailChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Tables -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
            
            <!-- Subject Breakdown Table -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                    <i class="fas fa-list-alt"></i> Subject Breakdown
                </h3>
                <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr style="position: sticky; top: 0; background: white; z-index: 1;">
                                <th>Subject</th>
                                <th style="text-align: center;">Attempts</th>
                                <th style="text-align: right;">Avg. Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($subject_data as $sub): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($sub['subject']); ?></td>
                                    <td style="text-align: center;">
                                        <span style="background: var(--bg-body); padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.8rem;">
                                            <?php echo $sub['attempts']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; color: var(--primary); font-weight: 700;">
                                        <?php echo round($sub['avg_pct'], 1); ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                    <i class="fas fa-trophy" style="color: #f59e0b;"></i> Top Performers
                </h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student</th>
                                <th>Subject</th>
                                <th style="text-align: right;">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_performers as $i => $row): ?>
                                <tr>
                                    <td>
                                        <?php if($i == 0): ?>     <i class="fas fa-medal" style="color: #ffd700;"></i>
                                        <?php elseif($i == 1): ?> <i class="fas fa-medal" style="color: #c0c0c0;"></i>
                                        <?php elseif($i == 2): ?> <i class="fas fa-medal" style="color: #cd7f32;"></i>
                                        <?php else: echo $i + 1; endif; ?>
                                    </td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($row['student_id']); ?></td>
                                    <td style="font-size: 0.9rem; color: var(--text-light);"><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td style="text-align: right; color: var(--secondary); font-weight: 800;">
                                        <?php echo $row['percentage']; ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</main>

<!-- Chart Initialization -->
<script>
    // 1. Subject Performance Bar Chart
    const ctxSubject = document.getElementById('subjectChart').getContext('2d');
    new Chart(ctxSubject, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Average Score (%)',
                data: <?php echo json_encode($chart_scores); ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.6)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // 2. Pass/Fail Doughnut Chart
    const ctxPassFail = document.getElementById('passFailChart').getContext('2d');
    new Chart(ctxPassFail, {
        type: 'doughnut',
        data: {
            labels: ['Passed', 'Failed'],
            datasets: [{
                data: [<?php echo $pass_count; ?>, <?php echo $fail_count; ?>],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)', // Green for pass
                    'rgba(239, 68, 68, 0.8)'   // Red for fail
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>
