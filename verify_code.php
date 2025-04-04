<?php
session_start();

if (!isset($_SESSION['verification_code']) || !isset($_SESSION['user_id'])) {
    die('Invalid request');
}

$entered_code = $_POST['verification_code'];

if ($entered_code == $_SESSION['verification_code']) {
    // Verification successful
    $role = strtolower($_SESSION['role']); // Convert to lowercase for better handling
    $branch = $_SESSION['branch'];
    $username = $_SESSION['username'];

    // Redirect based on role
    if ($role === 'hod') {
        header("Location: hod.html");
        exit();
    } else if ($role === 'faculty') {
        header("Location: faculty.html");
        exit();
    } else if ($role === 'principal') {
        header("Location: principal_dashboard.html");
        exit();
    } else {
        header("Location: student.html"); // Default dashboard for other roles
        exit();
    }
} else {
    echo "Invalid verification code. Please try again.";
}
