<?php
/**
 * Database Configuration - SSMPAS v2.0
 * Supports local (WAMP), Railway, and InfinityFree via environment variables
 */

// Load .env file if exists (for local development)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}

// Priority: Railway (DATABASE_URL) -> InfinityFree/Production env vars -> Local
if (isset($_ENV['DATABASE_URL'])) {
    // Railway provides DATABASE_URL
    $db_url = parse_url($_ENV['DATABASE_URL']);
    $host = $db_url['host'] ?? 'localhost';
    $port = $db_url['port'] ?? '3306';
    $name = ltrim($db_url['path'], '/');
    $user = $db_url['user'] ?? 'root';
    $pass = $db_url['pass'] ?? '';
} elseif (isset($_ENV['DB_HOST'])) {
    // InfinityFree or custom environment variables
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $name = $_ENV['DB_NAME'] ?? 'ssmpas';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
} else {
    // Local WAMP development fallback
    $host = 'localhost';
    $port = '3306';
    $name = 'ssmpas';
    $user = 'root';
    $pass = '';
}

$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}
