<?php
// db.php
header("Content-Type: application/json; charset=UTF-8");

$host = 'localhost';
$db   = 'techtrack_db'; // Change if you used a different database name
$user = 'root';        // Default XAMPP username
$pass = '';            // Default XAMPP password - change if you set one

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     http_response_code(500);
     echo json_encode(['error' => 'Database connection failed.', 'details' => $e->getMessage()]);
     exit();
}
?>