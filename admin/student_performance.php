<?php
require '../config/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$student_id = $_GET['student_id'] ?? '';
$results = [];
$error = '';

if ($student_id) {
    $stmt = $pdo->prepare("SELECT * FROM results WHERE student_id = ? ORDER BY completed_at DESC");
    $stmt->execute([$student_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        $error = "No results found for student: " . htmlspecialchars($student_id);
    }
} else {
    header('Location: view_results.php');
    exit;
}
?>

<?php include '../includes/header.php'; 
include '../includes/admin_nav.php'; 
?>

<main style="padding-bottom: 4rem;">
    <div class="container" style="padding-top: 2rem;">
        
        <div style="margin-bottom: 2rem;">
            <a href="view_results.php" class="btn" style="background: transparent; color: var(--text-light); border: 1px solid var(--glass-border); display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.9rem;">
                <i class="fas fa-arrow-left"></i> Back to All Results
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <div style="width: 60px; height: 60px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.75rem;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 2rem;">Performance Report</h1>
                    <p style="margin: 0; color: var(--text-light); font-size: 1.1rem;">Student: <strong><?php echo htmlspecialchars($student_id); ?></strong></p>
                </div>
            </div>

            <?php
            // Calculate Stats (Reusing logic)
            $total_tests = count($results);
            $total_score_pct = 0;
            $graded_count = 0;
            $best_score = 0;
            $best_subject = '-';
            
            foreach ($results as $r) {
                if (($r['status'] ?? 'completed') !== 'pending_grading') {
                    $total_score_pct += $r['percentage'];
                    $graded_count++;
                    if ($r['percentage'] > $best_score) {
                        $best_score = $r['percentage'];
                        $best_subject = $r['subject'];
                    }
                }
            }
            $avg_score = $graded_count > 0 ? ($total_score_pct / $graded_count) : 0;
            ?>

            <!-- Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--glass-border); box-shadow: var(--shadow-sm); text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 0.5rem;">Tests Taken</div>
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary);"><?php echo $total_tests; ?></div>
                </div>
                 <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--glass-border); box-shadow: var(--shadow-sm); text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 0.5rem;">Average Performance</div>
                    <div style="font-size: 2.5rem; font-weight: 800; color: <?php echo $avg_score >= 50 ? 'var(--secondary)' : 'var(--danger)'; ?>;">
                        <?php echo number_format($avg_score, 1); ?><span style="font-size: 1.25rem;">%</span>
                    </div>
                </div>
                 <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--glass-border); box-shadow: var(--shadow-sm); text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 0.5rem;">Strongest Subject</div>
                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--text-header); padding-top: 0.5rem;"><?php echo htmlspecialchars($best_subject); ?></div>
                    <div style="font-size: 0.8rem; color: var(--secondary); font-weight: 600;"><?php echo number_format($best_score, 0); ?>%</div>
                </div>
            </div>

            <div class="card" style="border: 1px solid var(--glass-border);">
                <h3 style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--glass-border);">Detailed History</h3>
                <div class="table-responsive">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9fafb;">
                                <th style="padding: 1rem; text-align: left;">Subject</th>
                                <th style="padding: 1rem; text-align: center;">Date</th>
                                <th style="padding: 1rem; text-align: center;">Score</th>
                                <th style="padding: 1rem; text-align: center;">Percentage</th>
                                <th style="padding: 1rem; text-align: center;">Status</th>
                                <th style="padding: 1rem; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $r): 
                                $status = $r['status'] ?? 'completed';
                                $isPending = $status === 'pending_grading';
                            ?>
                                <tr>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 600;"><?php echo htmlspecialchars($r['subject']); ?></td>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; text-align: center; color: var(--text-light);"><?php echo date('M j, Y H:i', strtotime($r['completed_at'])); ?></td>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; text-align: center; font-weight: 700;">
                                        <?php if ($isPending): ?>-<?php else: ?><?php echo $r['score'] . '/' . $r['total_questions']; ?><?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                        <?php if ($isPending): ?>
                                            <span style="color: var(--text-light);">-</span>
                                        <?php else: ?>
                                            <span style="font-weight: 800; color: <?php echo $r['percentage'] >= 50 ? 'var(--secondary)' : 'var(--danger)'; ?>;">
                                                <?php echo number_format($r['percentage'], 1); ?>%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                         <?php if ($isPending): ?>
                                            <span class="badge" style="background: #fff7ed; color: #ea580c; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.75rem;">Pending</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #ecfdf5; color: #10b981; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.75rem;">Graded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                         <?php if ($isPending): ?>
                                            <a href="grade_result.php?id=<?php echo $r['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">Grade</a>
                                        <?php else: ?>
                                            <a href="grade_result.php?id=<?php echo $r['id']; ?>" class="btn btn-outline-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem; border: 1px solid var(--glass-border); color: var(--text-light);">View/Edit</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>
