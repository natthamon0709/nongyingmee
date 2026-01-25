<?php
// src/TaskRepository.php
require_once __DIR__ . '/db.php';

/**
 * Utilities: ตรวจว่ามีตาราง/คอลัมน์ไหม
 */
function table_exists(PDO $pdo, string $table): bool {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$table]);
    return (bool)$st->fetchColumn();
}
function column_exists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$table, $column]);
    return (bool)$st->fetchColumn();
}

/**
 * ดึงรายการงานแบบเดิม (อ่าน attachments เป็น array)
 */
function tasks_list(?int $departmentId = null, ?string $status = null): array {
  $pdo = db();
  $w = [];
  $p = [];

  if ($departmentId) { $w[] = 't.department_id = ?'; $p[] = $departmentId; }
  if ($status)       { $w[] = 't.status = ?';        $p[] = $status; }

  // เลือกชื่อฝ่ายจาก settings หรือ departments ตามที่มีอยู่
  $join = '';
  $deptNameExpr = 'NULL AS department_name';
  if (table_exists($pdo, 'settings') && column_exists($pdo, 'settings', 'name')) {
      $join = "LEFT JOIN settings d ON t.department_id = d.id".(column_exists($pdo,'settings','type')?" AND d.type='department'":"");
      $deptNameExpr = 'd.name AS department_name';
  } elseif (table_exists($pdo, 'departments') && column_exists($pdo, 'departments','name')) {
      $join = "LEFT JOIN departments d ON t.department_id = d.id";
      $deptNameExpr = 'd.name AS department_name';
  }

  $where = $w ? ('WHERE '.implode(' AND ',$w)) : '';
  $sql = "
    SELECT t.id, t.title, t.doc_type, t.code_no, t.assignee_name,
           t.created_at, t.due_date, t.status, t.attachments,s.review_status AS review_status,
           $deptNameExpr
    FROM tasks t
    $join
    LEFT JOIN task_submissions s ON s.task_id = t.id
    $where
    ORDER BY t.created_at DESC, t.id DESC
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($p);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // แปลง JSON attachments เป็น array
  foreach ($rows as &$r) {
    if (!empty($r['attachments'])) {
      $a = json_decode($r['attachments'], true);
      $r['attachments'] = is_array($a) ? $a : [];
    } else {
      $r['attachments'] = [];
    }
  }
  unset($r);
  return $rows;
}

/**
 * รายการสำหรับหน้า reviewer (คงเดิม)
 */
