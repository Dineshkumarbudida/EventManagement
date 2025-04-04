<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Start session
session_start();

// Include database connection
include "db.php";

// Function to send JSON response and exit
function sendResponse($success, $message) {
    echo json_encode(["success" => $success, "message" => $message]);
    exit;
}

// Function to recursively delete a directory and its contents
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    // Open the directory
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = "$dir/$file";
        if (is_dir($path)) {
            deleteDirectory($path); // Recursively delete subdirectories
        } else {
            unlink($path); // Delete files
        }
    }

    return rmdir($dir); // Delete the empty directory
}

// Check if user is logged in
if (!isset($_SESSION["username"]) || !isset($_SESSION["branch"])) {
    sendResponse(false, "Unauthorized access.");
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Debugging: Log request data
file_put_contents("debug.log", print_r($data, true));

if (!isset($data["id"])) {
    sendResponse(false, "Missing event ID.");
}

$event_id = $data["id"];
$branch = $_SESSION["branch"];

// Find event details before deleting
$stmt = $conn->prepare("SELECT event_name, media_path FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    sendResponse(false, "Event not found.");
}

$stmt->bind_result($event_name, $media_path);
$stmt->fetch();
$stmt->close();

// Define event folder path
$event_folder = "ideal/$branch/{$branch}_HOD/MyUploads/" . $event_name;

// Delete media files if they exist
$media_files = json_decode($media_path, true);
if (!empty($media_files)) {
    foreach ($media_files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Delete event folder and its contents
if (is_dir($event_folder)) {
    if (!deleteDirectory($event_folder)) {
        sendResponse(false, "Failed to delete event folder.");
    }
}

// Delete event from database
$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);

if ($stmt->execute()) {
    sendResponse(true, "Event deleted successfully.");
} else {
    sendResponse(false, "Failed to delete event.");
}

$stmt->close();
$conn->close();
?>
