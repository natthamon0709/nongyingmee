<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/SettingsRepository.php';

require_auth('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin.php?tab=settings');

$csrf = $_POST['csrf'] ?? '';
if (!verify_csrf($csrf)) {
  start_session(); $_SESSION['flash_error'] = 'CSRF ไม่ถูกต้อง';
  redirect('admin.php?tab=settings');
}

$type = $_POST['type'] ?? '';
$name = $_POST['name'] ?? '';
$ok = settings_create($type, $name);

start_session();
$_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'เพิ่มข้อมูลแล้ว' : 'เพิ่มไม่สำเร็จ (อาจซ้ำชื่อ)';
redirect('admin.php?tab=settings');
