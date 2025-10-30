<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_auth('admin');

$id = (int)($_POST['id'] ?? 0);
$csrf = $_POST['csrf'] ?? '';

if (!$id || !verify_csrf($csrf)) {
  start_session(); $_SESSION['flash_error'] = 'ไม่สามารถลบได้';
  redirect('admin.php?tab=users');
}

$ok = user_delete($id);
start_session();
$_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'ลบข้อมูลแล้ว' : 'ลบไม่สำเร็จ';
redirect('admin.php?tab=users');
