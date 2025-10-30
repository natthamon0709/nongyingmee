<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/UserRepository.php';

require_auth('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin.php?tab=users');
$csrf = $_POST['csrf'] ?? '';
if (!verify_csrf($csrf)) {
  start_session(); $_SESSION['flash_error'] = 'CSRF ไม่ถูกต้อง';
  redirect('admin.php?tab=users');
}

$data = [
  'name'         => trim($_POST['name'] ?? ''),
  'email'        => trim($_POST['email'] ?? ''),
  'position_id'  => $_POST['position_id'] ?? null,
  'status_id'    => $_POST['status_id'] ?? null,
  'password'     => $_POST['password'] ?? '',
  'role'         => $_POST['role'] ?? 'teacher',
  'rating'       => $_POST['rating'] ?? 0,
];

$ok = $data['name'] && $data['password'] && user_create($data);
start_session();
$_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'เพิ่มผู้ใช้สำเร็จ' : 'เพิ่มผู้ใช้ไม่สำเร็จ';
redirect('admin.php?tab=users');
