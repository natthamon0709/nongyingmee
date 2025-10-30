<?php
// public/user_update.php
declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';        // csrf_token(), verify_csrf(), require_auth(), redirect()
require_once __DIR__ . '/../src/db.php';

require_auth('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php?tab=users');
}

try {
    verify_csrf($_POST['csrf'] ?? '');
} catch (Throwable $e) {
    $_SESSION['flash_error'] = 'CSRF ไม่ถูกต้อง';
    redirect('admin.php?tab=users');
}

$id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name        = trim((string)($_POST['name'] ?? ''));
$email       = trim((string)($_POST['email'] ?? ''));
$position_id = $_POST['position_id'] !== '' ? (int)$_POST['position_id'] : null;
$status_id   = $_POST['status_id']   !== '' ? (int)$_POST['status_id']   : null;
$rating      = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$role        = trim((string)($_POST['role'] ?? ''));

if ($id <= 0) {
    $_SESSION['flash_error'] = 'ไม่พบผู้ใช้ที่ต้องการแก้ไข';
    redirect('admin.php?tab=users');
}
if ($name === '') {
    $_SESSION['flash_error'] = 'กรุณากรอกชื่อ-นามสกุล';
    redirect('admin.php?tab=users&id=' . $id);
}

$pdo = db();

// อัปเดต (ไม่ยุ่ง password ตรงนี้)
$sql = "
    UPDATE users
    SET name = :name,
        email = :email,
        position_id = :position_id,
        status_id = :status_id,
        rating = :rating,
        role = :role,
        updated_at = NOW()
    WHERE id = :id
";
$st = $pdo->prepare($sql);
$ok = $st->execute([
    ':name'        => $name,
    ':email'       => ($email !== '' ? $email : null),
    ':position_id' => $position_id,
    ':status_id'   => $status_id,
    ':rating'      => $rating,
    ':role'        => ($role !== '' ? $role : 'teacher'),
    ':id'          => $id,
]);

if ($ok) {
    $_SESSION['flash_success'] = 'บันทึกการแก้ไขผู้ใช้เรียบร้อย';
    redirect('admin.php?tab=users');
} else {
    $_SESSION['flash_error'] = 'ไม่สามารถบันทึกการแก้ไขได้';
    redirect('admin.php?tab=users&id=' . $id);
}
