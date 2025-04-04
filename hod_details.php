<?php
session_start();
include __DIR__ . '/db.php'; // Ensure the correct path to db.php

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HOD') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$hod_id = $_SESSION['user_id'];

// Fetch HOD details
$sql = "SELECT username AS name, branch FROM users WHERE id = ? AND role = 'HOD' LIMIT 1";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $hod_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $hod_details = $result->fetch_assoc();
        echo json_encode($hod_details); // Return JSON response
    } else {
        echo json_encode(['error' => 'HOD not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Database query failed']);
}

$conn->close();
?>
