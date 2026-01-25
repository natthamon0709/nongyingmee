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
/**
 * สรุปผลคะแนน + ประสิทธิภาพรายบุคคล (Real-time)
 * ใช้ submission ล่าสุดต่อ task
 */
function user_performance_summary(): array {
    $pdo = db();

    // ตรวจว่ามี user_id ใน submissions ไหม
    $hasUserId = column_exists($pdo, 'task_submissions', 'user_id');

    // เลือก key join ให้ตรง schema
    $joinUser = $hasUserId
        ? 's.user_id = u.id'
        : 's.sender_name = u.name';

    $sql = "
        WITH latest AS (
            SELECT task_id, MAX(id) AS last_id
            FROM task_submissions
            GROUP BY task_id
        )
        SELECT
            u.id,
            u.name,
            u.email,
            p.name AS position, e.name AS status,
            u.rating, u.role,

            COUNT(s.id) AS total_tasks,

            SUM(CASE WHEN s.review_status = 'approved' THEN 1 ELSE 0 END) AS approved_tasks,
            SUM(CASE WHEN s.review_status = 'rework' THEN 1 ELSE 0 END) AS rework_tasks,
            SUM(CASE
                WHEN s.review_status = 'waiting' OR s.review_status IS NULL
                THEN 1 ELSE 0 END
            ) AS waiting_tasks,

            ROUND(AVG(s.score), 2) AS avg_score

        FROM users u
        LEFT JOIN task_submissions s ON {$joinUser}
        LEFT JOIN latest l ON l.last_id = s.id
        LEFT JOIN positions p ON u.position_id = p.id
        LEFT JOIN employment_statuses e ON u.status_id = e.id

        GROUP BY u.id
        ORDER BY u.name
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // คำนวณ performance %
    foreach ($rows as &$r) {
        $r['performance'] = $r['total_tasks'] > 0
            ? round(($r['approved_tasks'] / $r['total_tasks']) * 100)
            : 0;
    }

    return $rows;
}



