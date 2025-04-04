<?php
session_start(); // Start the session

// Destroy the session
session_destroy();

// Return a success response
echo json_encode(["status" => "success", "message" => "Logged out successfully"]);
?>