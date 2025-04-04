<?php
include 'db.php'; // Ensure database connection is available

header('Content-Type: application/json');

// Fetch all faculty members
$sql = "SELECT id, username FROM users WHERE role = 'Faculty'";
$result = $conn->query($sql);

$facultyList = [];

while ($row = $result->fetch_assoc()) {
    $facultyList[] = [
        'id' => $row['id'],
        'username' => $row['username'] // Ensure 'username' is correct column name
    ];
}

echo json_encode($facultyList);
?>
