<?php
header('Content-Type: application/json');
require 'db.php';
session_start();

// Force clear on restart param
if (isset($_GET['restart'])) {
    unset($_SESSION['test_start_time'], $_SESSION['test_questions'], $_SESSION['test_answers'], $_SESSION['current_index'], $_SESSION['test_category']);
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
    $db_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($db_questions)) {
        echo json_encode(['error' => 'No questions found for category: ' . $category]);
        exit;
    }

    // Shuffle options for each question (per session)
    $shuffled_questions = [];
    foreach ($db_questions as $q) {
        // Original options array: [A, B, C, D]
        $options = [
            'A' => $q['option_a'],
            'B' => $q['option_b'],
            'C' => $q['option_c'],
            'D' => $q['option_d']
        ];
        $keys = array_keys($options); // ['A', 'B', 'C', 'D']
        shuffle($keys); // Random order, e.g., ['C', 'A', 'D', 'B']

        // Remap shuffled options
        $shuffled_q = [
            'id' => $q['id'],
            'question' => $q['question'],
            'category' => $q['category'],
            'original_correct' => $q['correct_answer'], // For internal scoring (keep secret)
        ];
        foreach ($keys as $new_pos => $old_key) {
            $shuffled_q['option_' . strtolower($keys[$new_pos])] = $options[$old_key];
        }

        // Remap correct answer to new position
        $old_correct_pos = array_search($q['correct_answer'], $keys);
        $shuffled_q['correct_answer'] = $keys[$old_correct_pos]; // e.g., 'A' -> 'C'

        $shuffled_questions[] = $shuffled_q;
    }

    $_SESSION['test_questions'] = $shuffled_questions; // Store shuffled version
    $_SESSION['test_start_time'] = time();
    $_SESSION['test_answers'] = [];
    $_SESSION['current_index'] = 0;
    $_SESSION['test_category'] = $category;
    echo json_encode($shuffled_questions);
} else {
    echo json_encode($_SESSION['test_questions']); // Return shuffled (consistent per session)
}
?>