// ตัวอย่างแนวทางภายใน TaskRepository.php
function tasks_review_list(?string $rev = null, ?int $dept = null): array {
    $pdo = db();

    // ===== (1) รับพารามิเตอร์ filter เสริมจาก GET (ถ้ามี) =====
    $q         = isset($_GET['q'])      ? trim((string)$_GET['q']) : null;         // คีย์เวิร์ด
    $startDate = isset($_GET['start'])  ? trim((string)$_GET['start']) : null;     // YYYY-MM-DD
    $endDate   = isset($_GET['end'])    ? trim((string)$_GET['end'])   : null;     // YYYY-MM-DD
    $hasSub    = isset($_GET['has_sub'])? trim((string)$_GET['has_sub']) : null;   // 'yes' | 'no'
    $page      = max(1, (int)($_GET['page'] ?? 1));
    $limit     = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset    = ($page - 1) * $limit;

    // ===== (2) ตรวจตารางฝ่าย (กันพัง) =====
    $tblExists = function(string $t) use ($pdo): bool {
        $q = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
        $q->execute([$t]);
        return (bool)$q->fetchColumn();
    };
    $colExists = function(string $t, string $c) use ($pdo): bool {
        $q = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
        $q->execute([$t, $c]);
        return (bool)$q->fetchColumn();
    };

    $deptJoin  = "";
    $deptField = "NULL AS department_name";
    if ($tblExists('settings') && $colExists('settings','type') && $colExists('settings','name')) {
        $deptJoin  = "LEFT JOIN settings d ON d.id = t.department_id AND d.type = 'department'";
        $deptField = "d.name AS department_name";
    } elseif ($tblExists('departments') && $colExists('departments','name')) {
        $deptJoin  = "LEFT JOIN departments d ON d.id = t.department_id";
        $deptField = "d.name AS department_name";
    }

    // ===== (3) WHERE เงื่อนไขกรอง =====
    $where  = [];
    $params = [];

    if ($dept) {
        $where[]  = 't.department_id = ?';
        $params[] = $dept;
    }

    // ให้ NULL นับเป็น 'waiting'
    if ($rev) {
        if ($rev === 'waiting') {
            $where[] = "COALESCE(s2.review_status, 'waiting') = 'waiting'";
        } elseif ($rev === 'approved') {
            // รวมงานที่ปิดแล้วหรือกดอนุมัติไว้ด้วย
            $where[] = "(s2.review_status = 'approved' OR t.status = 'done')";
        } else {
            $where[]  = "s2.review_status = ?";
            $params[] = $rev; // 'rework' เป็นต้น
        }
    }

    // คีย์เวิร์ด: title / code_no / doc_type / assignee_name
    if (!empty($q)) {
        $where[]  = "(t.title LIKE ? OR t.code_no LIKE ? OR t.doc_type LIKE ? OR t.assignee_name LIKE ?)";
        $like = "%$q%";
        array_push($params, $like, $like, $like, $like);
    }

    // วันที่ (อิง created_at) — ใส่แค่ไหนกรองแค่นั้น
    if (!empty($startDate)) {
        $where[]  = "DATE(t.created_at) >= ?";
        $params[] = $startDate;
    }
    if (!empty($endDate)) {
        $where[]  = "DATE(t.created_at) <= ?";
        $params[] = $endDate;
    }

    // มี/ไม่มีการส่งงานล่าสุด
    if ($hasSub === 'yes') {
        $where[] = "ls.latest_id IS NOT NULL";
    } elseif ($hasSub === 'no') {
        $where[] = "ls.latest_id IS NULL";
    }

    // ===== (4) เลือก submission ล่าสุด (MAX(id)) รองรับ MySQL/MariaDB =====
    $sql = "
        SELECT
            t.id, t.title, t.doc_type, t.code_no, t.department_id,
            COALESCE(s2.review_status, 'waiting') AS review_status,
            t.status, t.assignee_name, t.created_at, t.due_date,
            $deptField,
            s2.id AS submission_id, s2.sender_name, s2.content, s2.sent_at,
            s2.score, s2.reviewer_comment,
            t.attachments AS task_attachments   -- << เพิ่ม
        FROM tasks t
        LEFT JOIN (
            SELECT task_id, MAX(id) AS latest_id
            FROM task_submissions
            GROUP BY task_id
        ) ls ON ls.task_id = t.id
        LEFT JOIN task_submissions s2 ON s2.id = ls.latest_id
        $deptJoin
        " . ($where ? ('WHERE ' . implode(' AND ', $where)) : '') . "
        ORDER BY  t.id DESC
        LIMIT $limit OFFSET $offset
    ";


    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}





/**
 * ปรับสถานะรีวิว submission (คงเดิม)
 */
