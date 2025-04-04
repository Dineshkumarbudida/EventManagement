<?php
require "db.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newBranch = trim($_POST['branch']);

    if (empty($newBranch)) {
        echo json_encode(["status" => "error", "message" => "Branch name cannot be empty"]);
        exit;
    }

    // Debugging
    file_put_contents("debug.log", "Received branch: " . $newBranch . PHP_EOL, FILE_APPEND);

    // Check if branch exists
    $checkBranch = $conn->prepare("SELECT * FROM branches WHERE name = ?");
    if (!$checkBranch) {
        echo json_encode(["status" => "error", "message" => "SQL Prepare Failed: " . $conn->error]);
        exit;
    }

    $checkBranch->bind_param("s", $newBranch);
    $checkBranch->execute();
    $result = $checkBranch->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Branch already exists"]);
        exit;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO branches (name) VALUES (?)");
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "SQL Prepare Failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("s", $newBranch);
    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => "Failed to add branch: " . $stmt->error]);
        exit;
    }

    // Debugging Folder Path
    $folderPath = "Ideal/" . $newBranch;
    file_put_contents("debug.log", "Creating folder: " . $folderPath . PHP_EOL, FILE_APPEND);

    if (!file_exists($folderPath)) {
        if (!mkdir($folderPath, 0777, true)) {
            echo json_encode(["status" => "error", "message" => "Failed to create folder at: " . $folderPath]);
            exit;
        }
    }

    echo json_encode(["status" => "success", "message" => "Branch added successfully"]);

    $stmt->close();
    $conn->close();
}
