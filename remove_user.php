<?php
header("Content-Type: application/json"); // Ensure JSON output
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_clean(); // Clear any unwanted output

require "db.php"; // Include the database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Read JSON data from fetch request
        $inputData = json_decode(file_get_contents("php://input"), true);
        if (!isset($inputData['username'])) {
            throw new Exception("Username not received");
        }

        $username = trim($inputData['username']);
        if (empty($username)) {
            throw new Exception("Username is required");
        }

        // Check if the user exists
        $checkUser = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $checkUser->bind_param("s", $username);
        $checkUser->execute();
        $result = $checkUser->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("User not found");
        }

        // Fetch user details (role, branch)
        $user = $result->fetch_assoc();
        $role = $user['role'];
        $branch = $user['branch'];

        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            throw new Exception("Failed to remove user: " . $stmt->error);
        }

        // Folder deletion logic
        $rootDirectory = "Ideal";
        $branchFolder = "$rootDirectory/$branch";
        if ($role == "HOD") {
            $userFolder = "$branchFolder/{$branch}_HOD";
        } elseif ($role == "Faculty") {
            $userFolder = "$branchFolder/$username";
        }

        // Delete the user's folder
        if (isset($userFolder) && is_dir($userFolder)) {
            if (!deleteFolder($userFolder)) {
                throw new Exception("Failed to delete user folder");
            }
        }

        echo json_encode(["status" => "success", "message" => "User and folder removed successfully"]);
    } catch (Exception $e) {
        error_log("Remove User Error: " . $e->getMessage()); // Log error for debugging
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($checkUser)) $checkUser->close();
        $conn->close();
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

// Function to delete folder
function deleteFolder($folderPath) {
    if (!is_dir($folderPath)) {
        return false;
    }
    $files = array_diff(scandir($folderPath), ['.', '..']);
    foreach ($files as $file) {
        $path = "$folderPath/$file";
        is_dir($path) ? deleteFolder($path) : unlink($path);
    }
    return rmdir($folderPath);
}
?>
