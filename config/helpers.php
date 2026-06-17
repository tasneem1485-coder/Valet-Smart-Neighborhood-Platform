<?php
// Start session if not already started
function ensureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Send a JSON response and exit
function jsonResponse(bool $success, string $message, array $data = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// Validate that required POST fields are present and non-empty
function requireFields(array $fields): array {
    $values = [];
    foreach ($fields as $field) {
        $val = trim($_POST[$field] ?? '');
        if ($val === '') {
            jsonResponse(false, "Field '{$field}' is required.", [], 422);
        }
        $values[$field] = $val;
    }
    return $values;
}

// Sanitize a string for safe output
function clean(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// Get currently logged-in user id from session
function getSessionUserId(): ?int {
    ensureSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// Require login – send 401 if not authenticated
function requireLogin(): int {
    $id = getSessionUserId();
    if ($id === null) {
        jsonResponse(false, 'Not authenticated. Please sign in.', [], 401);
    }
    return $id;
}

// Only allow specific HTTP methods
function allowMethods(array $methods): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        jsonResponse(false, 'Method not allowed.', [], 405);
    }
}

// Set CORS headers (for development with separate frontend)
function setCorsHeaders(): void {
    $allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: {$origin}");
    }
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
