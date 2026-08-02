<?php

$host = "localhost";
$user = "dialoguesmedia_learn";
$password = "adminroot@123$";
$database = "dialoguesmedia.in_db";

// DB Connection
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit();
}
?>