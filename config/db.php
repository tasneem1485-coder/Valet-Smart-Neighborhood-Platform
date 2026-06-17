<?php
// Database configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // XAMPP default user
define('DB_PASS', '');           // XAMPP default password (empty)
define('DB_NAME', 'valet_db');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Make sure XAMPP MySQL is running and the database exists.',
            'error'   => $e->getMessage()
        ]);
        exit;
    }

    return $pdo;
}
