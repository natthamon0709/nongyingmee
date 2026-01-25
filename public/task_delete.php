<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/TaskRepository.php';

// require_auth('admin');

$taskId = (int)($_POST['task_id'] ?? 0);
$csrf   = $_POST['csrf'] ?? '';

if (!$taskId || !verify_csrf($csrf)) {
  start_session();
  $_SESSION['flash_error'] = 'ไม่สามารถลบงานได้';
  redirect('admin.php?tab=review');
}

// ลบงาน + submissions
task_delete_full($taskId);

start_session();
$_SESSION['flash_success'] = 'ลบงานเรียบร้อยแล้ว';
redirect('admin.php?tab=review');
