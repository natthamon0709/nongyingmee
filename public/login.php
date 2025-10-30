<?php
// public/login.php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php'; // มี attempt_login() ที่รองรับ master password แล้ว

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

error_reporting(E_ALL); ini_set('display_errors', '1');

start_session(); // ต้องมาก่อนใช้ $_SESSION

// ---- CSRF ----
$csrf = $_POST['csrf'] ?? '';
if (!verify_csrf($csrf)) {
  $_SESSION['flash_error'] = 'ไม่สามารถยืนยันแบบฟอร์มได้ กรุณาลองใหม่';
  redirect('index.php');
}

// ---- รับและทำความสะอาดค่า ----
$userType  = strtolower(trim($_POST['user_type'] ?? ''));
$password  = (string)($_POST['password'] ?? '');
$teacherId = isset($_POST['teacher_id']) && $_POST['teacher_id'] !== '' ? (int)$_POST['teacher_id'] : null;

// อนุญาตเฉพาะ 3 บทบาทนี้
$allowedRoles = ['admin','reporter','teacher'];
if (!in_array($userType, $allowedRoles, true)) {
  $_SESSION['flash_error'] = 'ประเภทผู้ใช้ไม่ถูกต้อง';
  redirect('index.php');
}

// ถ้าไม่ใช่ครู ให้ล้าง teacherId ทิ้ง (กันเผลอติดมากับฟอร์ม)
if ($userType !== 'teacher') {
  $teacherId = null;
}

// ---- validate ขั้นต้น ----
if ($userType === '') {
  $_SESSION['flash_error'] = 'กรุณาเลือกประเภทผู้ใช้งาน';
  redirect('index.php');
}
if ($password === '') {
  $_SESSION['flash_error'] = 'กรุณากรอกรหัสผ่าน';
  redirect('index.php');
}
if ($userType === 'teacher' && !$teacherId) {
  $_SESSION['flash_error'] = 'กรุณาเลือกชื่อครู';
  redirect('index.php');
}

// ---- พยายามล็อกอิน ----
// attempt_login() จะ:
// - admin    : ผ่านทันทีถ้า password === Admin@12345 (ไม่แตะ DB)
// - reporter : ผ่านทันทีถ้า password === Report@12345 (ไม่แตะ DB)
// - teacher  : ตรวจรหัสผ่านกับ DB
$user = attempt_login($userType, $password, $teacherId);

if ($user) {
  // ปลอดภัยขึ้น: เปลี่ยน session id หลังยืนยันตัวตน
  session_regenerate_id(true);

  // ห้ามเก็บรหัสผ่าน
  unset($user['password']);
  $_SESSION['user'] = $user;

  // map ปลายทางตามบทบาท
  $redirectMap = [
    'admin'    => 'admin.php',
    'reporter' => 'reporter.php',
    'teacher'  => 'teacher.php',
  ];

  // role จากผู้ใช้ (normalize) ถ้าไม่มีใช้ที่เลือกมา
  $role = strtolower($user['role'] ?? $userType);
  $to   = $redirectMap[$role] ?? 'dashboard.php';

  redirect($to);
}

// ---- ล้มเหลว ----
$_SESSION['flash_error'] = $_SESSION['flash_error'] ?? 'เข้าสู่ระบบไม่สำเร็จ ตรวจสอบข้อมูลอีกครั้ง';
redirect('index.php');
