<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();
allowMethods(['GET']);

$userId = requireLogin();

$db   = getDBConnection();
$stmt = $db->prepare(
    'SELECT id, service_type, preferred_date, preferred_time, address, notes, status, created_at
     FROM bookings
     WHERE user_id = :user_id
     ORDER BY created_at DESC
     LIMIT 50'
);
$stmt->execute([':user_id' => $userId]);
$bookings = $stmt->fetchAll();

// Cast types
foreach ($bookings as &$b) {
    $b['id'] = (int)$b['id'];
}

jsonResponse(true, 'Bookings retrieved.', ['bookings' => $bookings]);
