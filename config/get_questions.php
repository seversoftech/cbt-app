<?php
header('Content-Type: application/json');
require 'db.php';
session_start();

// FIX: Force clear on restart param
if (isset($_GET['restart'])) {
    unset($_SESSION['test_start_time'], $_SESSION['test_questions'], $_SESSION['test_answers'], $_SESSION['current_index']);
    $_SESSION = array_diff_key($_SESSION, array_flip(['test_start_time', 'test_questions', 'test_answers', 'current_index'])); // Extra clean
}

// Only init if no active test
if (!isset($_SESSION['test_start_time'])) {
    $stmt = $pdo->query("SELECT * FROM questions ORDER BY RAND()");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $_SESSION['test_questions'] = $questions;
    $_SESSION['test_start_time'] = time();
    $_SESSION['test_answers'] = [];
    $_SESSION['current_index'] = 0;
    echo json_encode($questions);
} else {
    // Redirect to state if active (but since JS checks state first, this is fallback)
    header('Location: get_test_state.php');
    exit;
}
?>