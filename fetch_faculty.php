<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Validate session and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HOD') {
    http_response_code(403);
    exit(json_encode(["error" => "Access denied"]));
}

// Ensure the branch is set in the session
if (!isset($_SESSION['branch'])) {
    http_response_code(400);
    exit(json_encode(["error" => "Branch information missing"]));
}

try {
    // Fetch faculty members belonging to the same branch
    $stmt = $conn->prepare("
        SELECT id, username AS name 
        FROM users 
        WHERE branch = ? AND role = 'Faculty'
    ");
    $stmt->bind_param("s", $_SESSION['branch']);
    $stmt->execute();
    $result = $stmt->get_result();

    $faculty = [];
    while ($row = $result->fetch_assoc()) {
        $faculty[] = $row;
    }

    // Return JSON response
    echo json_encode($faculty ?: []); // Ensures an empty array instead of null if no results found

    $stmt->close();
} catch (Exception $e) {
    error_log("Database error in fetch_faculty.php: " . $e->getMessage());
    http_response_code(500);
    exit(json_encode(["error" => "Database operation failed"]));
} finally {
    $conn->close();
}
