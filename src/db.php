<?php
require_once __DIR__ . '/config.php';


function db(): PDO {
static $pdo;
if ($pdo) return $pdo;


$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = (int)($_ENV['DB_PORT'] ?? 3306);
$name = $_ENV['DB_NAME'] ?? 'work';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';


$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
$opt = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);
return $pdo;
}