<?php
require 'config/db.php';

// Fix missing columns in student_responses table
try {
    // Attempt to add grader_note
    try {
        $pdo->exec("ALTER TABLE student_responses ADD COLUMN grader_note TEXT DEFAULT NULL");
        echo "Successfully added 'grader_note' column.<br>";
    } catch (PDOException $e) {
        // likely already exists or other error, suppress to continue
        echo "Note: Could not add 'grader_note' (may already exist). Error: " . $e->getMessage() . "<br>";
    }

    // Attempt to add score_awarded
    try {
        $pdo->exec("ALTER TABLE student_responses ADD COLUMN score_awarded DECIMAL(5,2) DEFAULT NULL");
        echo "Successfully added 'score_awarded' column.<br>";
    } catch (PDOException $e) {
        echo "Note: Could not add 'score_awarded' (may already exist). Error: " . $e->getMessage() . "<br>";
    }
    
    // Attempt to add status to results if missing (just in case)
    /* 
    try {
        $pdo->exec("ALTER TABLE results MODIFY COLUMN status VARCHAR(50) DEFAULT 'completed'");
         echo "Verified 'status' column in results.<br>";
    } catch (PDOException $e) {
         echo "Error checking status column: " . $e->getMessage() . "<br>";
    }
    */

} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
?>
