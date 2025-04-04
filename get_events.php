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
function sendResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION["username"], $_SESSION["branch"], $_SESSION["role"])) {
    sendResponse(false, "Unauthorized access. Please log in.");
}

$username = $_SESSION["username"];
$branch = $_SESSION["branch"];
$role = $_SESSION["role"];

// Get filter value from query parameter
$filter = $_GET["filter"] ?? "my-uploads"; // Default to "my-uploads"

// Validate filter value
$validFilters = ["my-uploads", "faculty-uploads", "faculty-collaboration", "all-faculties-common", "common-folder"];
if (!in_array($filter, $validFilters)) {
    sendResponse(false, "Invalid filter value.");
}

// Base query with corrected field aliases to match frontend expectations
$query = "SELECT e.id, 
          e.event_name AS event_name,  -- Changed from 'name' to match frontend
          e.event_description AS description, 
          e.from_date AS from_date,    -- Changed from 'start_date'
          e.to_date AS to_date,        -- Changed from 'end_date'
          e.media_path AS media, 
          e.created_by, 
          e.assigned_faculty, 
          e.is_collaboration 
          FROM events e
          WHERE e.branch = ?";

$params = [$branch];
$types = "s"; // Bind types

// Modify query based on filter
switch ($filter) {
    case "my-uploads":
        if ($role === "Faculty") {
            $query .= " AND e.created_by = ? AND e.is_collaboration = 0";
        } else {
            $query .= " AND e.created_by = ? AND e.is_collaboration = 0";
        }
        $params[] = $username;
        $types .= "s";
        break;

    case "faculty-uploads":
        $query .= " AND e.created_by IN (SELECT username FROM users WHERE role = 'Faculty' AND branch = ?)";
        $params[] = $branch;
        $types .= "s";
        break;

    case "faculty-collaboration":
        if ($role === "HOD") {
            $query = "SELECT e.id, 
                      e.event_name AS event_name, 
                      e.event_description AS description, 
                      e.from_date AS from_date, 
                      e.to_date AS to_date, 
                      e.media_path AS media, 
                      e.created_by, 
                      e.is_collaboration,
                      GROUP_CONCAT(u.username SEPARATOR ', ') AS assigned_faculty_names
                      FROM events e
                      JOIN users u ON FIND_IN_SET(u.username, e.assigned_faculty)
                      WHERE e.branch = ? 
                      AND e.is_collaboration = 1 
                      AND e.created_by = ?
                      GROUP BY e.id";
            $params = [$branch, $username];
            $types = "ss";
        } else {
            $query .= " AND e.is_collaboration = 1 AND FIND_IN_SET(?, e.assigned_faculty)";
            $params[] = $username;
            $types .= "s";
        }
        break;

    case "all-faculties-common":
        $query .= " AND e.created_by IN (SELECT username FROM users WHERE role = 'Faculty' AND branch = ?) 
                    AND e.is_collaboration = 1";
        $params[] = $branch;
        $types .= "s";
        break;

    case "common-folder":
        if ($role === "Faculty") {
            $query .= " AND e.is_collaboration = 1 
                        AND e.created_by IN (SELECT username FROM users WHERE role = 'HOD' AND branch = ?)
                        AND FIND_IN_SET(?, e.assigned_faculty)";
            $params[] = $branch;
            $params[] = $username;
            $types .= "ss";
        } else {
            $query .= " AND e.is_collaboration = 1 
                        AND e.created_by IN (SELECT username FROM users WHERE role = 'Principal')
                        AND FIND_IN_SET(?, e.assigned_faculty)";
            $params[] = $username;
            $types .= "s";
        }
        break;
}

// Prepare statement
$stmt = $conn->prepare($query);
if (!$stmt) {
    sendResponse(false, "Database error: Failed to prepare query.");
}

// Bind parameters
if ($stmt->bind_param($types, ...$params) === false) {
    sendResponse(false, "Database error: Failed to bind parameters.");
}

// Execute statement
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $events = [];

    while ($row = $result->fetch_assoc()) {
        // Handle media paths more robustly
        $media = ['No Media']; // Default value
        if (!empty($row['media'])) {
            $decoded = json_decode($row['media'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $media = $decoded;
            } elseif (is_array($row['media'])) {
                $media = $row['media'];
            } else {
                $media = [$row['media']];
            }
        }
    
        // Format dates properly
        $from_date = !empty($row['from_date']) ? date('Y-m-d', strtotime($row['from_date'])) : 'Not set';
        $to_date = !empty($row['to_date']) ? date('Y-m-d', strtotime($row['to_date'])) : 'Not set';
        
        // Ensure all fields have proper values
        $events[] = [
            "id" => (int)$row["id"],
            "name" => htmlspecialchars($row["event_name"] ?? 'Unnamed Event'),  // Changed to 'name'
            "description" => htmlspecialchars($row["description"] ?? 'No description'),
            "start_date" => $from_date, 
            "end_date" => $to_date,      
            "media" => $media,
            "created_by" => htmlspecialchars($row["created_by"] ?? 'Unknown'),
            "assigned_faculty" => htmlspecialchars($row['assigned_faculty_names'] ?? $row['assigned_faculty'] ?? 'Not assigned')        ];
    }
    sendResponse(true, "Events fetched successfully.", $events);
} else {
    sendResponse(false, "Database error: " . $stmt->error);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>