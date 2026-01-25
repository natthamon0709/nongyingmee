<?php
require_once __DIR__ . '/db.php';

function user_list(): array {
  $pdo = db();
  $sql = "SELECT u.id, u.name, u.email, p.name AS position, s.name AS status,
                 u.rating, u.role
          FROM users u
          LEFT JOIN positions p ON u.position_id = p.id
          LEFT JOIN employment_statuses s ON u.status_id = s.id
          ORDER BY u.name ASC";
  return $pdo->query($sql)->fetchAll();
}

function user_create(array $data): bool {
  $pdo = db();
  $stmt = $pdo->prepare("INSERT INTO users (name,email,position_id,status_id,password,role,rating)
                         VALUES (?,?,?,?,?,?,?)");
  $hash = password_hash($data['password'], PASSWORD_BCRYPT);
  return $stmt->execute([
    $data['name'],
    $data['email'],
    $data['position_id'] ?: null,
    $data['status_id'] ?: null,
    $hash,
    $data['role'],
    $data['rating'] ?? 0
  ]);
}

function user_delete(int $id): bool {
  $pdo = db();
  $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
  return $stmt->execute([$id]);
}

function user_find(int $id): ?array {
        $pdo = db();
        $sql = "
            SELECT 
                u.*
            FROM users u
            WHERE u.id = ?
            LIMIT 1
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
}
function user_performance_summary(): array {
  $pdo = db();

  $sql = "
    SELECT 
      u.id,
      u.name,
      u.email,
      u.rating,
      p.name AS position,
      s.name AS status,

      COUNT(t.id) AS total_tasks,
      SUM(CASE WHEN r.review_status = 'approved' THEN 1 ELSE 0 END) AS approved_tasks,
      SUM(CASE WHEN r.review_status = 'rework' THEN 1 ELSE 0 END) AS rework_tasks,
      SUM(CASE WHEN r.review_status = 'waiting' OR r.review_status IS NULL THEN 1 ELSE 0 END) AS waiting_tasks,
      ROUND(AVG(r.score), 2) AS avg_score

    FROM users u
    LEFT JOIN positions p ON u.position_id = p.id
    LEFT JOIN statuses s  ON u.status_id   = s.id
    LEFT JOIN tasks t ON t.assignee_id = u.id
    LEFT JOIN submissions sb ON sb.task_id = t.id
    LEFT JOIN reviews r ON r.submission_id = sb.id

    GROUP BY u.id
    ORDER BY u.name
  ";

  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // คำนวณประสิทธิภาพ %
  foreach ($rows as &$r) {
    if ($r['total_tasks'] > 0) {
      $r['performance'] = round(($r['approved_tasks'] / $r['total_tasks']) * 100);
    } else {
      $r['performance'] = 0;
    }
  }

  return $rows;
}

