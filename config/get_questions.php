<?php
header('Content-Type: application/json');
require 'db.php';
session_start();

// FIX: Force clear on restart param
if (isset($_GET['restart'])) {
    unset($_SESSION['test_start_time'], $_SESSION['test_questions'], $_SESSION['test_answers'], $_SESSION['current_index'], $_SESSION['test_category']);
    // Extra clean
    $_SESSION = array_diff_key($_SESSION, array_flip(['test_start_time', 'test_questions', 'test_answers', 'current_index', 'test_category']));
}

// Get category from query
$category = $_GET['category'] ?? '';

// Only init if no active test
if (!isset($_SESSION['test_start_time'])) {
    if (empty($category)) {
        echo json_encode(['error' => 'Category required for new test']);
        exit;
    }
    // Load questions for category
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE category = ? ORDER BY RAND()");
    $stmt->execute([$category]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($questions)) {
        echo json_encode(['error' => 'No questions found for category: ' . $category]);
        exit;
    }
    $_SESSION['test_questions'] = $questions;
    $_SESSION['test_start_time'] = time();
    $_SESSION['test_answers'] = [];
    $_SESSION['current_index'] = 0;
    $_SESSION['test_category'] = $category;
    echo json_encode($questions);
} else {
    // If active test but category mismatch, error or clear? For now, return active questions (assume same category)
    // In JS, category is set before fetch, so should match
    echo json_encode($_SESSION['test_questions']);
}
?>