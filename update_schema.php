<?php
require 'config/db.php';

try {
    echo "Updating schema...\n";

    // 1. Add `type` column to `questions` table
    // Check if column exists first to avoid error
    $col_stmt = $pdo->query("SHOW COLUMNS FROM questions LIKE 'type'");
    if ($col_stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN type ENUM('objective', 'theory') DEFAULT 'objective' AFTER category");
        echo "Added 'type' column to questions table.\n";
    } else {
        echo "'type' column already exists in questions table.\n";
    }

    // 2. Create `student_responses` table
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        result_id INT NULL, -- Can be null initially if responses are saved before result created, but usually linked
        student_id VARCHAR(50),
        question_id INT,
        answer_text TEXT,
        score_awarded INT DEFAULT 0,
        graded_by VARCHAR(50) NULL,
        graded_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (result_id),
        INDEX (student_id),
        INDEX (question_id)
    )");
    echo "Created/Verified 'student_responses' table.\n";

    // 3. Update `results` table for grading status
    $res_col_stmt = $pdo->query("SHOW COLUMNS FROM results LIKE 'status'");
    if ($res_col_stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE results ADD COLUMN status ENUM('completed', 'pending_grading') DEFAULT 'completed' AFTER percentage");
        echo "Added 'status' column to results table.\n";
    } else {
        echo "'status' column already exists in results table.\n";
    }

    echo "Schema update completed successfully.\n";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
    exit(1);
}