function submission_update_review(int $submissionId, string $reviewStatus, ?int $score, ?string $comment): bool {
  $pdo = db();
  $stmt = $pdo->prepare("
    UPDATE task_submissions
    SET review_status = ?, score = ?, reviewer_comment = ?, reviewed_at = NOW()
    WHERE id = ?
  ");
  return $stmt->execute([$reviewStatus, $score, $comment, $submissionId]);
}

/**
 * สรุปภาพรวม (คงเดิม)
 */
function task_summary_overall(): array {
  $pdo = db();
  $sql = "
    WITH latest AS (
      SELECT task_id, MAX(id) AS last_id
      FROM task_submissions
      GROUP BY task_id
    )
    SELECT
      COUNT(t.id) AS total,
      SUM(CASE WHEN ts.review_status = 'approved' THEN 1 ELSE 0 END) AS done,
      SUM(CASE
            WHEN l.last_id IS NOT NULL
             AND ts.review_status IN ('waiting','rework')
             AND (t.due_date IS NULL OR t.due_date >= CURDATE())
           THEN 1 ELSE 0 END) AS in_progress,
      SUM(CASE
            WHEN l.last_id IS NOT NULL
             AND ts.review_status IN ('waiting','rework')
             AND (t.due_date IS NOT NULL AND t.due_date < CURDATE())
           THEN 1 ELSE 0 END) AS overdue
    FROM tasks t
    LEFT JOIN latest l  ON l.task_id = t.id
    LEFT JOIN task_submissions ts ON ts.id = l.last_id
  ";
  $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
  return [
    'total'       => (int)($row['total'] ?? 0),
    'done'        => (int)($row['done'] ?? 0),
    'in_progress' => (int)($row['in_progress'] ?? 0),
    'overdue'     => (int)($row['overdue'] ?? 0),
  ];
}

/**
 * สรุปตามฝ่ายงาน (คงเดิม)
 */
function task_summary_by_department(): array {
  $pdo = db();
  $sql = "
    WITH latest AS (
      SELECT task_id, MAX(id) AS last_id
      FROM task_submissions
      GROUP BY task_id
    )
    SELECT
      d.id, d.name,
      COUNT(t.id) AS total,
      SUM(CASE WHEN ts.review_status = 'approved' THEN 1 ELSE 0 END) AS done,
      SUM(CASE
            WHEN l.last_id IS NOT NULL
             AND ts.review_status IN ('waiting','rework')
             AND (t.due_date IS NULL OR t.due_date >= CURDATE())
          THEN 1 ELSE 0 END) AS in_progress,
      SUM(CASE
            WHEN l.last_id IS NOT NULL
             AND ts.review_status IN ('waiting','rework')
             AND (t.due_date IS NOT NULL AND t.due_date < CURDATE())
          THEN 1 ELSE 0 END) AS overdue
    FROM departments d
    LEFT JOIN tasks t           ON t.department_id = d.id
    LEFT JOIN latest l          ON l.task_id       = t.id
    LEFT JOIN task_submissions ts ON ts.id         = l.last_id
    GROUP BY d.id, d.name
    ORDER BY d.name
  ";
  return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * >>> INSERT แบบไดนามิก: มีคอลัมน์ไหน ค่อยใส่คอลัมน์นั้น <<<
 * - ถ้ามี `assignees` จะเก็บ JSON IDs
 * - ถ้าไม่มี `assignees` แต่มี `assignee_name` จะ map IDs -> ชื่อ แล้วเก็บชื่อรวมเป็นสตริง
 * - attachments เก็บ JSON ของ URL รูป
 */
function task_create(array $data): int {
    $pdo = db();

    // ตรวจ schema ที่มีจริง
    $hasAssignees     = column_exists($pdo, 'tasks', 'assignees');
    $hasAssigneeName  = column_exists($pdo, 'tasks', 'assignee_name');
    $hasSentDate      = column_exists($pdo, 'tasks', 'sent_date');
    $hasDueText       = column_exists($pdo, 'tasks', 'due_text');
    $hasDueDate       = column_exists($pdo, 'tasks', 'due_date');
    $hasAttachments   = column_exists($pdo, 'tasks', 'attachments');
    $hasCreatedBy     = column_exists($pdo, 'tasks', 'created_by');
    $hasStatus        = column_exists($pdo, 'tasks', 'status');
    $hasCreatedAt     = column_exists($pdo, 'tasks', 'created_at');

    // เตรียมค่า
    $attachmentsJson = $hasAttachments ? json_encode($data['attachments'] ?? []) : null;
    $assigneesArr    = is_array($data['assignees'] ?? null) ? array_values(array_filter($data['assignees'], 'is_numeric')) : [];

    // ถ้าต้อง fallback เป็นชื่อ
    $assigneeNameStr = null;
    if (!$hasAssignees && $hasAssigneeName) {
        // map id -> name จากตาราง users (ถ้ามี)
        $names = [];
        if (table_exists($pdo, 'users') && column_exists($pdo,'users','id') && column_exists($pdo,'users','name') && $assigneesArr) {
            $in  = implode(',', array_fill(0, count($assigneesArr), '?'));
            $st  = $pdo->prepare("SELECT name FROM users WHERE id IN ($in)");
            $st->execute($assigneesArr);
            $names = $st->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($assigneesArr) {
            // ถ้าไม่มี users table ก็เก็บเป็นรหัส id string
            $names = array_map(fn($x)=> (string)$x, $assigneesArr);
        }
        $assigneeNameStr = $names ? implode(', ', $names) : null;
    }

    // สร้าง SQL insert แบบไดนามิก
    $cols = ['doc_type','code_no','title','department_id'];
    $params = [
        ':doc_type'      => $data['doc_type'] ?? '',
        ':code_no'       => $data['code_no'] ?? '',
        ':title'         => $data['title'] ?? '',
        ':department_id' => (int)($data['department_id'] ?? 0),
    ];

    if ($hasAssignees) {
        $cols[] = 'assignees';
        $params[':assignees'] = json_encode($assigneesArr);
    } elseif ($hasAssigneeName) {
        $cols[] = 'assignee_name';
        $params[':assignee_name'] = $assigneeNameStr;
    }

    if ($hasSentDate)    { $cols[]='sent_date';    $params[':sent_date']    = $data['sent_date'] ?? null; }
    if ($hasDueText)     { $cols[]='due_text';     $params[':due_text']     = $data['due_text'] ?? ''; }
    if ($hasDueDate)     { $cols[]='due_date';     $params[':due_date']     = $data['due_date'] ?? null; }
    if ($hasAttachments) { $cols[]='attachments';  $params[':attachments']  = $attachmentsJson; }
    if ($hasCreatedBy)   { $cols[]='created_by';   $params[':created_by']   = $data['created_by'] ?? null; }
    if ($hasStatus)      { $cols[]='status';       $params[':status']       = $data['status'] ?? 'pending'; }
    if ($hasCreatedAt)   { $cols[]='created_at';   $params[':created_at']   = date('Y-m-d H:i:s'); }

    $placeholders = array_map(fn($c)=>':'.$c, $cols);
    // map คีย์พารามิเตอร์ให้ตรง (กรณี created_at/status ถูกเพิ่มด้วยชื่อ :created_at / :status)
    foreach ($cols as $c) {
        $ph = ':'.$c;
        if (!array_key_exists($ph, $params)) {
            // already bound by an alternate key (like :doc_type) or not needed
            if ($c === 'created_at') $params[$ph] = date('Y-m-d H:i:s');
            if ($c === 'status' && !isset($params[$ph])) $params[$ph] = 'pending';
        }
    }

    $sql = "INSERT INTO tasks (".implode(',', $cols).") VALUES (".implode(',', $placeholders).")";
    $st  = $pdo->prepare($sql);
    $st->execute($params);

    return (int)$pdo->lastInsertId();
}

/**
 * ดึงรายการงานทั้งหมด (รองรับทั้งสคีมาแบบมี/ไม่มี assignees)
 */
function tasks_list2(?int $department_id = null, ?string $status = null): array {
    $pdo = db();

    /* เลือก JOIN แหล่งชื่อฝ่ายงานแบบไดนามิก */
    $join = '';
    $deptNameExpr = 'NULL AS department_name';

    if (table_exists($pdo, 'settings')) {
        if (column_exists($pdo, 'settings', 'type')) {
            $join = "LEFT JOIN settings d ON t.department_id = d.id AND d.type = 'department'";
        } else {
            $join = "LEFT JOIN settings d ON t.department_id = d.id";
        }
        $deptNameExpr = 'd.name AS department_name';
    } elseif (table_exists($pdo, 'departments')) {
        $join = "LEFT JOIN departments d ON t.department_id = d.id";
        $deptNameExpr = 'd.name AS department_name';
    }

    /* เงื่อนไขกรอง */
    $where = [];
    $params = [];

    if (!empty($department_id)) { $where[] = 't.department_id = ?'; $params[] = $department_id; }
    if (!empty($status))        { $where[] = 't.status = ?';        $params[] = $status; }

    $sql = "
        SELECT 
            t.*,
            {$deptNameExpr}
        FROM tasks t
        {$join}
        " . ($where ? ('WHERE ' . implode(' AND ', $where)) : '') . "
        ORDER BY t.created_at DESC, t.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* เตรียม map id=>name ของผู้ใช้ (สำหรับโชว์ชื่อผู้รับผิดชอบ) */
    $userMap = [];
    if (table_exists($pdo, 'users') && column_exists($pdo, 'users', 'id') && column_exists($pdo, 'users', 'name')) {
        $userMap = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /* แปลง JSON + เติมชื่อผู้รับผิดชอบ (assignee_name) อย่างระวัง */
    foreach ($rows as &$r) {
        // attachments
        if (isset($r['attachments']) && $r['attachments'] !== null && $r['attachments'] !== '') {
            $attachments = json_decode($r['attachments'], true);
            $r['attachments'] = is_array($attachments) ? $attachments : [];
        } else {
            $r['attachments'] = [];
        }

        // ถ้ามีคอลัมน์ assignees จริง จึงค่อย decode/คำนวณชื่อ
        if (array_key_exists('assignees', $r) && $r['assignees'] !== null && $r['assignees'] !== '') {
            $assignees = json_decode($r['assignees'], true);
            $assignees = is_array($assignees) ? $assignees : [];
            $r['assignees'] = $assignees;

            // คำนวณชื่อถ้าทำได้
            if ($assignees && $userMap) {
                $names = [];
                foreach ($assignees as $uid) {
                    if (isset($userMap[$uid])) $names[] = $userMap[$uid];
                }
                // ถ้าตารางมี assignee_name อยู่แล้ว ไม่ทับค่าถ้าเดิมมีค่า
                if (empty($r['assignee_name'])) {
                    $r['assignee_name'] = $names ? implode(', ', $names) : null;
                }
            }
        } else {
            // ไม่มีคอลัมน์ assignees → คงค่าเดิมของ assignee_name ตามสคีมาเก่า
            $r['assignees'] = [];
            // $r['assignee_name'] ใช้ตามที่ SELECT มาจากตาราง
        }
    }
    unset($r);

    return $rows;
}

function task_delete_full(int $taskId): void {
  $db = db();

  // ลบการส่งงาน
  $stmt = $db->prepare("DELETE FROM submissions WHERE task_id = ?");
  $stmt->execute([$taskId]);

  // ลบงานหลัก
  $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
  $stmt->execute([$taskId]);
}
