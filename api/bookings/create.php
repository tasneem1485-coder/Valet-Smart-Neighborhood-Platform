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

$fullName      = trim($_POST['full_name'] ?? '');
$email         = trim($_POST['email'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$serviceType   = trim($_POST['service_type'] ?? '');
$preferredDate = trim($_POST['preferred_date'] ?? '');
$preferredTime = trim($_POST['preferred_time'] ?? '');
$address       = trim($_POST['address'] ?? '');
$notes         = trim($_POST['notes'] ?? '');

// Validate required fields
if (empty($fullName) || empty($email) || empty($phone) || empty($serviceType) || empty($preferredDate) || empty($preferredTime) || empty($address)) {
    jsonResponse(false, 'Required fields: full_name, email, phone, service_type, preferred_date, preferred_time, address.', [], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.', [], 422);
}

// Validate service type
$validServices = ['plumbing', 'electrical', 'cleaning', 'tutoring', 'babysitting', 'painting', 'carwash', 'cooking'];
if (!in_array($serviceType, $validServices, true)) {
    jsonResponse(false, 'Invalid service type.', [], 422);
}

// Validate date format (YYYY-MM-DD)
$dateObj = DateTime::createFromFormat('Y-m-d', $preferredDate);
if (!$dateObj || $dateObj->format('Y-m-d') !== $preferredDate) {
    jsonResponse(false, 'Invalid date format. Use YYYY-MM-DD.', [], 422);
}

// Do not allow past dates
if ($dateObj < new DateTime('today')) {
    jsonResponse(false, 'Preferred date cannot be in the past.', [], 422);
}

// Validate time format (HH:MM or HH:MM:SS)
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $preferredTime)) {
    jsonResponse(false, 'Invalid time format. Use HH:MM.', [], 422);
}

$userId = getSessionUserId(); // May be null for guests

$db   = getDBConnection();
$stmt = $db->prepare(
    'INSERT INTO bookings (user_id, full_name, email, phone, service_type, preferred_date, preferred_time, address, notes)
     VALUES (:user_id, :full_name, :email, :phone, :service_type, :preferred_date, :preferred_time, :address, :notes)'
);
$stmt->execute([
    ':user_id'        => $userId,
    ':full_name'      => $fullName,
    ':email'          => $email,
    ':phone'          => $phone,
    ':service_type'   => $serviceType,
    ':preferred_date' => $preferredDate,
    ':preferred_time' => $preferredTime,
    ':address'        => $address,
    ':notes'          => $notes ?: null,
]);

$bookingId = (int)$db->lastInsertId();

jsonResponse(true, 'Your booking has been received! We will confirm within 30 minutes.', [
    'booking' => [
        'id'             => $bookingId,
        'service_type'   => $serviceType,
        'preferred_date' => $preferredDate,
        'preferred_time' => $preferredTime,
        'status'         => 'pending',
    ]
], 201);
