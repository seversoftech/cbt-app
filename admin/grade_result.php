<?php
require '../config/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$result_id = $_GET['id'] ?? null;
if (!$result_id) {
    header('Location: view_results.php');
    exit;
}

// Fetch Result Details
$stmt = $pdo->prepare("SELECT * FROM results WHERE id = ?");
$stmt->execute([$result_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) die("Result not found.");

// Fetch Student Responses for this result
// Using LEFT JOIN to get question details even if question was deleted (though FK constraint usually prevents that, depends on setup)
// Actually getting question text from questions table
$sql = "SELECT sr.*, q.question, q.correct_answer as model_answer, q.image 
        FROM student_responses sr 
        JOIN questions q ON sr.question_id = q.id 
        WHERE sr.result_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$result_id]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Grading Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Calculate the CONSTANT Objective Score (Current Total - Previous Theory Sum)
        $prevStmt = $pdo->prepare("SELECT SUM(score_awarded) FROM student_responses WHERE result_id = ?");
        $prevStmt->execute([$result_id]);
        $previous_theory_total = $prevStmt->fetchColumn() ?: 0;
        
        $current_total_score = floatval($result['score']);
        $objective_score = $current_total_score - floatval($previous_theory_total);
        
        // 2. Update Responses with NEW Scores
        $total_theory_score = 0;
        foreach ($responses as $resp) {
            $resp_id = $resp['id'];
            $score = floatval($_POST['score_' . $resp_id] ?? 0);
            $note = $_POST['note_' . $resp_id] ?? '';
            
            $total_theory_score += $score;
            
            // Update individual response
            $updateResp = $pdo->prepare("UPDATE student_responses SET score_awarded = ?, grader_note = ? WHERE id = ?");
            $updateResp->execute([$score, $note, $resp_id]);
        }
        
        // 3. Calculate New Total and Update Result
        $new_total_score = $objective_score + $total_theory_score;
        
        // Protect against division by zero
        $total_qs = intval($result['total_questions']);
        $new_percentage = ($total_qs > 0) ? ($new_total_score / $total_qs) * 100 : 0;
        
        $updateResult = $pdo->prepare("UPDATE results SET score = ?, percentage = ?, status = 'completed' WHERE id = ?");
        $updateResult->execute([$new_total_score, $new_percentage, $result_id]);
        
        header("Location: view_results.php?msg=graded");
        exit;

    } catch (Exception $e) {
        // Log error and show message
        file_put_contents('grading_error_log.txt', $e->getMessage(), FILE_APPEND);
        die("Error processing grades: " . $e->getMessage());
    }
}

?>

<?php include '../includes/header.php'; 
include '../includes/admin_nav.php'; 
?>

<main style="padding-bottom: 4rem;">
    <div class="container" style="padding-top: 2rem;">
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                <div>
                     <h2 style="margin: 0;">Grade Assessment</h2>
                     <p style="color: var(--text-light); margin:0;">Student: <strong><?php echo htmlspecialchars($result['student_id']); ?></strong> | Subject: <?php echo htmlspecialchars($result['subject']); ?></p>
                </div>
                <a href="view_results.php" class="btn" style="background: var(--text-light);">Cancel</a>
            </div>

            <?php if (empty($responses)): ?>
                <div class="alert alert-info">No theory questions found for this result.</div>
            <?php else: ?>
                <form method="POST">
                    <?php foreach ($responses as $index => $r): ?>
                        <div class="question-grade-card" style="background: var(--bg-body); border: 1px solid var(--glass-border); padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem;">
                            
                            <!-- Question -->
                            <div style="margin-bottom: 1rem; font-weight: 600;">
                                <span style="background: #e0e7ff; color: var(--primary); padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.8rem; margin-right: 0.5rem;">Q<?php echo $index + 1; ?></span>
                                <?php echo strip_tags($r['question']); ?>
                            </div>
                            
                            <?php if ($r['image']): ?>
                                <img src="../<?php echo $r['image']; ?>" style="max-height: 150px; margin-bottom: 1rem; border-radius: 0.5rem;">
                            <?php endif; ?>

                            <!-- Student Answer -->
                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; color: var(--text-light); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Student Answer</label>
                                <div style="padding: 1rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-family: monospace; font-size: 1rem; color: var(--text-main); white-space: pre-wrap;">
                                    <?php echo htmlspecialchars($r['answer_text'] ?: 'No answer provided.'); ?>
                                </div>
                            </div>

                            <!-- Model Answer -->
                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; color: #10b981; font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Model Answer / Rubric</label>
                                <div style="padding: 1rem; background: #ecfdf5; border: 1px solid #d1fae5; border-radius: 0.5rem; color: #065f46; font-size: 0.95rem;">
                                    <?php echo htmlspecialchars($r['model_answer'] ?: 'No model answer provided.'); ?>
                                </div>
                            </div>

                            <!-- Grading Inputs -->
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; background: rgba(0,0,0,0.02); padding: 1rem; border-radius: 0.5rem;">
                                <div>
                                    <label>Score (Points)</label>
                                    <input type="number" name="score_<?php echo $r['id']; ?>" value="<?php echo $r['score_awarded'] ?? 0; ?>" min="0" step="0.5" class="modern-select" style="font-weight: bold;">
                                </div>
                                <div>
                                    <label>Grader Note (Optional)</label>
                                    <input type="text" name="note_<?php echo $r['id']; ?>" value="<?php echo htmlspecialchars($r['grader_note'] ?? ''); ?>" placeholder="Feedback..." style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--glass-border);">
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>

                    <div style="text-align: right; margin-top: 2rem;">
                         <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
                            <i class="fas fa-check-circle"></i> Submit Grades
                         </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<!-- Font Awesome already included in footer? No, usually header. Checking... -->
<script src="../assets/js/script.js"></script>
</body>
</html>
