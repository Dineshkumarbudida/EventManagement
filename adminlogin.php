<?php
// Allow CORS for development (restrict in production)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Load environment variables (if using .env file)
// require_once 'vendor/autoload.php'; // Uncomment if using Dotenv library
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();

// Sample Admin Credentials (use environment variables in production)
$admin_username = "admin";
$admin_password_hash = password_hash("123", PASSWORD_DEFAULT); // Hash the password

// Get JSON data from frontend
$json_input = file_get_contents("php://input");
$data = json_decode($json_input, true);

// Validate JSON input
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
if (empty($data['username']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

// Check if username and password match
if ($data['username'] === $admin_username && password_verify($data['password'], $admin_password_hash)) {
    echo json_encode(['success' => true, 'message' => 'Login Successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Credentials']);
}
?>