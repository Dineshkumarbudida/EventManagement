<?php
require "db.php";

$sql = "SELECT id, username, email, role, branch, date_created FROM users ORDER BY id ASC";
$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
?>
