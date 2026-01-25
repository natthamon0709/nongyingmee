<?php
// ===== teacher.php =====
// หน้าครูสำหรับดูรายละเอียดงาน + ส่งงาน (เวอร์ชันสวยขึ้น)

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../src/helpers.php';       // csrf_token(), require_auth(), verify_csrf()
require_once __DIR__ . '/../src/TaskRepository.php';// tasks_list2()
require_once __DIR__ . '/../src/db.php';            // db()

// --- สิทธิ์ ---
$user = require_auth(['teacher','admin']);

$pdo = db();

// ---------- Utilities ตรวจสคีมา ----------
function tbl_exists(PDO $pdo, string $t): bool {
  $q = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
  $q->execute([$t]);
  return (bool)$q->fetchColumn();
}
function col_exists(PDO $pdo, string $t, string $c): bool {
  $q = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
  $q->execute([$t,$c]);
  return (bool)$q->fetchColumn();
}

// ---------- Helpers: ถอดไฟล์แนบ + เรนเดอร์ชิป (แบบเดียวกับ All/Review) ----------
$decodeAttachments = function($raw) {
  if ($raw === null) return [];

  // ถ้าเป็น array อยู่แล้ว → ส่งกลับทันที
  if (is_array($raw)) return $raw;

  // ถ้าเป็น object → แปลงเป็น array
  if (is_object($raw)) return (array)$raw;

  // ปกติคาดหวังเป็น string
  $raw = (string)$raw;
  if ($raw === '') return [];

  // ลอง parse JSON string
  $data = json_decode($raw, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    if (is_string($data)) return [$data];
    if (is_array($data))  return $data;
  }

  // ไม่ใช่ JSON → อาจเป็น URL เดี่ยวหรือหลายบรรทัด
  if (filter_var($raw, FILTER_VALIDATE_URL)) return [$raw];
  $parts = preg_split("/[\r\n,]+/", $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
  return array_values(array_filter($parts, fn($u) => filter_var($u, FILTER_VALIDATE_URL)));
};


$renderFileChip = function($f) {
  if (is_string($f)) {
    $url = $f;
    $label = basename(parse_url($url, PHP_URL_PATH)) ?: 'เปิดไฟล์';
  } else {
    $url   = $f['url'] ?? ($f['path'] ?? '');
    $label = $f['name'] ?? ($f['original_name'] ?? basename((string)$url));
  }
  if (!$url) return '';
  $safeUrl   = htmlspecialchars($url, ENT_QUOTES);
  $safeLabel = htmlspecialchars($label ?: 'เปิดไฟล์', ENT_QUOTES);
  return '<a href="'.$safeUrl.'" target="_blank" rel="noopener"
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-sm hover:bg-slate-50
                   border-slate-200 text-slate-700">
            📎 <span class="truncate max-w-[18ch]">'.$safeLabel.'</span>
          </a>';
};

// ---------- รับพารามิเตอร์ ----------
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ---------- แผนที่สถานะ ----------
$statusMeta = [
  'pending'     => ['label'=>'รอดำเนินการ', 'cls'=>'bg-amber-100 text-amber-700 ring-1 ring-amber-200/60'],
  'in_progress' => ['label'=>'กำลังทำ',     'cls'=>'bg-sky-100 text-sky-700 ring-1 ring-sky-200/60'],
  'done'        => ['label'=>'เสร็จสิ้น',   'cls'=>'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200/60'],
  'overdue'     => ['label'=>'เลยกำหนด',    'cls'=>'bg-rose-100 text-rose-700 ring-1 ring-rose-200/60'],
  'cancelled'   => ['label'=>'ยกเลิก',      'cls'=>'bg-slate-200 text-slate-700 ring-1 ring-slate-300/60'],
];

// ---------- งานที่เลือก / งานของครู ----------
$task = null;
if ($taskId) {
  foreach (tasks_list2(null, null) as $r) {
    if ((int)$r['id'] === $taskId) { $task = $r; break; }
  }
}

$myTasks = [];
if (!$task) {
  foreach (tasks_list2(null, null) as $r) {
    $ok = false;
    if (!empty($r['assignees']) && is_array($r['assignees'])) {
      $ok = in_array((int)($user['id'] ?? 0), array_map('intval',$r['assignees']), true);
    }
    if (!$ok && !empty($r['assignee_name'])) {
      $nm = strtolower($user['name'] ?? '');
      $ok = $nm !== '' && (strpos(strtolower($r['assignee_name']), $nm) !== false);

    }
    if ($ok) $myTasks[] = $r;
  }
} else {
  $myTasks = [$task];
}

// ---------- อ่าน submission ล่าสุดของครู (ถ้าเลือกงาน) ----------
$latest = null;
if ($task && tbl_exists($pdo, 'task_submissions')) {
  $hasSenderId   = col_exists($pdo,'task_submissions','sender_id');
  $hasTaskId     = col_exists($pdo,'task_submissions','task_id');
  $sql = "SELECT * FROM task_submissions";
  $w=[]; $p=[];
  if ($hasTaskId)   { $w[]="task_id = ?";   $p[]=(int)$task['id']; }
  if ($hasSenderId) { $w[]="sender_id = ?"; $p[]=(int)($user['id'] ?? 0); }
  if ($w) $sql .= " WHERE ".implode(' AND ',$w);
  $sql .= " ORDER BY id DESC LIMIT 1";
  $st = $pdo->prepare($sql); $st->execute($p);
  $latest = $st->fetch(PDO::FETCH_ASSOC);
  if ($latest && !empty($latest['files'])) {
      $files = json_decode($latest['files'], true);
      $latest['files'] = is_array($files) ? $files : [];
  }
}

// ---------- POST: ส่งงาน ----------
$flashOk = $flashErr = null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    if (!verify_csrf($_POST['csrf'] ?? '')) throw new RuntimeException('CSRF token ไม่ถูกต้อง');
    $tid = (int)($_POST['task_id'] ?? 0);
    if (!$tid) throw new RuntimeException('ไม่พบรหัสงาน');

    $content = trim($_POST['content'] ?? '');
    $linkUrl = trim($_POST['link_url'] ?? '');

    // (รองรับอัปโหลดไฟล์; ถ้าอยากเก็บเฉพาะลิงก์ สามารถซ่อนไว้ได้)
    // $fileUrls = [];
    // if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
    //   $baseDir = __DIR__ . '/../uploads/submissions';
    //   if (!is_dir($baseDir)) @mkdir($baseDir, 0777, true);
    //   foreach ($_FILES['files']['name'] as $i=>$name) {
    //     $err = $_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
    //     if ($err !== UPLOAD_ERR_OK) continue;
    //     $tmp = $_FILES['files']['tmp_name'][$i];
    //     $ext = pathinfo($name, PATHINFO_EXTENSION);
    //     $fn  = 'sub_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . ($ext ? ".{$ext}" : '');
    //     if (move_uploaded_file($tmp, $baseDir.'/'.$fn)) {
    //       $fileUrls[] = 'uploads/submissions/'.$fn;
    //     }
    //   }
    // }
    $uploadedFiles = [];

    if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {

        $ym = date('Y/m');
        $baseDir = __DIR__ . "/../uploads/submissions/$ym";

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        foreach ($_FILES['files']['name'] as $i => $originalName) {

            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmp = $_FILES['files']['tmp_name'][$i];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $safeName = preg_replace('/[^a-zA-Z0-9ก-๙._-]/u', '_', $originalName);

            $filename = 'sub_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? ".$ext" : '');
            $fullPath = "$baseDir/$filename";
            $dbPath   = "uploads/submissions/$ym/$filename";

            if (move_uploaded_file($tmp, $fullPath)) {
                $uploadedFiles[] = [
                    'path' => $dbPath,
                    'name' => $safeName,
                    'type' => $_FILES['files']['type'][$i],
                    'size' => $_FILES['files']['size'][$i],
                ];
            }
        }
    }


    if (!tbl_exists($pdo,'task_submissions')) throw new RuntimeException('ตาราง task_submissions ไม่มีในฐานข้อมูล');

    $cols=[]; $vals=[]; $prm=[];
    if (col_exists($pdo,'task_submissions','task_id'))       { $cols[]='task_id';       $vals[]='?';         $prm[]=$tid; }
    if (col_exists($pdo,'task_submissions','sender_id'))     { $cols[]='sender_id';     $vals[]='?';         $prm[]=(int)($user['id'] ?? 0); }
    if (col_exists($pdo,'task_submissions','sender_name'))   { $cols[]='sender_name';   $vals[]='?';         $prm[]=$user['name'] ?? null; }
    if (col_exists($pdo,'task_submissions','content'))       { $cols[]='content';       $vals[]='?';         $prm[]=$content ?: null; }
    if (col_exists($pdo,'task_submissions','link_url'))      { $cols[]='link_url';      $vals[]='?';         $prm[]=$linkUrl ?: null; }
    if (col_exists($pdo,'task_submissions','files'))         { $cols[] = 'files';       $vals[] = '?';       $prm[]  = json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE);}
    if (col_exists($pdo,'task_submissions','review_status')) { $cols[]='review_status'; $vals[]='?';         $prm[]='waiting'; }
    if (col_exists($pdo,'task_submissions','sent_at'))       { $cols[]='sent_at';       $vals[]='NOW()'; }

    $sql = "INSERT INTO task_submissions (".implode(',',$cols).") VALUES (".implode(',',$vals).")";
    $pdo->prepare($sql)->execute($prm);

    if (col_exists($pdo,'tasks','status')) {
      $pdo->prepare("UPDATE tasks SET status='in_progress' WHERE id=?")->execute([$tid]);
    }

    header('Location: teacher.php?id='.urlencode($tid).'&ok=1');
    exit;

  } catch (Throwable $e) {
    $flashErr = $e->getMessage();
  }
}

