<?php
require_once __DIR__ . '/config.php';

function start_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
  }
}

function csrf_token(): string {
  start_session();
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function verify_csrf(string $token): bool {
  start_session();
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function redirect(string $path): never {
  header('Location: ' . $path);
  exit;
}

/** path ปัจจุบัน (เช่น admin.php, index.php) */
function current_path(): string {
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  $basename = basename(parse_url($uri, PHP_URL_PATH) ?? '');
  return $basename ?: 'index.php';
}

/** ส่งผู้ใช้ไปหน้า default ตามบทบาทของตัวเอง (กันลูป: ถ้าหน้าปัจจุบันตรงอยู่แล้วจะไม่ redirect) */
function redirect_home(): never {
  start_session();
  $role = strtolower($_SESSION['user']['role'] ?? '');
  $map = [
    'admin'    => 'admin.php',
    'reporter' => 'reporter.php',
    'teacher'  => 'teacher.php',
  ];
  $target = $map[$role] ?? 'dashboard.php';

  if (current_path() !== $target) {
    redirect($target);
  }
  header('Cache-Control: no-store');
  exit;
}

/** คืนค่าผู้ใช้ที่ล็อกอินอยู่ หรือ null */
function auth_user(): ?array {
  start_session();
  return $_SESSION['user'] ?? null;
}

/**
 * ต้องล็อกอิน และ (ถ้าระบุ $roles) ต้องเป็นหนึ่งในบทบาทที่กำหนด
 * - รับได้ทั้ง string เดียว หรือ array ของบทบาท
 * - คืนค่าข้อมูลผู้ใช้ (array) เพื่อให้หน้าเรียกใช้งานต่อได้
 */
function require_auth(string|array $roles = null): array {
  start_session();

  // ยังไม่ล็อกอิน -> ไปหน้า login
  if (empty($_SESSION['user'])) {
    if (current_path() !== 'index.php') {
      redirect('index.php');
    }
    // ถ้าอยู่บน index (หน้า login) ก็หยุดทันที
    http_response_code(401);
    exit;
  }

  $user = $_SESSION['user'];

  // ถ้ากำหนดบทบาทที่ยอมรับ
  if ($roles) {
    $roles = is_array($roles) ? $roles : [$roles];
    $roles = array_map('strtolower', $roles);
    $current = strtolower($user['role'] ?? '');
    if (!in_array($current, $roles, true)) {
      // ล็อกอินแล้วแต่สิทธิ์ไม่ตรง -> ส่งไปหน้าโฮมตามสิทธิ์
      redirect_home();
    }
  }

  return $user;
}

/** กันผู้ใช้ที่ล็อกอินแล้วไม่ให้เข้าหน้า login/register */
function require_guest(): void {
  start_session();
  if (!empty($_SESSION['user'])) {
    redirect_home(); // ไปหน้าของตนเอง
  }
}
