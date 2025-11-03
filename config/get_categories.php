<?php
// Enable error reporting for debugging (remove in prod)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
require 'db.php';

try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM questions WHERE category != '' ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC); // Use ASSOC for safety
    $catList = array_column($categories, 'category'); // Extract if needed
    echo json_encode($catList ?: []); // Empty array if none
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Query Failed: ' . $e->getMessage()]);
}
?>