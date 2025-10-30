<?php
require_once __DIR__ . '/db.php';


function teacher_list(): array {
$pdo = db();
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY name");
return $stmt->fetchAll();
}