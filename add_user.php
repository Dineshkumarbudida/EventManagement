<?php
require "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $branch = $_POST['branch'];

    if (empty($username) || empty($email) || empty($_POST['password']) || empty($role) || empty($branch)) {
        echo json_encode(["status" => "error", "message" => "All fields are required"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit;
    }

    $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkUser->bind_param("s", $username);
    $checkUser->execute();
    $result = $checkUser->get_result();

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE users SET email = ?, role = ?, branch = ? WHERE username = ?");
        $stmt->bind_param("ssss", $email, $role, $branch, $username);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User data updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update user"]);
        }
        $stmt->close();
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, branch, date_created) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $username, $email, $password, $role, $branch);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add user");
            }

            $rootDir = "Ideal";
            $folderErrors = [];

            switch ($role) {
                case "HOD":
                    $hodPath = "$rootDir/$branch/{$branch}_HOD";
                    if (!is_dir($hodPath)) {
                        if (mkdir($hodPath, 0777, true)) {
                            $subfolders = ["MyUploads", "FacultyUploads", "FacultyCollaboration", "AllFacultiesCommon", "CommonFolder"];
                            foreach ($subfolders as $folder) {
                                $subfolderPath = "$hodPath/$folder";
                                if (!mkdir($subfolderPath, 0777, true)) {
                                    $folderErrors[] = "HOD subfolder $folder creation failed";
                                }
                            }
                        } else {
                            $folderErrors[] = "HOD folder creation failed";
                        }
                    } else {
                        $folderErrors[] = "HOD folder already exists";
                    }
                    break;

                case "Faculty":
                    $facultyPath = "$rootDir/$branch/$username";
                    if (!is_dir($facultyPath)) {
                        if (mkdir($facultyPath, 0777, true)) {
                            $subfolders = ["MyUploads", "CommonFolder"];
                            foreach ($subfolders as $folder) {
                                $subfolderPath = "$facultyPath/$folder";
                                if (!mkdir($subfolderPath, 0777, true)) {
                                    $folderErrors[] = "Faculty subfolder $folder creation failed";
                                }
                            }
                        } else {
                            $folderErrors[] = "Faculty folder creation failed";
                        }
                    } else {
                        $folderErrors[] = "Faculty folder already exists";
                    }
                    break;

                case "Principal":
                    $principalPath = "$rootDir/Principal";
                    if (!is_dir($principalPath)) {
                        if (mkdir($principalPath, 0777, true)) {
                            $subfolders = ["MyUploads", "HodUploads", "HodCollaboration", "AllHodsCommon"];
                            foreach ($subfolders as $folder) {
                                $subfolderPath = "$principalPath/$folder";
                                if (!mkdir($subfolderPath, 0777, true)) {
                                    $folderErrors[] = "Principal subfolder $folder creation failed";
                                }
                            }
                        } else {
                            $folderErrors[] = "Principal folder creation failed";
                        }
                    } else {
                        $folderErrors[] = "Principal folder already exists";
                    }
                    break;
            }

            if (!empty($folderErrors)) {
                throw new Exception("User added but folder creation failed: " . implode(", ", $folderErrors));
            }

            $conn->commit();
            echo json_encode(["status" => "success", "message" => "User and folders created successfully"]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        $stmt->close();
    }

    $checkUser->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>