<?php
header('Content-Type: application/json');
require 'db.php';

$withCounts = isset($_GET['with_counts']) && $_GET['with_counts'] == 1;

try {
    if ($withCounts) {
        $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM questions WHERE category != '' GROUP BY category ORDER BY category");
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("SELECT DISTINCT category FROM questions WHERE category != '' ORDER BY category");
        $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    echo json_encode($subjects);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Query Failed: ' . $e->getMessage()]);
}
?>