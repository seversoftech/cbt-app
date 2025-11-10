

<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require '../config/db.php';
session_start(); 

// Handle POST (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if test session exists
    if (!isset($_SESSION['test_questions']) || empty($_SESSION['test_questions'])) {
        echo json_encode(['error' => 'No active test session found']);
        exit;
    }

   // Retrieve subject from POST (sent from JS)
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

    $stmt = $pdo->prepare("INSERT INTO results (subject, score, total_questions, percentage) VALUES (?, ?, ?, ?)");
    $stmt->execute([$subject, $score, $total, $percentage]);

    
    $_SESSION['failed_questions'] = $failed_questions;

   
    unset($_SESSION['test_questions']);
    unset($_SESSION['test_subject']); // Clear after use (if existed)

    echo json_encode(['score' => $score, 'total' => $total, 'percentage' => $percentage]);
    exit;
}

$score = (int)($_GET['score'] ?? 0);
$total = (int)($_GET['total'] ?? 0);
$percentage = ($total > 0) ? ($score / $total) * 100 : 0;
$status = $percentage >= 50 ? 'Pass' : 'Fail';

$failed_questions = $_SESSION['failed_questions'] ?? [];
if (!empty($failed_questions)) {
    unset($_SESSION['failed_questions']); 
}
?>
<?php include '../includes/header.php'; ?>

<!-- Bootstrap 5 + FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<style>
/* body { 
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); 
    min-height: 100vh; 
    margin: 0;
    padding: 0;
} */

</style>

<main>
<div class="card text-center">
    <div class="card-header">
        <i class="fa-solid fa-chart-line fa-2x text-primary mb-3"></i>
        <h1>Test Results</h1>
    </div>
    <div class="card-body">
        <div class="score-display">
            <p class="mb-2">Score: <strong><?php echo $score; ?> / <?php echo $total; ?></strong></p>
            <p class="mb-0 text-muted"> (<?php echo number_format($percentage, 2); ?>%)</p>
        </div>
        <div class="status-badge <?php echo $status === 'Pass' ? 'status-pass' : 'status-fail'; ?>">
            <i class="fa-solid <?php echo $status === 'Pass' ? 'fa-check-circle me-2' : 'fa-exclamation-triangle me-2'; ?>"></i>
            <?php echo $status; ?>
        </div>
        <?php if ($status === 'Pass'): ?>
            <div class="message success">
                <i class="fa-solid fa-trophy me-2"></i>
                Congratulations! You passed.
            </div>
            <i class="fa-solid fa-star icon-large text-success"></i>
        <?php else: ?>
            <div class="message error">
                <i class="fa-solid fa-lightbulb me-2"></i>
                ⚠ Keep practicing to improve your score.
            </div>
            <i class="fa-solid fa-chart-line icon-large text-warning"></i>
        <?php endif; ?>

        <!-- NEW: Failed Questions Review Section -->
        <?php if (!empty($failed_questions)): ?>
            <div class="review-section">
                <div class="review-header">
                    <i class="fa-solid fa-exclamation-circle text-danger"></i>
                    <span>Review Failed Questions (<?php echo count($failed_questions); ?>)</span>
                </div>
                <div class="accordion" id="failedAccordion">
                    <?php foreach ($failed_questions as $index => $fq): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false" aria-controls="collapse<?php echo $index; ?>">
                                    Question <?php echo $index + 1; ?>: <?php echo htmlspecialchars(substr($fq['question'], 0, 50)); ?>...
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#failedAccordion">
                                <div class="accordion-body">
                                    <div class="failed-q"><?php echo htmlspecialchars($fq['question']); ?></div>
                                    <div class="user-ans"><i class="fa-solid fa-user me-1"></i> Your Answer: <?php echo htmlspecialchars($fq['user_answer']); ?></div>
                                    <div class="correct-ans"><i class="fa-solid fa-check-circle me-1"></i> Correct Answer: <?php echo htmlspecialchars($fq['correct_answer']); ?></div>
                                    <?php if (!empty($fq['explanation'])): ?>
                                        <div class="explanation"><i class="fa-solid fa-info-circle me-1"></i> Note: <?php echo htmlspecialchars($fq['explanation']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <button onclick="window.location.href='index.php'" class="btn-modern btn-modern-primary">
                <i class="fa-solid fa-rotate-left"></i> Take Test Again
            </button>
        </div>
    </div>
</div>
</main>

<?php
include '../includes/footer.php';
?>

<script src="../assets/js/script.js"></script>
</body>
</html>