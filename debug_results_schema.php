<?php
require 'config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE results");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
