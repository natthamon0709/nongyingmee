<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/ReviewRepository.php';

require_auth('admin');

$userId = (int)($_GET['user_id'] ?? 0);
header('Content-Type: application/json');

if ($userId <= 0) {
  echo json_encode(['error' => 'invalid user']);
  exit;
}

// ตัวอย่าง logic
$data = review_summary_by_user($userId);
/*
$return = [
  'total_score' => 85,
  'efficiency' => 92,
  'summary' => 'ทำงานได้ดี ส่งงานตรงเวลา มีความสม่ำเสมอ'
];
*/

echo json_encode($data);
