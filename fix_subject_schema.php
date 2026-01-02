<?php
require 'config/db.php';

try {
    // OLD: varchar(11)
    // NEW: varchar(100) just to be safe
    $pdo->exec("ALTER TABLE results MODIFY COLUMN subject VARCHAR(100) NOT NULL");
    echo "Successfully updated 'subject' column width to 100 characters.";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>
