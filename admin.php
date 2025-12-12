<?php
// admin.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // --- DELETE ALL DATA ---
    try {
        // Drop and recreate tables to ensure integrity and reset auto-increment
        $pdo->exec("DROP TABLE IF EXISTS reports;");
        $pdo->exec("DROP TABLE IF EXISTS users;");
        
        $pdo->exec("
            CREATE TABLE users (
                id VARCHAR(50) PRIMARY KEY,
                username VARCHAR(30) NOT NULL,
                role ENUM('Teacher', 'Student', 'IT Staff') NOT NULL,
                registered_at BIGINT NOT NULL
            );
        ");

        $pdo->exec("
            CREATE TABLE reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                display_id VARCHAR(10) NOT NULL UNIQUE,
                content TEXT NOT NULL,
                category VARCHAR(100) NOT NULL,
                location VARCHAR(100) NOT NULL,
                submitted_by VARCHAR(50) NOT NULL,
                submitted_by_role VARCHAR(20) NOT NULL,
                submitted_by_id VARCHAR(50) NOT NULL,
                status ENUM('New', 'Pending', 'Resolved') DEFAULT 'New',
                photo_base64 MEDIUMTEXT NULL,
                timestamp BIGINT NOT NULL,
                resolved_at BIGINT NULL
            );
        ");

        echo json_encode(['message' => 'All data has been reset and tables recreated.']);
        
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error during reset.', 'details' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
?>