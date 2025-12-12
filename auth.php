<?php
// auth.php
require 'db.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$userKey = $input['userKey'] ?? null;
$username = $input['username'] ?? null;
$role = $input['role'] ?? null;

if (!$userKey || !$username || !$role) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields.']);
    exit();
}

// 1. Check if user exists
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
$stmt->execute([$userKey]);
$user = $stmt->fetch();

if ($user) {
    // User exists, return profile
    echo json_encode(['exists' => true, 'data' => $user]);
} else {
    // User does not exist, create new user
    $registeredAt = round(microtime(true) * 1000); // Current time in milliseconds
    $stmt = $pdo->prepare("INSERT INTO users (id, username, role, registered_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userKey, $username, $role, $registeredAt]);
    
    $newUser = [
        'id' => $userKey, 
        'username' => $username, 
        'role' => $role
    ];
    
    echo json_encode(['exists' => false, 'data' => $newUser]);
}
?>