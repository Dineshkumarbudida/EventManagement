<?php
session_start();
header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION["username"]) || !isset($_SESSION["branch"]) || !isset($_SESSION["role"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// ✅ Ensure only Faculty can access this file
if ($_SESSION["role"] !== "Faculty") {
    echo json_encode(["success" => false, "message" => "Access denied. Only Faculty can view this."]);
    exit;
}

// ✅ Send Faculty details
echo json_encode([
    "success" => true,
    "name" => $_SESSION["username"],
    "branch" => $_SESSION["branch"]
]);
?>