require_once __DIR__ . '/../templates/header.php';

// flash จาก query
if (isset($_GET['ok'])) $flashOk = 'ส่งงานเรียบร้อยแล้ว';
?>
<style>
  /* เติมความละมุนเล็กๆ เพิ่ม readable width และโทนพื้นหลัง */
  .soft-card { box-shadow: 0 10px 25px -10px rgba(2, 6, 23, .15); }
  .chip      { padding:.25rem .6rem; border-radius:9999px; font-weight:600; font-size:.75rem; }
  .divider   { height:1px; background:linear-gradient(to right,#e2e8f0,transparent); }
  .br-dash   { border:1px dashed rgba(148,163,184,.7); }
</style>

<div class="max-w-6xl mx-auto p-6">

  <!-- Header bar -->
  <div class="rounded-2xl overflow-hidden mb-6 soft-card">
    <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-blue-500 px-5 py-4">
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="h-11 w-11 rounded-xl bg-white/15 grid place-items-center text-white">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l10 5-10 5L2 7l10-5Zm0 8.5L20 7v6c0 3.87-3.58 7-8 7s-8-3.13-8-7V7l8 3.5Z"/></svg>
          </div>
          <div class="text-white">
            <div class="text-sm opacity-80">โรงเรียนบ้านหนองยิงหมี • สพป.ประจวบคีรีขันธ์ เขต 2</div>
            <h1 class="text-xl font-bold leading-tight">ส่งงาน / ดูความคืบหน้า</h1>
          </div>
        </div>
        <a href="logout.php" class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/20 text-white px-3 py-2 text-sm transition">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 13v-2H7V7l-5 5 5 5v-4h9zM20 3h-8v2h8v14h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
          ออกจากระบบ
        </a>
      </div>
    </div>
    <div class="bg-gradient-to-b from-blue-200/60 to-blue-100/60 px-4 py-2">
      <div class="text-slate-700 text-sm">หน้าครู • จัดส่งงานพร้อมแนบลิงก์หรือไฟล์</div>
    </div>
  </div>
  

  <?php if ($flashOk): ?>
    <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 soft-card"><?= htmlspecialchars($flashOk) ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 soft-card">เกิดข้อผิดพลาด: <?= htmlspecialchars($flashErr) ?></div>
  <?php endif; ?>

  <?php if (!$myTasks): ?>
    <div class="rounded-2xl border border-slate-200 bg-white/90 p-8 text-center soft-card">
      <div class="text-slate-700">ยังไม่มีงานที่มอบหมายถึงคุณ</div>
    </div>
  <?php else: ?>

    <?php foreach ($myTasks as $t):
      $statusKey = $t['status'] ?? 'pending';
      $st = $statusMeta[$statusKey] ?? ['label'=>'ไม่ทราบ','cls'=>'bg-slate-100 text-slate-600'];
      $created = !empty($t['created_at']) ? date('j F Y', strtotime($t['created_at'])) : '-';
      $due     = !empty($t['due_date'])   ? date('j F Y', strtotime($t['due_date']))   : '-';
      $isCurrent = $task && (int)$task['id'] === (int)$t['id'];

      // NEW: ถอดไฟล์แนบของงานให้เป็นลิสต์ลิงก์ (ทนทุกฟอร์แมต)
      $taskFiles = $decodeAttachments($t['attachments'] ?? null);
    ?>
    <section class="rounded-2xl border border-slate-200 bg-white overflow-hidden mb-6 soft-card">
      <!-- หัวการ์ด -->
      <div class="px-5 pt-5 pb-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h2 class="text-lg font-semibold text-slate-800 truncate"><?= htmlspecialchars($t['title'] ?? '-') ?></h2>
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-1 text-sm text-slate-600">
              <div class="flex items-center gap-2"><span class="text-slate-400">📄</span> ประเภท: <span class="font-medium ml-1"><?= htmlspecialchars($t['doc_type'] ?? '-') ?></span></div>
              <div class="flex items-center gap-2"><span class="text-slate-400">#</span> เลขที่: <span class="font-medium ml-1"><?= htmlspecialchars($t['code_no'] ?? '-') ?></span></div>
              <div class="flex items-center gap-2"><span class="text-slate-400">🏢</span> ฝ่าย: <span class="font-medium ml-1"><?= htmlspecialchars($t['department_name'] ?? '-') ?></span></div>
            </div>
          </div>
          <div class="text-right">
            <div class="mb-2"><span class="chip <?= $st['cls'] ?>"><?= $st['label'] ?></span></div>
            <div class="text-sm text-slate-600">
              <div>วันที่สั่ง: <span class="font-medium"><?= htmlspecialchars($created) ?></span></div>
              <div>กำหนดส่ง: <span class="font-medium"><?= htmlspecialchars($due) ?></span></div>
            </div>
          </div>
        </div>

        <!-- NEW: เอกสาร/ไฟล์แนบของงาน (แสดงเป็นชิปลิงก์แบบ All/Review) -->
        <?php if (!empty($taskFiles)): ?>
          <div class="mt-4">
            <div class="text-sm text-slate-700 mb-2">เอกสาร/ไฟล์แนบของงาน</div>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($taskFiles as $f) echo $renderFileChip($f); ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($isCurrent && $latest): ?>
          <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4">
            <div class="flex items-center justify-between">
              <div class="font-semibold text-emerald-800">งานที่ส่งแล้ว</div>
              <a href="teacher.php?id=<?= (int)$t['id'] ?>" class="text-xs inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white">แก้ไขการส่งงาน</a>
            </div>
            <div class="text-sm text-emerald-900/80 mt-1">
              ครั้งล่าสุด: <?= !empty($latest['sent_at']) ? htmlspecialchars(date('j F Y เวลา H:i', strtotime($latest['sent_at']))) : '-' ?>
            </div>
            <?php if (!empty($latest['content'])): ?>
              <div class="mt-3 text-sm bg-white/80 rounded-lg p-3 border"><?= nl2br(htmlspecialchars($latest['content'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($latest['link_url'])): ?>
              <div class="mt-2 text-sm">ลิงก์: <a href="<?= htmlspecialchars($latest['link_url']) ?>" class="text-blue-700 underline" target="_blank" rel="noopener"><?= htmlspecialchars($latest['link_url']) ?></a></div>
            <?php endif; ?>
            <?php if (!empty($latest['files'])): ?>
              <div class="mt-3 text-sm">
                <div class="text-slate-700 mb-1">ไฟล์ที่แนบ:</div>

                <div class="flex flex-wrap gap-3">
                  <?php foreach ($latest['files'] as $f): ?>
                    <?php
                      $url  = htmlspecialchars($f['path']);
                      $name = htmlspecialchars($f['name']);
                      $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                      $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                    ?>

                    <?php if ($isImg): ?>
                      <a href="<?= $url ?>" target="_blank"
                        class="rounded-lg border br-dash p-1 hover:border-emerald-300">
                        <img src="<?= $url ?>" class="w-28 h-20 object-cover rounded-md" />
                      </a>
                    <?php else: ?>
                      <a href="<?= $url ?>" target="_blank"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border hover:bg-slate-50">
                        📎 <?= $name ?>
                      </a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="divider"></div>

      <!-- ฟอร์มส่งงาน -->
      <div class="px-5 py-5 bg-slate-50/60">
        <div class="flex items-center justify-between mb-3">
          <div class="font-semibold text-slate-800">ส่งงาน</div>
          <?php if (!$isCurrent): ?>
            <a class="text-sm text-blue-700 underline" href="teacher.php?id=<?= (int)$t['id'] ?>">ไปหน้าส่งงาน</a>
          <?php endif; ?>
        </div>

        <?php if ($isCurrent): ?>
          <form method="post" action="teacher.php?id=<?= (int)$t['id'] ?>" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
            <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
            <a href="teacher.php"
              class="inline-flex items-center gap-2 rounded-xl bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 text-sm font-medium shadow-sm transition">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 11H7.83l4.88-4.88a1 1 0 10-1.42-1.42l-6.59 6.59a1 1 0 000 1.42l6.59 6.59a1 1 0 101.42-1.42L7.83 13H19a1 1 0 100-2z"/>
              </svg>
              ย้อนกลับ
            </a>
            <div>
              <label class="block text-sm text-slate-700 mb-1">คำอธิบายรายละเอียดงาน</label>
              <textarea name="content" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300" placeholder="อธิบายรายละเอียดการส่งงาน..."><?= isset($latest['content']) ? htmlspecialchars($latest['content']) : '' ?></textarea>
            </div>

            <!-- <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-slate-700 mb-1">ลิงก์ไฟล์ (Google Drive/YouTube/ฯลฯ)</label>
                <input type="url" name="link_url" value="<?= isset($latest['link_url']) ? htmlspecialchars($latest['link_url']) : '' ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300" placeholder="https://..." />
                <div class="text-xs text-slate-500 mt-1">หากแนบลิงก์แล้วอาจไม่ต้องอัปโหลดไฟล์ซ้ำ</div>
              </div>
            </div> -->
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-slate-700 mb-1">
                  แนบไฟล์งาน
                </label>

                <input
                  type="file"
                  name="files[]"
                  multiple
                  accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar,.jpg,.jpeg,.png"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2.5
                        file:mr-4 file:rounded-lg file:border-0
                        file:bg-blue-50 file:px-4 file:py-2
                        file:text-sm file:font-semibold
                        file:text-blue-700 hover:file:bg-blue-100
                        focus:ring-2 focus:ring-blue-300"
                />

                <div class="text-xs text-slate-500 mt-1">
                  รองรับหลายไฟล์ (PDF, Word, PowerPoint, ZIP, รูปภาพ)
                </div>
              </div>
            </div>


            <div class="text-sm text-slate-600">
              แจ้งเตือน: <span class="chip bg-slate-200 text-slate-700"><?= htmlspecialchars($t['assignee_name'] ?? 'ครูผู้รับผิดชอบหลัก') ?></span>
            </div>

            <div class="pt-1">
              <button class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
                ส่งงาน
              </button>
            </div>
          </form>
        <?php else: ?>
          <div class="text-sm text-slate-600">กด “ไปหน้าส่งงาน” เพื่อกรอกข้อมูล/แนบไฟล์</div>
        <?php endif; ?>
      </div>
    </section>
    <?php endforeach; ?>

  <?php endif; ?>
</div>

<script>
// โทสต์เล็ก ๆ (เผื่อใช้ในอนาคต)
function showToast(msg, isErr=false) {
  const el = document.createElement('div');
  el.textContent = msg;
  el.className = `fixed z-50 right-4 bottom-4 px-4 py-2 rounded-xl shadow text-white ${isErr ? 'bg-rose-600' : 'bg-emerald-600'}`;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 2500);
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
