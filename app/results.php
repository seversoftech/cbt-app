<?php
require '../config/db.php';

// Handle POST (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug log
    file_put_contents('debug_post.txt', "POST: " . print_r($_POST, true) . "\nSESSION: " . print_r($_SESSION, true), FILE_APPEND);


    // Check if test session exists
    if (!isset($_SESSION['test_questions']) || empty($_SESSION['test_questions'])) {
        echo json_encode(['error' => 'No active test session found']);
        exit;
    }

    // Retrieve subject
    $subject = $_POST['subject'] ?? ($_SESSION['test_category'] ?? 'Unknown');

    $questions = $_SESSION['test_questions'];
    $score = 0;
    $total = count($questions);
    $failed_questions = [];

    // Loop through questions and compare answers
    foreach ($questions as $index => $q) {
        $ans = $_POST["q{$index}"] ?? '';
        $correct = $q['correct_answer'] ?? '';
        
        if (strcasecmp(trim($ans), trim($correct)) === 0) {
            $score++;
        } else {
            $failed_questions[] = [
                'question' => $q['question'],
                'user_answer' => $ans ? (strtoupper($ans) . '. ' . ($q['option_' . strtolower($ans)] ?? 'No option selected')) : 'No answer selected',
                'correct_answer' => $correct ? (strtoupper($correct) . '. ' . ($q['option_' . strtolower($correct)] ?? $correct)) : 'No answer provided',
                'explanation' => $q['explanation'] ?? 'Review the options carefully.' 
            ];
        }
    }

    $percentage = ($total > 0) ? ($score / $total) * 100 : 0;
    
    // Prioritize POST, then Session, then Anonymous
    $student_id = 'Anonymous';
    if (!empty($_POST['student_id'])) {
        $student_id = trim($_POST['student_id']);
    } elseif (!empty($_SESSION['student_id'])) {
        $student_id = trim($_SESSION['student_id']);
    }

    $stmt = $pdo->prepare("INSERT INTO results (student_id, subject, score, total_questions, percentage) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$student_id, $subject, $score, $total, $percentage]);

    $_SESSION['failed_questions'] = $failed_questions;
    $_SESSION['last_student_name'] = $student_id; 
    $_SESSION['student_id'] = $student_id; // Keep it in session for display persistence

    unset($_SESSION['test_questions']);
    unset($_SESSION['test_subject']); 

    echo json_encode(['score' => $score, 'total' => $total, 'percentage' => $percentage]);
    exit;
}

$score = (int)($_GET['score'] ?? 0);
$total = (int)($_GET['total'] ?? 0);
$percentage = ($total > 0) ? ($score / $total) * 100 : 0;
$status = $percentage >= 50 ? 'Pass' : 'Fail';
$statusClass = $percentage >= 50 ? 'success' : 'danger';
$statusColor = $percentage >= 50 ? 'var(--secondary)' : 'var(--danger)';

$failed_questions = $_SESSION['failed_questions'] ?? [];
// Do NOT unset here, let them refresh if they want
// if (!empty($failed_questions)) { unset($_SESSION['failed_questions']); }
?>
<?php include '../includes/header.php'; ?>

