<?php
/**
 * Database Configuration - SSMPAS v2.0
 * Supports both local (WAMP) and production (Railway) environments
 */

// Check for Railway DATABASE_URL first, fallback to local config
if (isset($_ENV['DATABASE_URL'])) {
    $db_url = parse_url($_ENV['DATABASE_URL']);
    $host = $db_url['host'] ?? 'localhost';
    $port = $db_url['port'] ?? '3306';
    $name = ltrim($db_url['path'], '/');
    $user = $db_url['user'] ?? 'root';
    $pass = $db_url['pass'] ?? '';
} else {
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
