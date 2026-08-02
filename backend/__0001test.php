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

require_once 'db_config.php';


try {

    $sql = "SELECT * FROM registrations ORDER BY created_at DESC";
    $result = $conn->query($sql);

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $sql = "SELECT * FROM admin_users";
    $result = $conn->query($sql);

    $data2 = [];
    while ($row = $result->fetch_assoc()) {
        $data2[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $data,
        "data2" => $data2
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid token"
    ]);
}
?>