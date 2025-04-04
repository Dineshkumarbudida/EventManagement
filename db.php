<?php
// Database configuration
$host = "localhost"; // Database host
$user = "root"; // Default XAMPP username
$pass = ""; // Default XAMPP password (empty)
$dbname = "event_storage"; // Your database name

// Create a new MySQLi connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    // Log the error (for debugging purposes) 
    error_log("Database connection failed: " . $conn->connect_error);

    // Return a JSON response (useful for API-based applications)
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed. Please try again later."
    ]));
}

// Set the charset to UTF-8 (to avoid character encoding issues)
$conn->set_charset("utf8mb4");

// Optional: Enable error reporting for mysqli (useful during development)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>