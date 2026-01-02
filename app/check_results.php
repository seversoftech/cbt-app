<?php
require '../config/db.php';

$search_results = [];
$search_id = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['student_id'])) {
    $search_id = trim($_POST['student_id'] ?? $_GET['student_id']);
    
    if (!empty($search_id)) {
        // Fetch results for this student ID
        $stmt = $pdo->prepare("SELECT * FROM results WHERE student_id = ? ORDER BY completed_at DESC");
        $stmt->execute([$search_id]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($search_results)) {
            $error = "No records found for Student ID: " . htmlspecialchars($search_id);
        }
    } else {
        $error = "Please enter a valid Student ID.";
    }
}
?>
<?php include '../includes/header.php'; ?>

<main style="min-height: 80vh; padding: 4rem 0;">
    <div class="container">
        
        <div style="max-width: 600px; margin: 0 auto; text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary);">Check Results</h1>
            <p style="color: var(--text-light); font-size: 1.1rem;">Enter your Student ID to view your exam history and performance.</p>
        </div>

        <!-- Search Card -->
        <div class="card" style="max-width: 500px; margin: 0 auto 3rem; padding: 2rem; border: 1px solid var(--glass-border); box-shadow: var(--shadow-lg);">
            <form method="POST" action="check_results.php">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 700;">Student ID</label>
                    <div style="position: relative;">
                        <i class="fas fa-id-card" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <input type="text" name="student_id" value="<?php echo htmlspecialchars($search_id); ?>" placeholder="Enter ID used during exam..." required 
                               style="width: 100%; padding: 0.8rem 1rem 0.8rem 3rem; border: 2px solid var(--glass-border); border-radius: 0.5rem; font-size: 1.1rem; transition: border-color 0.3s;">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; justify-content: center;">
                    <i class="fas fa-search"></i> View History
                </button>
            </form>
        </div>

        <?php if ($error): ?>
            <div style="max-width: 500px; margin: 0 auto; background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; text-align: center; border: 1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($search_results)): ?>
            <div class="card" style="max-width: 900px; margin: 0 auto;">
                <h3 style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--glass-border);">
                    Exam History: <span style="color: var(--primary);"><?php echo htmlspecialchars($search_id); ?></span>
                </h3>
                
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9fafb;">
                                <th style="text-align: left; padding: 1rem; border-bottom: 2px solid #e5e7eb;">Subject</th>
                                <th style="text-align: center; padding: 1rem; border-bottom: 2px solid #e5e7eb;">Date</th>
                                <th style="text-align: center; padding: 1rem; border-bottom: 2px solid #e5e7eb;">Status</th>
                                <th style="text-align: center; padding: 1rem; border-bottom: 2px solid #e5e7eb;">Score</th>
                                <th style="text-align: center; padding: 1rem; border-bottom: 2px solid #e5e7eb;">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $r): 
                                $status = $r['status'] ?? 'completed';
                                $isPending = $status === 'pending_grading';
                            ?>
                                <tr>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 600;">
                                        <?php echo htmlspecialchars($r['subject']); ?>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; text-align: center; color: var(--text-light); font-size: 0.9rem;">
                                        <?php echo date('M j, Y h:i A', strtotime($r['completed_at'])); ?>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                        <?php if ($isPending): ?>
                                            <span class="badge" style="background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 0.3rem 0.8rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 700;">
                                                <i class="fas fa-clock"></i> Pending Grading
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #ecfdf5; color: #10b981; padding: 0.3rem 0.8rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 700;">
                                                <i class="fas fa-check-circle"></i> Graded
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid #e5e7eb; text-align: center; font-weight: 700;">
                                        <?php if ($isPending): ?>
                                            <span style="color: var(--text-light);">-</span>
                                        <?php else: ?>
                                            <?php echo $r['score']; ?> / <?php echo $r['total_questions']; ?>
                                        <?php endif; ?>
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
