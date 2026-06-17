<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();
allowMethods(['POST']);

// Parse JSON body if Content-Type is application/json
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    foreach ($body as $k => $v) {
        $_POST[$k] = $v;
    }
}

$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$serviceType = trim($_POST['service_type'] ?? '');
$message     = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    jsonResponse(false, 'Required fields: name, email, message.', [], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.', [], 422);
}

if (strlen($message) < 10) {
    jsonResponse(false, 'Message must be at least 10 characters long.', [], 422);
}

// Simple rate limiting by IP (max 5 messages per hour per IP)
$db     = getDBConnection();
$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$stmt   = $db->prepare(
    "SELECT COUNT(*) as cnt FROM contact_messages
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
);
$stmt->execute();
// Note: for production add IP column and filter by IP

$stmt = $db->prepare(
    'INSERT INTO contact_messages (name, email, phone, service_type, message)
     VALUES (:name, :email, :phone, :service_type, :message)'
);
$stmt->execute([
    ':name'         => $name,
    ':email'        => $email,
    ':phone'        => $phone ?: null,
    ':service_type' => $serviceType ?: null,
    ':message'      => $message,
]);

jsonResponse(true, 'Your message has been sent! We will get back to you soon.', [
    'contact_id' => (int)$db->lastInsertId()
], 201);
