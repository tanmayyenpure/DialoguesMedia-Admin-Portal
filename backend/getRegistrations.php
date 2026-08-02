<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// ✅ Handle Preflight Request FIRST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); // STOP execution here
}

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once 'db_config.php';

// Get token from header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader) {
    echo json_encode(["status" => "error", "message" => "No token"]);
    exit;
}

$token = str_replace("Bearer ", "", $authHeader);

$key = "38089ad498a438241cb2a3b327e451d3536199ba26646c3a7aa209f03eccf070327aac95a23fa7481d515c8d7864b819b5e0151c7da47711044920cca4001b25fe7ef2d5ba71b09ff2ecf174cd5d0330fdf494355ecfb94da5c5e5f08d4dd71d30a198319eec5f2a88a9ef930525a9e53abca1c47183485a8f7ecaf1519e6779";

try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));

    $sql = "SELECT * FROM registrations ORDER BY created_at DESC";
    $result = $conn->query($sql);

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid token"
    ]);
}
?>