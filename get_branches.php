<?php
// Connect to the database
$connection = new mysqli("localhost", "root", "", "event_storage");

if ($connection->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

// Fetch all branches from the database
$result = $connection->query("SELECT name FROM branches");
$branches = [];

while ($row = $result->fetch_assoc()) {
    $branches[] = ["name" => $row["name"]];
}

// Return JSON response
echo json_encode($branches);

$connection->close();
?>
