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

// Validate required fields
$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
    jsonResponse(false, 'All fields are required: full_name, email, phone, password.', [], 422);
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.', [], 422);
}

// Validate password length
if (strlen($password) < 8) {
    jsonResponse(false, 'Password must be at least 8 characters long.', [], 422);
}

$db = getDBConnection();

// Check if email already exists
$stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    jsonResponse(false, 'An account with this email address already exists.', [], 409);
}

// Hash password and insert user
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare(
    'INSERT INTO users (full_name, email, phone, password_hash) VALUES (:full_name, :email, :phone, :password_hash)'
);
$stmt->execute([
    ':full_name'     => $fullName,
    ':email'         => $email,
    ':phone'         => $phone,
    ':password_hash' => $passwordHash,
]);

$userId = (int)$db->lastInsertId();

// Start session for the new user
$_SESSION['user_id']   = $userId;
$_SESSION['user_name'] = $fullName;
$_SESSION['user_email']= $email;

jsonResponse(true, 'Account created successfully. Welcome to VALET!', [
    'user' => [
        'id'        => $userId,
        'full_name' => $fullName,
        'email'     => $email,
        'phone'     => $phone,
    ]
], 201);
