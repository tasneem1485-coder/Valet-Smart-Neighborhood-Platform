<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();
allowMethods(['POST']);

$userId    = requireLogin();
$bookingId = (int)($_POST['booking_id'] ?? 0);

if ($bookingId <= 0) {
    jsonResponse(false, 'booking_id is required.', [], 422);
}

$db   = getDBConnection();

// Ensure the booking belongs to this user
$stmt = $db->prepare('SELECT id, status FROM bookings WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $bookingId, ':user_id' => $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    jsonResponse(false, 'Booking not found.', [], 404);
}

if (in_array($booking['status'], ['completed', 'cancelled'], true)) {
    jsonResponse(false, 'This booking cannot be cancelled.', [], 409);
}

$stmt = $db->prepare('UPDATE bookings SET status = "cancelled" WHERE id = :id');
$stmt->execute([':id' => $bookingId]);

jsonResponse(true, 'Booking cancelled successfully.');