<!-- Custom Styles for Results -->
<style>
    .result-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 140px);
        padding: 2rem 1rem;
    }

    .result-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 2rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        padding: 2.5rem;
        width: 100%;
        max-width: 600px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.6);
        animation: floatIn 0.5s ease-out;
    }

    @keyframes floatIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .score-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: conic-gradient(<?php echo $statusColor; ?> <?php echo $percentage; ?>%, #e5e7eb 0);
        margin: 0 auto 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .score-circle::before {
        content: '';
        position: absolute;
        width: 130px;
        height: 130px;
        background: white;
        border-radius: 50%;
    }

    .score-value {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .score-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--dark);
        line-height: 1;
    }

    .score-total {
        font-size: 1rem;
        color: var(--text-light);
        font-weight: 600;
    }

    .status-badge {
        display: inline-block;
        padding: 0.5rem 1.5rem;
        border-radius: 2rem;
        font-weight: 800;
        font-size: 1.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 2rem;
        background: <?php echo $percentage >= 50 ? '#ecfdf5' : '#fef2f2'; ?>;
        color: <?php echo $statusColor; ?>;
        border: 2px solid <?php echo $percentage >= 50 ? '#d1fae5' : '#fee2e2'; ?>;
    }

    .details-box {
        text-align: left;
        margin-top: 2rem;
        border-top: 2px solid #f3f4f6;
        padding-top: 2rem;
    }

    details {
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        overflow: hidden;
        background: #f9fafb;
    }

    summary {
        padding: 1rem;
        cursor: pointer;
        font-weight: 600;
        color: var(--dark);
        background: white;
        list-style: none; /* Hide default arrow */
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    summary::-webkit-details-marker {
        display: none;
    }

    summary:after {
        content: '\f078'; /* FontAwesome chevron-down */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        transition: transform 0.2s;
        font-size: 0.8rem;
        color: var(--text-light);
    }

    details[open] summary:after {
        transform: rotate(180deg);
    }

    .failed-item-body {
        padding: 1.5rem;
        border-top: 1px solid #e5e7eb;
        font-size: 0.95rem;
    }

    .answer-row {
        margin-bottom: 0.5rem;
        display: flex;
        gap: 0.5rem;
    }
    
    .answer-label {
        font-weight: 700;
        min-width: 120px;
    }
</style>

<div class="result-container">
    <div class="result-card">
        <h1 style="margin-bottom: 0.5rem; font-size: 2rem;">Result Summary</h1>
        <div style="font-weight: 700; color: var(--text-light); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.85rem;">
             Student: <span style="color: var(--text-header);"><?php echo htmlspecialchars($_SESSION['last_student_name'] ?? ($_SESSION['student_id'] ?? 'Guest')); ?></span>
        </div>
        <div style="font-weight: 700; color: var(--primary); margin-bottom: 2rem; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.9rem;">
             Subject: <?php echo htmlspecialchars($_GET['subject'] ?? 'General'); ?>
        </div>
        
        <div class="score-circle">
            <div class="score-value">
                <span class="score-number"><?php echo number_format($percentage, 0); ?>%</span>
                <span class="score-total"><?php echo $score; ?> / <?php echo $total; ?></span>
            </div>
        </div>

        <div class="status-badge">
            <?php echo $status; ?>
        </div>

        <p style="color: var(--text-light); font-size: 1.1rem; margin-bottom: 2.5rem;">
            <?php if ($percentage >= 50): ?>
                <i class="fas fa-check-circle" style="color: var(--secondary);"></i> Great job! You passed the exam.
            <?php else: ?>
                <i class="fas fa-exclamation-circle" style="color: var(--danger);"></i> Don't give up! Keep practicing.
            <?php endif; ?>
        </p>

        <a href="index.php" class="btn" style="width: 100%; padding: 1rem; font-size: 1.1rem; justify-content: center;">
            <i class="fas fa-redo"></i> Take Another Test
        </a>

        <?php if (!empty($failed_questions)): ?>
            <div class="details-box">
                <h3 style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; font-size: 1.25rem;">
                    <i class="fas fa-clipboard-list text-danger" style="color: var(--danger);"></i> 
                    Review Failed Questions
                </h3>
                
                <?php foreach ($failed_questions as $index => $fq): ?>
                    <details>
                        <summary>
                            <span style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="background: var(--danger); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                                    <?php echo $index + 1; ?>
                                </span>
                                <?php echo htmlspecialchars(substr($fq['question'], 0, 40)) . (strlen($fq['question']) > 40 ? '...' : ''); ?>
                            </span>
                        </summary>
                        <div class="failed-item-body">
                            <p style="font-weight: 700; margin-bottom: 1rem;"><?php echo htmlspecialchars($fq['question']); ?></p>
                            
                            <div class="answer-row" style="color: var(--danger);">
                                <span class="answer-label"><i class="fas fa-times-circle"></i> Your Answer:</span>
                                <span><?php echo htmlspecialchars($fq['user_answer']); ?></span>
                            </div>
                            
                            <div class="answer-row" style="color: var(--secondary);">
                                <span class="answer-label"><i class="fas fa-check-circle"></i> Correct:</span>
                                <span><?php echo htmlspecialchars($fq['correct_answer']); ?></span>
                            </div>
                            
                            <?php if (!empty($fq['explanation'])): ?>
                                <div style="margin-top: 1rem; padding: 1rem; background: #f3f4f6; border-radius: 0.5rem; font-size: 0.9rem; color: var(--text-light);">
                                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> <?php echo htmlspecialchars($fq['explanation']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>