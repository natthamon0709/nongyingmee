<?php
require_once __DIR__ . '/db.php';

const SETTINGS_TABLES = [
  'position'  => 'positions',
  'rank'      => 'ranks',
  'status'    => 'employment_statuses',
  'department'=> 'departments',
];

function settings_list(string $type): array {
  $tbl = SETTINGS_TABLES[$type] ?? null;
  if (!$tbl) return [];
  $pdo = db();
  $stmt = $pdo->query("SELECT id, name FROM {$tbl} WHERE is_active=1 ORDER BY sort_order, name");
  return $stmt->fetchAll();
}

function settings_create(string $type, string $name): bool {
  $tbl = SETTINGS_TABLES[$type] ?? null;
  if (!$tbl) return false;
  $name = trim($name);
  if ($name === '') return false;
  $pdo = db();
  $stmt = $pdo->prepare("INSERT INTO {$tbl}(name, sort_order) VALUES (?, 0)");
  try { return $stmt->execute([$name]); } catch (\PDOException $e) { return false; }
}

function settings_delete(string $type, int $id): bool {
  $tbl = SETTINGS_TABLES[$type] ?? null;
  if (!$tbl) return false;
  $pdo = db();
  $stmt = $pdo->prepare("DELETE FROM {$tbl} WHERE id = ?");
  try { return $stmt->execute([$id]); } catch (\PDOException $e) { return false; }
}
