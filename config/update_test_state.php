<?php
session_start();
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

echo json_encode(['success' => true]);
?>