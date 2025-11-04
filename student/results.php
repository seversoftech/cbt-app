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
body { 
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); 
    min-height: 100vh; 
    margin: 0;
    padding: 0;
}
main {
    min-height: calc(100vh - 200px); /* Adjust based on header/footer heights */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.card { 
    max-width: 500px; 
    width: 100%; 
    margin: 2rem auto; 
    border-radius: 20px; 
    padding: 2.5rem; 
    box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.15);
}
.card-header {
    text-align: center;
    margin-bottom: 2rem;
}
.card-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}
.score-display {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 15px;
    padding: 1.5rem;
    margin: 1rem 0;
    border: 2px solid #e2e8f0;
}
.score-display strong {
    font-size: 2.5rem;
    color: #6366f1;
    font-weight: 800;
}
.status-badge {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-size: 1.2rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 1rem 0;
}
.status-pass {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}
.status-fail {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}
.message {
    font-size: 1.1rem;
    margin: 1.5rem 0;
    padding: 1rem;
    border-radius: 10px;
    border-left: 5px solid;
}
.message.success {
    background: #ecfdf5;
    color: #065f46;
    border-left-color: #10b981;
}
.message.error {
    background: #fef2f2;
    color: #991b1b;
    border-left-color: #ef4444;
}
.btn-modern { 
    border-radius: 12px; 
    padding: 0.75rem 2rem; 
    font-weight: 600; 
    font-size: 1rem;
    display: inline-flex; 
    align-items: center; 
    gap: 0.5rem; 
    transition: all 0.3s ease; 
    border: none;
    width: 100%;
    justify-content: center;
}
.btn-modern-primary { 
    background: linear-gradient(135deg, #2563eb, #3b82f6); 
    color: #fff; 
}
.btn-modern-primary:hover { 
    background: linear-gradient(135deg, #1d4ed8, #2563eb); 
    box-shadow: 0 8px 25px rgba(59,130,246,0.4); 
    transform: translateY(-2px);
    color: #fff;
}
.icon-large {
    font-size: 3rem;
    margin-bottom: 1rem;
}
footer {
    width: 100%;
    margin: 0;
    padding: 1rem 0;
    background: #1f2937;
    color: white;
    text-align: center;
    /* Ensure it spans full width */
    position: relative;
    left: 0;
    right: 0;
}
@media (max-width: 576px) {
    .card {
        margin: 1rem;
        padding: 1.5rem;
    }
    .card-header h1 {
        font-size: 1.5rem;
    }
    .score-display strong {
        font-size: 2rem;
    }
}
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
                Congratulations! You've passed the test.
            </div>
            <i class="fa-solid fa-star icon-large text-success"></i>
        <?php else: ?>
            <div class="message error">
                <i class="fa-solid fa-lightbulb me-2"></i>
                ⚠ Keep practicing to improve your score.
            </div>
            <i class="fa-solid fa-chart-line icon-large text-warning"></i>
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