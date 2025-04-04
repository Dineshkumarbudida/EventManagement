<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include PHPMailer
require 'vendor/autoload.php'; // Make sure to install PHPMailer via Composer

// Database connection
$conn = new mysqli("localhost", "root", "", "event_storage");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === "send_otp") {
    $email = $_POST['email'] ?? '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email address."]);
        exit;
    }

    // Check if email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Email not found."]);
        exit;
    }

    // Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // OTP valid for 5 minutes

    // Send OTP via email using PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'budidadineshkumar123@gmail.com'; // Replace with your email
        $mail->Password = 'jekn puum rfil yqzq'; // Replace with your email password
        $mail->SMTPSecure = 'tls'; // Encryption (tls or ssl)
        $mail->Port = 587; // Port for TLS

        // Recipients
        $mail->setFrom('budidadineshkumar123@gmail.com', 'Ideal Institute of Technology'); // Sender email and name
        $mail->addAddress($email); // Recipient email

        // Content
        $mail->Subject = 'Password Reset OTP';
        $mail->Body = "Your OTP is: $otp";

        // Send email
        if (!$mail->send()) {
            echo json_encode(["status" => "error", "message" => "Failed to send OTP. Error: " . $mail->ErrorInfo]);
            exit;
        }

        echo json_encode(["status" => "success", "message" => "OTP sent to your email."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Failed to send OTP. Error: " . $mail->ErrorInfo]);
        exit;
    }
}

elseif ($action === "verify_otp") {
    $entered_otp = $_POST['otp'] ?? '';

    // Check if OTP is expired
    if (!isset($_SESSION['reset_otp']) || time() > $_SESSION['otp_expiry']) {
        echo json_encode(["status" => "error", "message" => "OTP expired."]);
        exit;
    }

    // Verify OTP
    if ($entered_otp == $_SESSION['reset_otp']) {
        $_SESSION['otp_verified'] = true;
        echo json_encode(["status" => "success", "message" => "OTP verified. Proceed to reset password."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Incorrect OTP."]);
    }
}

elseif ($action === "reset_password") {
    // Check if OTP is verified
    if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        echo json_encode(["status" => "error", "message" => "OTP verification required."]);
        exit;
    }

    $new_password = $_POST['new_password'] ?? '';

    // Validate new password
    if (strlen($new_password) < 6) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters."]);
        exit;
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $email = $_SESSION['reset_email'];

    // Update password in the database
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    $stmt->execute();

    // Clear session data
    session_unset();
    session_destroy();

    echo json_encode(["status" => "success", "message" => "Password reset successfully."]);
}

$conn->close();
?>