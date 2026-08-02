<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization");

require_once 'db_config.php';
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 🔐 Check Token
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';

if (!$auth) {
    echo json_encode(["status" => "error"]);
    exit();
}

$token = str_replace("Bearer ", "", $auth);

$key = "38089ad498a438241cb2a3b327e451d3536199ba26646c3a7aa209f03eccf070327aac95a23fa7481d515c8d7864b819b5e0151c7da47711044920cca4001b25fe7ef2d5ba71b09ff2ecf174cd5d0330fdf494355ecfb94da5c5e5f08d4dd71d30a198319eec5f2a88a9ef930525a9e53abca1c47183485a8f7ecaf1519e6779";

try {
    JWT::decode($token, new Key($key, 'HS256'));

    $id = $_GET['id'];

    $sql = "SELECT * FROM registrations WHERE id='$id' AND verified_status='Verified'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode([
            "status" => "success",
            "data" => $result->fetch_assoc()
        ]);
    } else {
        echo json_encode(["status" => "error"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error"]);
}
?>