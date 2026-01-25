<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/TaskRepository.php';

require_auth('admin');      // ✅ ลบได้เฉพาะ admin
csrf_verify();              // ✅ ตรวจ CSRF token

$taskId = (int)($_POST['task_id'] ?? 0);
if ($taskId <= 0) {
  $_SESSION['flash_error'] = 'ไม่พบงานที่ต้องการลบ';
  redirect('admin.php?tab=review');
}

/*
  ลำดับการลบที่ถูกต้อง:
  1) submissions
  2) attachments
  3) tasks
*/
task_delete_full($taskId);

$_SESSION['flash_success'] = 'ลบงานเรียบร้อยแล้ว';
redirect('admin.php?tab=review');
