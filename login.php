<?php
header('Content-Type: application/json');
require 'db.php'; // Use the existing database connection file

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username']);
$password = trim($input['password']);

// Fetch user from database
$sql = "SELECT id, username, password, role, branch FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Verify hashed password
    if (password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => true,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'branch' => $user['branch']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid username']);
}

$stmt->close();
$conn->close();
?>
