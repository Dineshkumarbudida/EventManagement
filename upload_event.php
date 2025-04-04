<?php
// Enable error reporting
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

// Assign session data
$username = $_SESSION["username"];
$branch = $_SESSION["branch"];
$role = $_SESSION["role"];

// Assign input data
$event_name = trim($_POST["event_name"]);
$description = trim($_POST["description"]);
$start_date = trim($_POST["start_date"]);
$end_date = trim($_POST["end_date"]);
$collaborators = isset($_POST["selected_faculties"]) ? json_decode($_POST["selected_faculties"], true) : [];

// Validate required fields
$requiredFields = ['event_name', 'description', 'start_date', 'end_date'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        sendResponse(false, "Missing required field: $field");
    }
}

// Validate date format
if (!strtotime($start_date) || !strtotime($end_date)) {
    sendResponse(false, "Invalid date format.");
}

// Define Folder Structure Based on Role and Collaboration
if ($role === "HOD" && !empty($collaborators)) {
    // HOD uploading to faculty CommonFolder - path will be built per faculty
    $base_folder = "ideal/$branch/";
} elseif ($role === "HOD") {
    $base_folder = "ideal/$branch/{$branch}_HOD/MyUploads/";
} elseif ($role === "Faculty") {
    $base_folder = "ideal/$branch/$username/MyUploads/";
} else {
    sendResponse(false, "Unauthorized role.");
}

// Check if the event already exists (only for non-collaboration or same creator)
if (empty($collaborators)) {
    $stmt = $conn->prepare("SELECT id, event_name, media_path, created_by FROM events WHERE event_name = ? AND branch = ? AND created_by = ?");
    $stmt->bind_param("sss", $event_name, $branch, $username);
    $stmt->execute();
    $stmt->store_result();
    $event_exists = ($stmt->num_rows > 0);
    $stmt->bind_result($existing_id, $old_event_name, $old_media, $created_by);
    $stmt->fetch();
    $stmt->close();
} else {
    $event_exists = false;
}

// Handle folder paths
if ($event_exists) {
    $old_event_folder = $base_folder . $old_event_name;
    $new_event_folder = $base_folder . $event_name;
    
    // Rename folder if event name changed
    if ($old_event_name !== $event_name && is_dir($old_event_folder)) {
        rename($old_event_folder, $new_event_folder);
    }
} elseif (empty($collaborators)) {
    $new_event_folder = $base_folder . $event_name;
    if (!file_exists($new_event_folder)) {
        mkdir($new_event_folder, 0777, true);
    }
}

// Handle file uploads
$media_files = [];
$is_collaboration = !empty($collaborators) ? 1 : 0;

// For Faculty: Media is mandatory for collaborated events
if ($role === "Faculty" && $is_collaboration && (!isset($_FILES["media"]) || empty($_FILES["media"]["name"][0]))) {
    sendResponse(false, "Media upload is required for collaborated events");
}

