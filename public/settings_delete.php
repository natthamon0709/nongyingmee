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
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$ok = $id > 0 ? settings_delete($type, $id) : false;

start_session();
$_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'ลบข้อมูลแล้ว' : 'ลบไม่สำเร็จ';
redirect('admin.php?tab=settings');
