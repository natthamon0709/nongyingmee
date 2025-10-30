<?php
require_once __DIR__ . '/config.php';


function db(): PDO {
static $pdo;
if ($pdo) return $pdo;


$host = $_ENV['DB_HOST'] ?? 'lolyz0ok3stvj6f0.cbetxkdyhwsb.us-east-1.rds.amazonaws.com';
$port = (int)($_ENV['DB_PORT'] ?? 3306);
$name = $_ENV['DB_NAME'] ?? 'rx6trpvt26s9ed7v';
$user = $_ENV['DB_USER'] ?? 'dxrj518624i460gr';
$pass = $_ENV['DB_PASS'] ?? 'y73zltt43lm3doc1';


$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
$opt = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);
return $pdo;
}