<?php
require 'config/db.php';

try {
    $stmt = $pdo->query("DESCRIBE student_responses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
