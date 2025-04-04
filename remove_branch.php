<?php
header("Content-Type: application/json"); // Ensure JSON output
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "db.php"; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $branch = trim($data['branch'] ?? '');

    if (empty($branch)) {
        echo json_encode(["status" => "error", "message" => "Branch name is required"]);
        exit;
    }

    // Check if branch exists
    $checkBranch = $conn->prepare("SELECT * FROM branches WHERE name = ?");
    $checkBranch->bind_param("s", $branch);
    $checkBranch->execute();
    $result = $checkBranch->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Branch not found"]);
        exit;
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM branches WHERE name = ?");
    $stmt->bind_param("s", $branch);

    if ($stmt->execute()) {
        // Remove folder (even if not empty)
        function deleteFolder($folderPath) {
            if (!is_dir($folderPath)) return;
            foreach (scandir($folderPath) as $file) {
                if ($file === '.' || $file === '..') continue;
                $filePath = "$folderPath/$file";
                is_dir($filePath) ? deleteFolder($filePath) : unlink($filePath);
            }
            rmdir($folderPath);
        }

        $folderPath = "Ideal/" . $branch;
        if (file_exists($folderPath) && is_dir($folderPath)) {
            deleteFolder($folderPath);
        }

        echo json_encode(["status" => "success", "message" => "Branch removed successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to remove branch: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
