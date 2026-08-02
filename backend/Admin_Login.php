<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");



if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'vendor/autoload.php';

use Firebase\JWT\JWT;


require_once 'db_config.php';


$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';


// Fetch admin
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

// $row = $result->fetch_assoc();
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

// 🔐 Verify password
if ($result->num_rows > 0) {

    $key = "38089ad498a438241cb2a3b327e451d3536199ba26646c3a7aa209f03eccf070327aac95a23fa7481d515c8d7864b819b5e0151c7da47711044920cca4001b25fe7ef2d5ba71b09ff2ecf174cd5d0330fdf494355ecfb94da5c5e5f08d4dd71d30a198319eec5f2a88a9ef930525a9e53abca1c47183485a8f7ecaf1519e6779";

    $payload = [
        "iss" => "localhost",
        "iat" => time(),
        "exp" => time() + (60 * 60), // 1 hour
        "data" => [
            "username" => $username
        ]
    ];

    $jwt = JWT::encode($payload, $key, 'HS256');

    echo json_encode([
        "status" => "success",
        "token" => $jwt
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid password"
    ]);
}
