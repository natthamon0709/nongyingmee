<?php
// review_save.php
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

// ดึง submission + task ที่เกี่ยวข้อง
$sql = "
  SELECT s.id AS submission_id, s.task_id, s.content, s.sent_at,
         t.id AS task_id, t.status AS task_status, t.department_id, t.title
  FROM task_submissions s
  JOIN tasks t ON t.id = s.task_id
  WHERE s.id = ?
  LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$submissionId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  redirect('admin.php?tab=review&err=not_found');
}

try {
  $pdo->beginTransaction();

  // 1) อัปเดตผลการรีวิวของ submission ล่าสุด
  $upd1 = $pdo->prepare("
    UPDATE task_submissions
    SET review_status = ?, score = ?, reviewer_comment = ?, reviewed_by = ?, reviewed_at = NOW()
    WHERE id = ?
  ");
  $upd1->execute([$reviewStatus, $score, $comment, (string)($user['name'] ?? 'reviewer'), $submissionId]);

  // 2) อัปเดตสถานะรีวิวที่ตัวงาน (ให้รู้สถานะล่าสุดของการตรวจ)
  $upd2 = $pdo->prepare("
    UPDATE tasks
    SET review_status = ?, last_review_submission_id = ?, last_reviewed_at = NOW()
    WHERE id = ?
  ");
  $upd2->execute([$reviewStatus, $submissionId, (int)$row['task_id']]);

  // 3) ถ้าอนุมัติแล้ว -> ปิดงานให้เสร็จสิ้น (done) + ตั้ง flag อนุมัติ
  // echo "Review status: " . $reviewStatus . "\n";exit;
  if ($reviewStatus === 'approved') {
    // echo "Approving task ID " . (int)$row['task_id'] . "\n";exit;
    $upd3 = $pdo->prepare("
      UPDATE tasks
      SET status = 'done',
          approved = 1,
          approved_at = NOW()
      WHERE id = ?
    ");
    $upd3->execute([(int)$row['task_id']]);
  }
  // (ทางเลือก) ถ้าถูกตีกลับ rework แล้วอยากดันกลับเป็นกำลังทำ:
  // else if ($reviewStatus === 'rework') {
  //   $upd3b = $pdo->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?");
  //   $upd3b->execute([(int)$row['task_id']]);
  // }

  $pdo->commit();

  // พากลับหน้า review พร้อมแจ้งสำเร็จ
  redirect('admin.php?tab=review&ok=1');

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // log_error($e); // ถ้ามีตัวช่วย
  redirect('admin.php?tab=review&err=exception');
}
