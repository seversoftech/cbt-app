<?php
require '../config/db.php';
session_start(); // Access test questions

// Handle POST (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if test session exists
    if (!isset($_SESSION['test_questions']) || empty($_SESSION['test_questions'])) {
        echo json_encode(['error' => 'No active test session found']);
        exit;
    }

    $questions = $_SESSION['test_questions'];
    $score = 0;
    $total = count($questions);

    // Loop through questions and compare answers
    foreach ($questions as $index => $q) {
        $ans = $_POST["q{$index}"] ?? '';
        $correct = $q['correct_answer'] ?? '';
        // Case-insensitive, trimmed comparison
        if (strcasecmp(trim($ans), trim($correct)) === 0) {
            $score++;
        }
    }

    $percentage = ($total > 0) ? ($score / $total) * 100 : 0;

    // Save result in database
    $stmt = $pdo->prepare("INSERT INTO results (score, total_questions, percentage) VALUES (?, ?, ?)");
    $stmt->execute([$score, $total, $percentage]);

    // Clear session after scoring
    unset($_SESSION['test_questions']);

    // Return JSON response for AJAX (optional)
    echo json_encode(['score' => $score, 'total' => $total, 'percentage' => $percentage]);
    exit;
}

// Handle GET (display result page)
$score = (int)($_GET['score'] ?? 0);
$total = (int)($_GET['total'] ?? 0);
$percentage = ($total > 0) ? ($score / $total) * 100 : 0;
$status = $percentage >= 50 ? 'Pass' : 'Fail';
?>
<?php include '../includes/header.php'; ?>

<!-- Bootstrap 5 + FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<style>
body { background: #f3f4f6; }
.card { max-width: 600px; margin: 3rem auto; border-radius: 12px; padding: 2rem; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.btn-modern { border-radius: 8px; padding: 0.5rem 1rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.4rem; transition: 0.2s; }
.btn-modern-primary { background: linear-gradient(90deg,#2563eb,#3b82f6); color: #fff; border: none; }
.btn-modern-primary:hover { background: linear-gradient(90deg,#1d4ed8,#2563eb); box-shadow: 0 4px 10px rgba(59,130,246,0.4); }
.success { color: #10b981; font-weight: 600; font-size: 1.2rem; }
.error { color: #ef4444; font-weight: 600; font-size: 1.2rem; }
</style>

<div class="card text-center">
    <h1 class="mb-3"><i class="fa-solid fa-chart-simple me-2"></i>Test Results</h1>
    <p>Score: <strong><?php echo $score; ?> / <?php echo $total; ?></strong> (<?php echo number_format($percentage, 2); ?>%)</p>
    <p>Status: <strong style="color: <?php echo $status === 'Pass' ? '#10b981' : '#ef4444'; ?>; font-size: 1.3rem;"><?php echo $status; ?></strong></p>
    <?php if ($status === 'Pass'): ?>
        <p class="success">🎉 Congratulations! You've passed the test.</p>
    <?php else: ?>
        <p class="error">⚠ Keep practicing to improve your score.</p>
    <?php endif; ?>
    <div class="mt-4">
        <a href="index.php" class="btn-modern btn-modern-primary"><i class="fa-solid fa-rotate-left"></i> Take Test Again</a>
    </div>
</div>

<script src="../assets/js/script.js"></script>
</body>
</html>
