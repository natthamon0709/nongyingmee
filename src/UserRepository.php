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