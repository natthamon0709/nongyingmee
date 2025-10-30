<?php
// review_save.php — บันทึกผลตรวจ และถ้าอนุมัติให้อัปเดตสถานะงานด้วย (เวอร์ชันกันพังตามสคีมาจริง)

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';     // db(), require_auth(), verify_csrf(), redirect()
require_once __DIR__ . '/../src/TaskRepository.php';

$user = require_auth(['teacher','admin']); // ให้สิทธิ์เฉพาะผู้ตรวจ
verify_csrf($_POST['csrf'] ?? '');         // กัน CSRF

$submissionId  = (int)($_POST['submission_id'] ?? 0);
$reviewStatus  = trim((string)($_POST['review_status'] ?? ''));
$score         = (int)($_POST['score'] ?? 0);
$comment       = trim((string)($_POST['comment'] ?? ''));

// validate เบื้องต้น
$allowed = ['waiting','approved','rework'];
if ($submissionId <= 0 || !in_array($reviewStatus, $allowed, true)) {
  redirect('admin.php?tab=review&err=bad_input');
}
if ($score < 1 || $score > 10) {
  $score = 5; // default กลางๆ
}

$pdo = db();
// เปิดโหมดโยน exception จะได้เห็นสาเหตุจริงหากพลาด
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/** util: มีคอลัมน์นี้ไหม */
$colExists = function(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare("
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    LIMIT 1
  ");
  $q->execute([$table, $col]);
  return (bool)$q->fetchColumn();
};

// ดึง submission + task ที่เกี่ยวข้อง (เพื่อรู้ task_id)
$sql = "
  SELECT s.id       AS submission_id,
         s.task_id  AS task_id
  FROM task_submissions s
  WHERE s.id = ?
  LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$submissionId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  redirect('admin.php?tab=review&err=not_found');
}

$taskId = (int)$row['task_id'];

// mapping สถานะของตาราง tasks ให้สัมพันธ์กับผลตรวจ
$taskUpdatesByReview = [
  'waiting'  => ['review_status' => 'waiting',  'status' => 'in_progress', 'approved_at' => null],
  'rework'   => ['review_status' => 'rework',   'status' => 'in_progress', 'approved_at' => null],
  'approved' => ['review_status' => 'approved', 'status' => 'done',        'approved_at' => 'NOW()'],
];

// เตรียมฟิลด์ที่จะอัปเดตแบบ dynamic ให้ “ตรงกับคอลัมน์ที่มีอยู่จริง”
$subTable   = 'task_submissions';
$taskTable  = 'tasks';

$subSet   = [];
$subParam = [];

// ฝั่ง task_submissions
if ($colExists($pdo, $subTable, 'review_status')) {
  $subSet[] = 'review_status = ?';
  $subParam[] = $reviewStatus;
}
if ($colExists($pdo, $subTable, 'score')) {
  $subSet[] = 'score = ?';
  $subParam[] = $score;
}
if ($colExists($pdo, $subTable, 'reviewer_comment')) {
  $subSet[] = 'reviewer_comment = ?';
  $subParam[] = $comment;
}
if ($colExists($pdo, $subTable, 'reviewer_id')) {
  $subSet[] = 'reviewer_id = ?';
  $subParam[] = (int)$user['id'];
}
// reviewed_at ถ้ามีให้ใช้ NOW()
if ($colExists($pdo, $subTable, 'reviewed_at')) {
  $subSet[] = 'reviewed_at = NOW()';
}

// กันกรณีไม่มีคอลัมน์ใดๆ ให้แก้ (จะถือว่าระบบยังไม่รองรับรีวิว)
if (empty($subSet)) {
  $_SESSION['flash_error'] = 'ไม่พบคอลัมน์สำหรับบันทึกผลตรวจในตาราง task_submissions';
  redirect('admin.php?tab=review&err=no_sub_cols');
}

try {
  $pdo->beginTransaction();

  // 1) อัปเดตผลตรวจใน task_submissions
  $sqlSub = "UPDATE {$subTable} SET " . implode(', ', $subSet) . " WHERE id = ? LIMIT 1";
  $subParam[] = $submissionId;
  $upd1 = $pdo->prepare($sqlSub);
  $upd1->execute($subParam);

  // 2) สะท้อนสถานะไปที่ตาราง tasks (ตามคอลัมน์ที่มี)
  $m = $taskUpdatesByReview[$reviewStatus];

  $taskSet   = [];
  $taskParam = [];

  if ($colExists($pdo, $taskTable, 'review_status')) {
    $taskSet[]   = 'review_status = ?';
    $taskParam[] = $m['review_status'];
  }
  // คอลัมน์สถานะหลักของงาน: ปกติใช้ชื่อ status (หากของบิวใช้ชื่ออื่น เช่น state ให้สร้างคอลัมน์นั้นแทน)
  if ($colExists($pdo, $taskTable, 'status')) {
    $taskSet[]   = 'status = ?';
    $taskParam[] = $m['status'];
  }
  // approved_at
  if ($colExists($pdo, $taskTable, 'approved_at')) {
    if ($m['approved_at'] === 'NOW()') {
      $taskSet[] = 'approved_at = NOW()';
    } else {
      $taskSet[] = 'approved_at = NULL';
    }
  }
  // reviewer_id
  if ($colExists($pdo, $taskTable, 'reviewer_id')) {
    $taskSet[]   = 'reviewer_id = ?';
    $taskParam[] = (int)$user['id'];
  }
  // last_submission_id
  if ($colExists($pdo, $taskTable, 'last_submission_id')) {
    $taskSet[]   = 'last_submission_id = ?';
    $taskParam[] = $submissionId;
  }
  // updated_at
  if ($colExists($pdo, $taskTable, 'updated_at')) {
    $taskSet[] = 'updated_at = NOW()';
  }

  if (!empty($taskSet)) {
    $sqlTask = "UPDATE {$taskTable} SET " . implode(', ', $taskSet) . " WHERE id = ? LIMIT 1";
    $taskParam[] = $taskId;
    $upd2 = $pdo->prepare($sqlTask);
    $upd2->execute($taskParam);
  }
  // ถ้าไม่มีคอลัมน์ใน tasks ให้แก้เลย ก็ไม่เป็นไร — ถือว่าอัปเดตเฉพาะผลตรวจ

  $pdo->commit();

  $_SESSION['flash_success'] = 'บันทึกผลตรวจเรียบร้อย';
  redirect('admin.php?tab=review');
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // error_log($e->getMessage());
  $_SESSION['flash_error'] = 'บันทึกผลตรวจไม่สำเร็จ: ' . $e->getMessage();
  redirect('admin.php?tab=review&err=tx_failed');
}
