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

$data = json_decode(file_get_contents("php://input"));

$user_id = $data->user_id;

// 📅 Current month/year
$month = date("m");
$year = date("y");
$month_year = "$month/$year";

try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    // 🔐 START TRANSACTION
    $conn->begin_transaction();

    // 1️⃣ Lock row for this month
    $stmt = $conn->prepare("SELECT counter FROM invoice_counter WHERE month_year = ? FOR UPDATE");
    $stmt->bind_param("s", $month_year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Row exists → increment
        $row = $result->fetch_assoc();
        $counter = $row['counter'] + 1;

        $update = $conn->prepare("UPDATE invoice_counter SET counter = ? WHERE month_year = ?");
        $update->bind_param("is", $counter, $month_year);
        $update->execute();
    } else {
        // First invoice of month
        $counter = 1;

        $insert = $conn->prepare("INSERT INTO invoice_counter (month_year, counter) VALUES (?, ?)");
        $insert->bind_param("si", $month_year, $counter);
        $insert->execute();
    }

    // 2️⃣ Format invoice number
    $counter_padded = str_pad($counter, 3, "0", STR_PAD_LEFT);
    $invoice_no = "LEARN-$counter_padded-$month_year";

    // 3️⃣ Verify user
    $verify = $conn->prepare("UPDATE registrations SET invoice_no = ?, payment_status = 'Paid', verified_status = 'Verified' WHERE id = ?");
    $verify->bind_param("ss", $invoice_no, $user_id);
    $verify->execute();

    // 4️⃣ Insert invoice
    // $inv = $conn->prepare("INSERT INTO invoice (user_id, invoice_no) VALUES (?, ?)");
    // $inv->bind_param("is", $user_id, $invoice_no);
    // $inv->execute();

    // ✅ COMMIT
    $conn->commit();

    echo json_encode([
        "status" => "success",
        "invoice_no" => $invoice_no
    ]);
} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => "Transaction failed"
    ]);
}

?>