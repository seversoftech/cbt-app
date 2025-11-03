<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (isset($_SESSION['test_start_time']) && isset($_SESSION['test_questions']) && !empty($_SESSION['test_questions'])) {
    // Check if expired (30min total)
    $elapsed = time() - $_SESSION['test_start_time'];
    if ($elapsed > 1800) { // 30min in seconds
        unset($_SESSION['test_start_time'], $_SESSION['test_questions'], $_SESSION['test_answers'], $_SESSION['current_index'], $_SESSION['test_category']);
        echo json_encode(['expired' => true]);
        exit;
    }

    // Default/init if not set
    $_SESSION['test_answers'] = $_SESSION['test_answers'] ?? [];
    $_SESSION['current_index'] = $_SESSION['current_index'] ?? 0;
    $_SESSION['test_category'] = $_SESSION['test_category'] ?? 'General';

    echo json_encode([
        'active' => true,
        'questions' => $_SESSION['test_questions'],
        'answers' => $_SESSION['test_answers'], // e.g., {'0': 'b', '1': 'c'}
        'current_index' => $_SESSION['current_index'],
        'start_time' => $_SESSION['test_start_time'],
        'category' => $_SESSION['test_category'],
        'total_time' => 1800 // 30min in seconds
    ]);
} else {
    echo json_encode(['active' => false]);
}
?>