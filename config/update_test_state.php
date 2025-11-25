<?php

header('Content-Type: application/json');
require 'db.php';

if (!isset($_SESSION['test_start_time']) || !isset($_SESSION['test_questions'])) {
    echo json_encode(['error' => 'No active test']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['answers'])) {
    $_SESSION['test_answers'] = array_merge($_SESSION['test_answers'] ?? [], $input['answers']);
}
if (isset($input['current_index'])) {
    $_SESSION['current_index'] = (int)$input['current_index'];
}
if (isset($input['category'])) {
    $_SESSION['test_category'] = $input['category'];
}
if (isset($input['clear']) && $input['clear']) {
    unset($_SESSION['test_start_time'], $_SESSION['test_questions'], $_SESSION['test_answers'], $_SESSION['current_index'], $_SESSION['test_category']);
    echo json_encode(['success' => true, 'cleared' => true]);
    exit;
}

echo json_encode(['success' => true]);
?>