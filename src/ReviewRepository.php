<?php
// src/TaskRepository.php
require_once __DIR__ . '/db.php';

function review_summary_by_user(int $userId): array {
  // mock ข้อมูล (เอาไปผูก DB จริงได้)
  return [
    'total_score' => rand(60, 100),
    'efficiency' => rand(70, 100),
    'summary' => 'ประสิทธิภาพการทำงานดี มีความรับผิดชอบ และส่งงานตรงเวลา'
  ];
}