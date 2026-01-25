<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/TaskRepository.php';

require_auth('admin');
csrf_verify();

$taskId = (int)($_POST['task_id'] ?? 0);
if ($taskId <= 0) {
  $_SESSION['flash_error'] = 'ไม่พบงานที่ต้องการลบ';
  redirect('admin.php?tab=review');
}

/*
  ควรลบตามลำดับ:
  1) submissions
  2) attachments (ถ้ามี)
  3) tasks
*/
task_delete_full($taskId);

$_SESSION['flash_success'] = 'ลบงานเรียบร้อยแล้ว';
redirect('admin.php?tab=review');
