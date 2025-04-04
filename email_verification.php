<?php
session_start();

if (!isset($_GET['user_id'])) {
    die('Invalid request');
}

$user_id = $_GET['user_id'];

// Database connection
require 'db.php'; // Use your database connection file

// Fetch user details (email, role, branch, username)
$stmt = $conn->prepare("SELECT email, role, branch, username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email, $role, $branch, $username);
$stmt->fetch();
$stmt->close();

if (!$email) {
    die('User not found');
}

// Generate a random verification code
$verification_code = rand(100000, 999999);
$_SESSION['verification_code'] = $verification_code;
$_SESSION['user_id'] = $user_id;
$_SESSION['role'] = $role;
$_SESSION['branch'] = $branch;
$_SESSION['username'] = $username;

// Send email using PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Load PHPMailer

$mail = new PHPMailer(true); // Enable exceptions

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'budidadineshkumar123@gmail.com'; // Your Gmail
    $mail->Password = 'jekn puum rfil yqzq'; // Use App Password if 2-Step Verification is enabled
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS
    $mail->Port = 587;
 // Enable debugging for testing
 


    
    
    // Recipients
    $mail->setFrom('budidadineshkumar123@gmail.com', 'Ideal Institute of Technology'); // Fix email address
    $mail->addAddress($email); // Add recipient

    // Content
    $mail->isHTML(false); // Set to false for plain text
    $mail->Subject = 'Email Verification Code';
    $mail->Body = "Your verification code is: $verification_code";

   
    // Send the email
    $mail->send();
    $message = "A verification code has been sent to your email.";
} catch (Exception $e) {
    $message = "Failed to send verification code. Error: " . $mail->ErrorInfo;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .verification-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        .verification-container input {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .verification-container button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .verification-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2>Email Verification</h2>
        <p><?php echo $message; ?></p>
        <form action="verify_code.php" method="POST">
            <input type="text" name="verification_code" placeholder="Enter verification code" required>
            <button type="submit">Verify</button>
        </form>
    </div>
</body>
</html>