if (isset($_FILES["media"]) && !empty($_FILES["media"]["name"][0])) {
    if ($is_collaboration && $role === "HOD") {
        // HOD assigning to faculty - files go to each faculty's CommonFolder
        foreach ($collaborators as $faculty_id) {
            // Get faculty username from ID
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->bind_param("i", $faculty_id);
            $stmt->execute();
            $stmt->bind_result($faculty_username);
            if ($stmt->fetch()) {
                $faculty_folder = "ideal/$branch/$faculty_username/CommonFolder/$event_name/";
                if (!file_exists($faculty_folder)) {
                    mkdir($faculty_folder, 0777, true);
                }
                
                foreach ($_FILES["media"]["tmp_name"] as $index => $tmpName) {
                    if (!empty($tmpName)) {
                        $filename = basename($_FILES["media"]["name"][$index]);
                        $target_path = "$faculty_folder/$filename";
                        
                        if (move_uploaded_file($tmpName, $target_path)) {
                            $media_files[$faculty_username][] = $target_path;
                        }
                    }
                }
            }
            $stmt->close();
        }
    } elseif ($is_collaboration && $role === "Faculty") {
        // Faculty uploading to collaboration - files go to their CommonFolder
        $common_folder = "ideal/$branch/$username/CommonFolder/$event_name/";
        if (!file_exists($common_folder)) {
            mkdir($common_folder, 0777, true);
        }
        
        foreach ($_FILES["media"]["tmp_name"] as $index => $tmpName) {
            if (!empty($tmpName)) {
                $filename = basename($_FILES["media"]["name"][$index]);
                $target_path = "$common_folder/$filename";
                
                if (move_uploaded_file($tmpName, $target_path)) {
                    $media_files[] = $target_path;
                }
            }
        }
    } else {
        // Regular upload (non-collaboration)
        $upload_folder = ($role === "HOD") 
            ? "ideal/$branch/{$branch}_HOD/MyUploads/$event_name/"
            : "ideal/$branch/$username/MyUploads/$event_name/";
            
        if (!file_exists($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }
        
        // Delete old media if exists (for updates)
        if ($event_exists) {
            $old_media = json_decode($old_media, true) ?? [];
            foreach ($old_media as $old_file) {
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
        }
        
        // Upload new media
        foreach ($_FILES["media"]["tmp_name"] as $index => $tmpName) {
            if (!empty($tmpName)) {
                $filename = basename($_FILES["media"]["name"][$index]);
                $target_path = "$upload_folder/$filename";
                
                if (move_uploaded_file($tmpName, $target_path)) {
                    $media_files[] = $target_path;
                }
            }
        }
    }
} elseif ($event_exists) {
    $media_files = json_decode($old_media, true) ?? [];
}

// For HOD collaboration: Ensure CommonFolder is created even with no media
if ($is_collaboration && $role === "HOD" && empty($media_files)) {
    foreach ($collaborators as $faculty_id) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $stmt->bind_result($faculty_username);
        if ($stmt->fetch()) {
            $faculty_folder = "ideal/$branch/$faculty_username/CommonFolder/$event_name/";
            if (!file_exists($faculty_folder)) {
                mkdir($faculty_folder, 0777, true);
            }
        }
        $stmt->close();
    }
}

$media_json = json_encode($media_files);

// Database operations
if ($event_exists) {
    // Update existing event
    $stmt = $conn->prepare("UPDATE events SET event_name = ?, event_description = ?, from_date = ?, to_date = ?, media_path = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $event_name, $description, $start_date, $end_date, $media_json, $existing_id);

    if ($stmt->execute()) {
        sendResponse(true, "Event updated successfully.", ["new_media" => $media_files]);
    } else {
        sendResponse(false, "Database update failed: " . $stmt->error);
    }
    $stmt->close();
} else {
    if ($is_collaboration && $role === "HOD") {
        // First, get faculty usernames from their IDs
        $faculty_usernames = [];
        foreach ($collaborators as $faculty_id) {
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->bind_param("i", $faculty_id);
            $stmt->execute();
            $stmt->bind_result($faculty_username);
            if ($stmt->fetch()) {
                $faculty_usernames[] = $faculty_username;
            }
            $stmt->close();
        }

        // Insert separate records for each faculty in collaboration using usernames
        foreach ($faculty_usernames as $faculty) {
            $faculty_media = isset($media_files[$faculty]) ? json_encode($media_files[$faculty]) : '[]';
            $stmt = $conn->prepare("INSERT INTO events (event_name, event_description, from_date, to_date, media_path, branch, created_by, is_collaboration, assigned_faculty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $is_collab_int = 1;
            $stmt->bind_param("sssssssis", $event_name, $description, $start_date, $end_date, $faculty_media, $branch, $username, $is_collab_int, $faculty);
            
            if (!$stmt->execute()) {
                sendResponse(false, "Failed to create event for faculty $faculty: " . $stmt->error);
            }
            $stmt->close();
        }
        sendResponse(true, "Collaborative event created successfully.");
    } else {
        // Regular event creation (non-collaboration)
        if ($is_collaboration && $role === "Faculty") {
            // Get HOD username for faculty collaboration
            $stmt = $conn->prepare("SELECT username FROM users WHERE role = 'HOD' AND branch = ?");
            $stmt->bind_param("s", $branch);
            $stmt->execute();
            $stmt->bind_result($hod_username);
            $stmt->fetch();
            $stmt->close();
            
            $assigned_faculty = $hod_username; // Assign HOD username
        } else {
            $assigned_faculty = ""; // No assignment for non-collaboration
        }
        
        $is_collab_int = (int)$is_collaboration;
        
        $stmt = $conn->prepare("INSERT INTO events (event_name, event_description, from_date, to_date, media_path, branch, created_by, is_collaboration, assigned_faculty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssis", $event_name, $description, $start_date, $end_date, $media_json, $branch, $username, $is_collab_int, $assigned_faculty);
        
        if ($stmt->execute()) {
            $event_id = $stmt->insert_id;

            // For faculty collaboration: Ensure CommonFolder exists
            if ($is_collaboration && $role === "Faculty") {
                $common_folder = "ideal/$branch/$username/CommonFolder/$event_name/";
                if (!file_exists($common_folder)) {
                    if (!mkdir($common_folder, 0777, true)) {
                        sendResponse(false, "Failed to create collaboration folder");
                    }
                }
            }

            sendResponse(true, "Event uploaded successfully.");
        } else {
            sendResponse(false, "Database insert failed: " . $stmt->error);
        }
        $stmt->close();
    }
}
?>