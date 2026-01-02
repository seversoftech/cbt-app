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

    // 4. Score Distribution (Histogram)
    $dist_stmt = $pdo->query("SELECT 
        CASE 
            WHEN percentage BETWEEN 0 AND 19 THEN '0-19%'
            WHEN percentage BETWEEN 20 AND 39 THEN '20-39%'
            WHEN percentage BETWEEN 40 AND 59 THEN '40-59%'
            WHEN percentage BETWEEN 60 AND 79 THEN '60-79%'
            WHEN percentage BETWEEN 80 AND 100 THEN '80-100%'
        END as score_range,
        COUNT(*) as count
        FROM results
        GROUP BY score_range
        ORDER BY score_range");
    $dist_data = $dist_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Normalize distribution data (ensure all ranges exist)
    $ranges = ['0-19%', '20-39%', '40-59%', '60-79%', '80-100%'];
    $chart_dist_counts = [];
    foreach ($ranges as $r) {
        $chart_dist_counts[] = $dist_data[$r] ?? 0;
    }

    // 5. Trend Analysis (Last 7 Days)
    $trend_stmt = $pdo->query("SELECT 
        DATE(completed_at) as test_date, 
        AVG(percentage) as daily_avg 
        FROM results 
        WHERE completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(completed_at) 
        ORDER BY test_date ASC");
    $trend_data = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $chart_trend_labels = [];
    $chart_trend_scores = [];
    foreach ($trend_data as $row) {
        $chart_trend_labels[] = date('M d', strtotime($row['test_date']));
        $chart_trend_scores[] = round($row['daily_avg'], 1);
    }

    // 6. Recent Activity Feed
    $activity_stmt = $pdo->query("SELECT student_id, subject, percentage, completed_at, status FROM results ORDER BY completed_at DESC LIMIT 5");
    $recent_activity = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Top Performers (Leaderboard) with Percentile (Approximation via Rank)
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
        <div class="card" style="margin-top: 0; background: linear-gradient(135deg, #db2777 0%, #be185d 100%); color: white; border: none; margin-bottom: 2rem; position: relative; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; position: relative; z-index: 2;">
                <div>
                    <h2 style="color: white; margin-bottom: 0.5rem; font-size: 1.75rem;"><i class="fas fa-chart-line"></i> Analytics Dashboard</h2>
                    <p style="color: rgba(255,255,255,0.9); margin-bottom: 0;">Deep dive into student performance, trends, and actionable insights.</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="window.print()" class="btn" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                    <a href="dashboard.php" class="btn" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <!-- Decorative circle -->
            <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
        </div>

        <!-- KPI Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card" style="text-align: center; padding: 2rem; border-top: 4px solid var(--primary);">
                <div style="font-size: 3rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;"><?php echo $avg_score; ?>%</div>
                <div style="color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; font-weight: 700;">Average Score</div>
            </div>
            <div class="card" style="text-align: center; padding: 2rem; border-top: 4px solid var(--secondary);">
                <div style="font-size: 3rem; font-weight: 800; color: var(--secondary); margin-bottom: 0.5rem;"><?php echo $pass_rate; ?>%</div>
                <div style="color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; font-weight: 700;">Pass Rate</div>
            </div>
            <div class="card" style="text-align: center; padding: 2rem; border-top: 4px solid var(--dark);">
                <div style="font-size: 3rem; font-weight: 800; color: var(--dark); margin-bottom: 0.5rem;"><?php echo number_format($total_tests); ?></div>
                <div style="color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; font-weight: 700;">Total Assessments</div>
            </div>
            <div class="card" style="text-align: center; padding: 2rem; border-top: 4px solid #f59e0b;">
                <div style="font-size: 3rem; font-weight: 800; color: #f59e0b; margin-bottom: 0.5rem;"><?php echo number_format($kpi['max_score'] ?: 0); ?>%</div>
                <div style="color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; font-weight: 700;">Highest Score</div>
            </div>
        </div>

        <!-- NEW: Distribution & Trend Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
             <!-- Score Distribution -->
             <div class="card" style="height: 100%;">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fas fa-chart-bar text-primary"></i> Score Distribution</span>
                    <span class="badge" style="font-size: 0.7rem; background: var(--primary-light); color: var(--primary); padding: 0.2rem 0.6rem; border-radius: 1rem;">Bell Curve</span>
                </h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>

            <!-- Trend Analysis -->
            <div class="card" style="height: 100%;">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                    <i class="fas fa-chart-area text-secondary"></i> Performance Trend (7 Days)
                </h3>
                <div style="position: relative; height: 300px;">
                    <?php if(empty($chart_trend_labels)): ?>
                        <div style="display: flex; height: 100%; align-items: center; justify-content: center; color: var(--text-light);">
                            No trend data available yet.
                        </div>
                    <?php else: ?>
                        <canvas id="trendChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Existing Charts Row (Subject & Pass/Fail) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <!-- Subject Performance -->
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

        <!-- Detailed Tables & Recent Activity -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem;">
            
            <!-- Recent Activity Feed -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                    <i class="fas fa-history"></i> Recent Activity
                </h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php if(empty($recent_activity)): ?>
                        <div style="text-align: center; color: var(--text-light); padding: 1rem;">No recent activity.</div>
                    <?php else: ?>
                        <?php foreach($recent_activity as $act): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--glass-border);">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; background: var(--bg-body); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary);">
                                        <?php echo strtoupper(substr($act['student_id'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($act['student_id']); ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-light);"><?php echo htmlspecialchars($act['subject']); ?> &bull; <?php echo date('M d, H:i', strtotime($act['completed_at'])); ?></div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="display: block; font-weight: 800; color: <?php echo $act['percentage'] >= 50 ? 'var(--secondary)' : 'var(--danger)'; ?>;">
                                        <?php echo $act['percentage']; ?>%
                                    </span>
                                    <span style="font-size: 0.75rem; text-transform: uppercase;">
                                        <?php echo $act['status'] === 'pending_grading' ? 'Pending' : ($act['percentage'] >= 50 ? 'Pass' : 'Fail'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
    // Common Chart Options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true },
            x: { grid: { display: false } }
        }
    };

    // 1. Score Distribution (Histogram)
    new Chart(document.getElementById('distributionChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($ranges); ?>,
            datasets: [{
                label: 'Students',
                data: <?php echo json_encode($chart_dist_counts); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.5)',
                borderColor: '#4f46e5',
                borderWidth: 1,
                borderRadius: 6,
                hoverBackgroundColor: '#4f46e5'
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    // 2. Trend Analysis (Line Chart)
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_trend_labels); ?>,
                datasets: [{
                    label: 'Daily Average',
                    data: <?php echo json_encode($chart_trend_scores); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#10b981',
                    pointRadius: 5,
                    fill: true,
                    tension: 0.4 // Smooth curves
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: { max: 100, beginAtZero: true }
                }
            }
        });
    }

    // 3. Subject Performance
    new Chart(document.getElementById('subjectChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Average Score (%)',
                data: <?php echo json_encode($chart_scores); ?>,
                backgroundColor: 'rgba(219, 39, 119, 0.6)',
                borderColor: '#db2777',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: { max: 100, beginAtZero: true }
            }
        }
    });

    // 4. Pass/Fail Doughnut
    new Chart(document.getElementById('passFailChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Passed', 'Failed'],
            datasets: [{
                data: [<?php echo $pass_count; ?>, <?php echo $fail_count; ?>],
                backgroundColor: ['#10b981', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 20, usePointStyle: true, boxWidth: 8 }
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>
