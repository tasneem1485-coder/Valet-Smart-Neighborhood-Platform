<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();
allowMethods(['GET']);
ensureSession();

$userId = getSessionUserId();

if ($userId === null) {
    jsonResponse(false, 'Not authenticated.', [], 401);
}

$db   = getDBConnection();
$stmt = $db->prepare('SELECT id, full_name, email, phone, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    // Session is stale
    session_destroy();
    jsonResponse(false, 'User not found.', [], 404);
}

jsonResponse(true, 'Authenticated.', [
    'user' => [
        'id'         => (int)$user['id'],
        'full_name'  => $user['full_name'],
        'email'      => $user['email'],
        'phone'      => $user['phone'],
        'created_at' => $user['created_at'],
    ]
]);
