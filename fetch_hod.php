<?php
session_start();
require 'db.php'; // Critical database dependency

header('Content-Type: application/json');

// Validate authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Validate role
if ($_SESSION['role'] !== 'HOD') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    // Secure database query
    $stmt = $conn->prepare("
        SELECT username, branch 
        FROM users 
        WHERE id = ?
    "); // Removed redundant role check (already validated in session)
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'username' => $row['username'],
            'branch' => $row['branch']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'HOD profile not found']);
    }

    $stmt->close(); // Proper resource cleanup
} catch (Exception $e) {
    http_response_code(500);
    error_log("Database error in fetch_hod.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database operation failed']);
} finally {
    $conn->close(); // Always close connection
}
?>