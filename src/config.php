<?php
// โหลด .env แบบง่ายโดยไม่ใช้ไลบรารี (อ่านไฟล์ทีละบรรทัด)
$envPath = __DIR__ . '/../.env';
if (is_file($envPath)) {
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
if (str_starts_with(trim($line), '#')) continue;
[$k, $v] = array_map('trim', explode('=', $line, 2));
$_ENV[$k] = $v;
}
}


// ค่าคงที่
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/GovTaskTracker/public');
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'govtask_sess');