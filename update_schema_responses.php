<?php
require 'config/db.php';

try {
    // Create student_responses table
    $sql = "CREATE TABLE IF NOT EXISTS student_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        result_id INT NOT NULL,
        student_id VARCHAR(255) NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT,
        score_awarded DECIMAL(5,2) DEFAULT NULL,
        grader_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (result_id) REFERENCES results(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Table 'student_responses' created successfully.<br>";
    
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
