<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();
allowMethods(['POST']);
ensureSession();

// Parse JSON body if Content-Type is application/json
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    foreach ($body as $k => $v) {
        $_POST[$k] = $v;
    }
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(false, 'Email and password are required.', [], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.', [], 422);
}

$db = getDBConnection();

$stmt = $db->prepare('SELECT id, full_name, email, phone, password_hash FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Generic message to avoid user enumeration
    jsonResponse(false, 'Invalid email or password.', [], 401);
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

$_SESSION['user_id']    = (int)$user['id'];
$_SESSION['user_name']  = $user['full_name'];
$_SESSION['user_email'] = $user['email'];

jsonResponse(true, 'Signed in successfully. Welcome back!', [
    'user' => [
        'id'        => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'phone'     => $user['phone'],
    ]
]);
