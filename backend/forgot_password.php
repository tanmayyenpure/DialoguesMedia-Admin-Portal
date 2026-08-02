<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// DB Connection
require_once 'db_config.php';

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

// Get email from request
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(["status" => "error", "message" => "Email required"]);
    exit;
}

// Check admin exists
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Email not found"]);
    exit;
}

//
// 🔐 STEP 1: Generate Random Password
//
function generatePassword($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

$newPassword = generatePassword(8);

//
// 🔐 STEP 2: Hash Password
//
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

//
// 🔐 STEP 3: Update DB
//
// $update = $conn->prepare("UPDATE admin_users SET password = ? WHERE email = ?");
// $update->bind_param("ss", $hashedPassword, $email);

if (!$update->execute()) {
    echo json_encode(["status" => "error", "message" => "Failed to update password"]);
    exit;
}

//
// 📧 STEP 4: Send Email
//
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;

    // 🔥 YOUR GMAIL
    $mail->Username   = '';

    // 🔥 APP PASSWORD (NOT normal password)
    $mail->Password   = '';

    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Sender & Receiver
    $mail->setFrom('', 'dialogues meadia learn admin');
    $mail->addAddress($email);

    // Email Content
    $mail->isHTML(true);
    $mail->Subject = 'Admin Password Reset';

    $mail->Body = "
        <h3>Password Reset</h3>
        <p>Your new password is:</p><br>
        <h2 style='color:red;'>$newPassword</h2>
        <p>Please do not share with anyone.</p>
    ";

    $mail->send();

    echo json_encode([
        "status" => "success",
        "message" => "New password sent to email"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Mailer Error: " . $mail->ErrorInfo
    ]);
}
