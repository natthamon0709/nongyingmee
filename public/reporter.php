<?php
// ===== reporter.php =====

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/SettingsRepository.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/TaskRepository.php';

$user = require_auth(['reporter','admin']);

// ========== In-page POST (บันทึกงาน) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  try {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
      http_response_code(419);
      echo json_encode(['ok'=>false,'message'=>'CSRF token ไม่ถูกต้อง']); exit;
    }

    $doc_type      = trim($_POST['doc_type'] ?? '');
    $code_no       = trim($_POST['code_no'] ?? '');
    $title         = trim($_POST['title'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $assignees     = array_map('intval', $_POST['assignees'] ?? []);
    $sent_date     = $_POST['sent_date'] ?: null;
    $due_text      = trim($_POST['due_text'] ?? '');
    $due_date      = $_POST['due_date'] ?: null;

    if ($title === '' || !$department_id) {
      http_response_code(422);
      echo json_encode(['ok'=>false,'message'=>'กรุณากรอกเรื่อง/คำแจ้ง และเลือกฝ่ายงาน']); exit;
    }

    // URLs แนบมาเป็นบรรทัด ๆ
    $image_urls_raw = $_POST['image_urls'] ?? '';
    $upload_paths = [];
    if ($image_urls_raw !== '') {
      foreach (preg_split('/\r\n|\r|\n/', $image_urls_raw) as $line) {
        $u = trim($line);
        if ($u !== '' && preg_match('~^https?://~i', $u) && filter_var($u, FILTER_VALIDATE_URL)) {
          $upload_paths[] = $u;
        }
      }
    }

    if (!function_exists('task_create_inline')) {
      function task_create_inline(array $payload): int {
        if (function_exists('task_create')) return (int) task_create($payload);
        if (class_exists('TaskRepository') && method_exists('TaskRepository','create')) {
          return (int) TaskRepository::create($payload);
        }
        return 0;
      }
    }

    $task_id = task_create_inline([
      'doc_type'      => $doc_type,
      'code_no'       => $code_no,
      'title'         => $title,
      'department_id' => $department_id,
      'assignees'     => $assignees,
      'sent_date'     => $sent_date,
      'due_text'      => $due_text,
      'due_date'      => $due_date,
      'attachments'   => $upload_paths,   // เก็บเป็นลิงก์
      'created_by'    => $user['id'] ?? null,
      'status'        => 'pending',
    ]);

    echo json_encode(['ok'=>true,'message'=>'บันทึกข้อมูลเรียบร้อย','task_id'=>$task_id,'files'=>$upload_paths]);
    exit;

  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'มีข้อผิดพลาดภายในระบบ','error'=>$e->getMessage()]);
    exit;
  }
}

// ========= VIEW (GET) =========
require_once __DIR__ . '/../templates/header.php';

$tab = $_GET['tab'] ?? 'form';
function tabClass($current,$target){
  $base='inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm border transition';
  $on='bg-white text-blue-700 border-white shadow';
  $off='bg-white/60 text-slate-700 border-white/70 hover:bg-white';
  return $base.' '.($current===$target?$on:$off);
}

$departments = settings_list('department');
$docTypes    = settings_list('doc_type');
if (!$docTypes) {
  $docTypes = [
    ['id'=>0,'name'=>'บันทึกข้อความ'],
    ['id'=>0,'name'=>'หนังสือราชการ'],
    ['id'=>0,'name'=>'รายงานผล'],
    ['id'=>0,'name'=>'คำสั่ง'],
  ];
}
$allUsers = user_list();
$today    = date('Y-m-d');

/* ========= เมตาสถานะสำหรับ “review” (ใช้กับทั้ง all และ review) ========= */
$reviewMeta = [
  'waiting'  => ['label'=>'รอตรวจ','cls'=>'bg-amber-100 text-amber-700'],
  'approved' => ['label'=>'เสร็จสิ้น','cls'=>'bg-emerald-100 text-emerald-700'],
  'rework'   => ['label'=>'รอแก้ไข','cls'=>'bg-rose-100 text-rose-700'],
];

/* ========= Helper: attachments → array ของ URL/obj ========= */
$decodeAttachments = function($raw){
  if ($raw === null) return [];
  $data = json_decode($raw, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    if (is_string($data)) return [$data];
    if (is_array($data))  return $data;
  }
  $raw = trim((string)$raw);
  if ($raw === '') return [];
  if (filter_var($raw, FILTER_VALIDATE_URL)) return [$raw];
  $parts = preg_split("/[\r\n,]+/", $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
  return array_values(array_filter($parts, fn($u)=>filter_var($u, FILTER_VALIDATE_URL)));
};

/* ========= Helper: เรนเดอร์ปุ่มลิงก์ไฟล์แนบ ========= */
$renderFileChip = function($f){
  if (is_string($f)) {
    $url = $f; $label = basename(parse_url($url, PHP_URL_PATH)) ?: 'เปิดไฟล์';
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

/* ========= “งานที่เพิ่งสร้าง” ของผู้ใช้ปัจจุบัน ========= */
$itemsAll = tasks_list(null,null) ?? [];
$RECENT_DAYS = 7;
$now=time();
$itemsRecent=[];
if (!empty($itemsAll)){
  $itemsRecent = array_values(array_filter($itemsAll,function($t) use($user,$RECENT_DAYS,$now){
    $creatorOk = (int)($t['created_by'] ?? 0) === (int)($user['id'] ?? 0);
    $ts = !empty($t['created_at']) ? strtotime($t['created_at']) : 0;
    return $creatorOk && $ts && ($now-$ts)<=($RECENT_DAYS*86400);
  }));
  usort($itemsRecent,function($a,$b){
    $ta = !empty($a['created_at'])?strtotime($a['created_at']):0;
    $tb = !empty($b['created_at'])?strtotime($b['created_at']):0;
    return $tb <=> $ta;
  });
}
?>
<div class="max-w-6xl mx-auto p-6">
  <div class="rounded-2xl overflow-hidden shadow mb-6">
    <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-blue-500 px-5 py-4">
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-white/15 grid place-items-center text-white">
            <img
              src="assets/logo-school.png"
              alt="โลโก้โรงเรียนบ้านหนองยิงหมี"
              class="w-6 h-6 object-contain rounded-full bg-white border border-white shadow"
            />
          </div>
          <div class="text-white">
            <h1 class="text-xl font-bold leading-tight">ระบบติดตามงานราชการ</h1>
            <p class="text-white/80 text-sm">โรงเรียนบ้านหนองยิงหมี • สพป.ประจวบคีรีขันธ์ เขต 2</p>
          </div>
        </div>
        <a href="logout.php" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 text-sm">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 13v-2H7V7l-5 5 5 5v-4h9zM20 3h-8v2h8v14h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
          ออกจากระบบ
        </a>
      </div>
    </div>
    <div class="bg-gradient-to-b from-blue-200 to-blue-100 px-3 py-2">
      <nav class="flex flex-wrap gap-2">
        <a href="?tab=form" class="<?= tabClass($tab,'form') ?>">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h10M4 18h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          แจ้งงาน
        </a>
        <a href="?tab=all" class="<?= tabClass($tab,'all') ?>">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="14" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/></svg>
          แสดงผลทั้งหมด
        </a>
      </nav>
    </div>
  </div>

  <div class="rounded-2xl border border-blue-100 bg-white shadow-sm">
    <div class="h-2 w-full rounded-t-2xl bg-gradient-to-r from-blue-200 via-blue-300 to-yellow-200"></div>
    <div class="p-6">

    <?php if ($tab === 'form'): ?>

      <!-- ====== ฟอร์มแจ้งงาน ====== -->
      <div class="text-lg font-semibold mb-4">🔔 แจ้งงาน</div>
      <form id="reportForm" method="post" action="reporter.php?tab=form" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
        <div>
          <label class="block text-sm text-slate-700 mb-1">ประเภทหนังสือ</label>
          <select name="doc_type" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
            <?php foreach($docTypes as $r): ?>
              <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm text-slate-700 mb-1">เลขที่หนังสือ</label>
          <input type="text" name="code_no" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="ระบุเลขที่หนังสือ">
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm text-slate-700 mb-1">เรื่อง/คำแจ้ง</label>
          <textarea name="title" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="พิมพ์รายละเอียด..."></textarea>
        </div>
        <div>
          <label class="block text-sm text-slate-700 mb-1">ฝ่ายงาน</label>
          <select name="department_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
            <?php foreach($departments as $d): ?>
              <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- <div>
          <label class="block text-sm text-slate-700 mb-1">ผู้รับผิดชอบ</label>
          <select name="assignees[]" multiple size="7" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 h-[190px] overflow-auto">
            <?php foreach($allUsers as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="text-xs text-gray-500 mt-1">กด Ctrl/⌘ เพื่อเลือกหลายคน</div>
        </div> -->
        <div style="font-family:'Kanit',sans-serif; color:#1e293b;">
          <label style="display:block; font-size:14px; font-weight:600; color:#334155; margin-bottom:6px;">
            ผู้รับผิดชอบ
          </label>

          <div style="position:relative;">
            <select
              name="assignees[]"
              multiple
              size="7"
              style="
                width:100%;
                border-radius:16px;
                border:1px solid #cbd5e1;
                padding:12px 40px 12px 16px;
                background-color:#fff;
                color:#0f172a;
                font-size:14px;
                line-height:1.5;
                box-shadow:0 2px 6px rgba(0,0,0,0.05);
                height:200px;
                outline:none;
                transition:all .2s ease;
                overflow:auto;
                scrollbar-width:thin;
                scrollbar-color:#93c5fd #f1f5f9;
              "
              onfocus="this.style.borderColor='#60a5fa';this.style.boxShadow='0 0 0 3px rgba(147,197,253,0.5)'"
              onblur="this.style.borderColor='#cbd5e1';this.style.boxShadow='0 2px 6px rgba(0,0,0,0.05)'"
            >
              <?php foreach($allUsers as $u): ?>
                <option
                  value="<?= (int)$u['id'] ?>"
                  style="
                    padding:6px 10px;
                    cursor:pointer;
                    border-radius:8px;
                  "
                  onmouseover="this.style.backgroundColor='#eff6ff'"
                  onmouseout="this.style.backgroundColor=''"
                >
                  <?= htmlspecialchars($u['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <!-- ไอคอนลูกศร -->
            <svg xmlns='http://www.w3.org/2000/svg'
                viewBox='0 0 20 20'
                fill='currentColor'
                style='width:20px;height:20px;color:#60a5fa;position:absolute;right:14px;top:14px;pointer-events:none;opacity:.8;'>
              <path fill-rule='evenodd'
                    d='M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z'
                    clip-rule='evenodd'/>
            </svg>
          </div>

          <div style="font-size:12px; color:#64748b; margin-top:4px; font-style:italic;">
            กด Ctrl (Windows) หรือ ⌘ (Mac) เพื่อเลือกหลายคนพร้อมกัน
          </div>
        </div>

          <div class="text-xs text-slate-500 mt-1 italic">
            กด Ctrl (Windows) หรือ ⌘ (Mac) เพื่อเลือกหลายคนพร้อมกัน
        </div>
        
        <div class="md:col-span-2">
          <label class="block text-sm text-slate-700 mb-1">ลิงก์รูปภาพ (URL)</label>
          <textarea name="image_urls" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="วางลิงก์รูปภาพ 1 รายการต่อ 1 บรรทัด&#10;เช่น https://example.com/image1.jpg"></textarea>
          <div class="text-xs text-gray-500 mt-1">รองรับ http/https เท่านั้น</div>
        </div>
        <div>
          <label class="block text-sm text-slate-700 mb-1">วันที่ส่ง</label>
          <input type="date" name="sent_date" value="<?= $today ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
        </div>
        <div>
          <label class="block text-sm text-slate-700 mb-1">กำหนดส่ง (ข้อความ)</label>
          <input type="text" name="due_text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="เช่น ภายใน 3 วัน / ด่วนมาก">
        </div>
        <div>
          <label class="block text-sm text-slate-700 mb-1">กำหนดส่ง (วันที่)</label>
          <input type="date" name="due_date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
        </div>

        <div class="md:col-span-2 flex flex-wrap gap-3 pt-1">
          <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
            แจ้งงาน
          </button>
          <button type="button" id="btnPersonal" class="inline-flex items-center gap-2 rounded-xl bg-white border border-blue-200 text-blue-700 px-4 py-2.5 hover:bg-blue-50">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zM2 22a10 10 0 0120 0"/></svg>
            สั่งงานรายบุคคล
          </button>
        </div>
      </form>

      <!-- ====== สรุปงานที่เพิ่งสร้าง ====== -->
      <!-- <div class="mt-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-3">🆕 สรุปงานที่เพิ่งสร้าง</h3>
        <?php if (!empty($itemsRecent)): ?>
          <div id="recentList" class="space-y-3">
            <?php foreach ($itemsRecent as $t):
              $created = !empty($t['created_at']) ? date('d/m/Y', strtotime($t['created_at'])) : '-';
              $due     = !empty($t['due_date'])   ? date('d/m/Y', strtotime($t['due_date']))   : '-';
            ?>
            <div class="flex flex-col md:flex-row md:items-center justify-between rounded-2xl border border-slate-200 bg-white/90 shadow-sm p-4 hover:bg-slate-50 transition">
              <div class="flex-1 space-y-1">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-slate-800"><?= htmlspecialchars($t['title'] ?? '-') ?></span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">รอดำเนินการ</span>
                </div>
                <div class="text-sm text-slate-600">
                  📄 <?= htmlspecialchars($t['doc_type'] ?? '-') ?> • ฝ่าย: <?= htmlspecialchars($t['department_name'] ?? '-') ?> • ผู้รับผิดชอบ: <?= htmlspecialchars($t['assignee_name'] ?? '-') ?>
                </div>
              </div>
              <div class="mt-2 md:mt-0 text-sm text-slate-500 flex-shrink-0 text-right">
                <div>วันที่ส่ง <?= htmlspecialchars($created) ?></div>
                <div>ครบกำหนด <?= htmlspecialchars($due) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div id="recentList" class="p-6 text-slate-600 bg-slate-50 rounded-2xl border border-slate-200 text-center">— ยังไม่มีงานที่เพิ่งสร้างในช่วงนี้ —</div>
        <?php endif; ?>
      </div> -->

    <?php elseif ($tab === 'all'): ?>

      <?php
      // ====== ฟิลเตอร์ของแท็บ ALL (ใช้ review status + dept) ======
      $filterDept   = isset($_GET['dept']) && $_GET['dept'] !== '' ? (int)$_GET['dept'] : null;
      $filterReview = isset($_GET['r'])    && $_GET['r']    !== '' ? $_GET['r']        : null;

      // ดึงผ่าน tasks_review_list (รวมฟิลด์ submission ล่าสุด + task_attachments)
      $rows = tasks_review_list($filterReview, $filterDept);
      ?>

      <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-slate-700" viewBox="0 0 24 24" fill="none">
          <path d="M4 6h16M4 12h16M4 18h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        แสดงผลการแจ้งงานและการส่งงานทั้งหมด
      </h2>

      <!-- ตัวกรอง -->
      <form method="get" class="flex flex-wrap items-center gap-3 mb-4">
        <input type="hidden" name="tab" value="all">
        <select name="dept" class="rounded-xl border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
          <option value="">ทุกฝ่าย</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= (int)$d['id'] ?>" <?= $filterDept===(int)$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="r" class="rounded-xl border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
          <option value="">ทุกสถานะตรวจ</option>
          <?php foreach ($reviewMeta as $k=>$m): ?>
            <option value="<?= $k ?>" <?= $filterReview===$k?'selected':'' ?>><?= $m['label'] ?></option>
          <?php endforeach; ?>
        </select>
        <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 text-white px-4 py-2.5 hover:bg-blue-700">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10 18l6-6-6-6v12z"/></svg>
          กรอง
        </button>
        <?php if ($filterDept || $filterReview): ?>
          <a href="reporter.php?tab=all" class="ml-1 text-slate-600 hover:underline">ล้างตัวกรอง</a>
        <?php endif; ?>
      </form>

      <!-- รายการการ์ด -->
      <div class="space-y-4">
        <?php foreach ($rows as $r): ?>
          <?php
            $revKey  = $r['review_status'] ?? 'waiting';
            $badge   = $reviewMeta[$revKey] ?? $reviewMeta['waiting'];
            $created = $r['created_at'] ? date('j F Y', strtotime($r['created_at'])) : '-';
            $due     = $r['due_date']   ? date('j F Y', strtotime($r['due_date']))   : '-';

            // แนบของงาน (tasks.attachments AS task_attachments) + แนบของการส่งล่าสุด (s2.attachments)
            $taskFiles = $decodeAttachments($r['task_attachments'] ?? null);
            $subFiles  = $decodeAttachments($r['attachments']      ?? null);
          ?>
          <article class="relative rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm">
            <div class="absolute top-3 right-3">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $badge['cls'] ?>">
                <?= $badge['label'] ?>
              </span>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <h3 class="font-semibold text-slate-800"><?= htmlspecialchars($r['title'] ?: '-') ?></h3>
                <div class="text-sm text-slate-700 space-y-1">
                  <div class="flex items-center gap-2"><span class="text-slate-500">📄</span> ประเภท: <?= htmlspecialchars($r['doc_type'] ?: '-') ?></div>
                  <div class="flex items-center gap-2"><span class="text-slate-500">#</span> เลขที่: <?= htmlspecialchars($r['code_no'] ?: '-') ?></div>
                  <div class="flex items-center gap-2">🏢 ฝ่าย: <?= htmlspecialchars($r['department_name'] ?: '-') ?></div>
                </div>

                <!-- ไฟล์แนบของงาน -->
                <?php if (!empty($taskFiles)): ?>
                  <div class="mt-3">
                    <div class="text-slate-700 font-medium mb-2">ไฟล์แนบของงาน</div>
                    <div class="flex flex-wrap gap-2">
                      <?php foreach ($taskFiles as $f) echo $renderFileChip($f); ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <div class="space-y-2 text-sm text-slate-700">
                <div class="flex items-center gap-2">📅 วันที่สั่ง: <?= htmlspecialchars($created) ?></div>
                <div class="flex items-center gap-2">📅 กำหนดส่ง: <?= htmlspecialchars($due) ?></div>
                <div class="flex items-center gap-2">👤 ผู้รับผิดชอบ: <?= htmlspecialchars($r['assignee_name'] ?: '-') ?></div>
              </div>
            </div>

            <hr class="my-4">

            <!-- การส่งล่าสุด -->
            <div>
              <div class="text-slate-700 font-medium mb-2">การส่งงาน (ล่าสุด)</div>
              <?php if (!empty($r['submission_id'])): ?>
                <div class="rounded-xl border border-amber-200 bg-amber-50/30 p-4">
                  <div class="font-semibold"><?= htmlspecialchars($r['sender_name']) ?></div>
                  <div class="text-xs text-slate-600 mb-2">ส่งเมื่อ: <?= htmlspecialchars(date('j F Y เวลา H:i', strtotime($r['sent_at']))) ?></div>
                  <div class="text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($r['content'] ?? '')) ?></div>

                  <?php if (!empty($subFiles)): ?>
                    <div class="mt-3">
                      <div class="text-slate-700 font-medium mb-2">ไฟล์แนบของการส่ง</div>
                      <div class="flex flex-wrap gap-2">
                        <?php foreach ($subFiles as $f) echo $renderFileChip($f); ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-slate-600">ยังไม่มีการส่งงาน</div>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (empty($rows)): ?>
          <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 text-slate-600">ไม่พบรายการ</div>
        <?php endif; ?>
      </div>

    <?php endif; ?>
    </div>
  </div>
</div>

<script>
  document.getElementById('btnPersonal')?.addEventListener('click', () => {
    const sel = document.querySelector('select[name="assignees[]"]');
    if (!sel) return;
    const picked = Array.from(sel.options).some(o => o.selected);
    if (!picked) { alert('โปรดเลือกผู้รับผิดชอบอย่างน้อย 1 คน'); sel.focus(); }
    else { alert('ระบบจะสร้างงานแยกรายบุคคลตามรายชื่อที่เลือก'); }
  });

  document.getElementById('reportForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    try {
      const res = await fetch(form.action, { method:'POST', body:data });
      const json = await res.json();
      if (!res.ok || !json.ok) throw new Error(json?.message || 'ส่งข้อมูลไม่สำเร็จ');
      showToast('✅ บันทึกข้อมูลเรียบร้อยแล้ว');
      const payload = Object.fromEntries(data.entries());
      prependRecentCard({
        title: payload.title || '-',
        doc_type: payload.doc_type || '-',
        department_name: getSelectedText('select[name="department_id"]') || '-',
        assignee_name: getSelectedMultiText('select[name="assignees[]"]') || '-',
        created_at: new Date().toISOString(),
        due_date: payload.due_date || ''
      });
      form.reset();
    } catch (err) { showToast('❌ ' + err.message, true); }
  });

  function getSelectedText(selector){
    const el=document.querySelector(selector); if(!el) return '';
    return el.options[el.selectedIndex]?.text?.trim()||'';
  }
  function getSelectedMultiText(selector){
    const el=document.querySelector(selector); if(!el) return '';
    return Array.from(el.selectedOptions).map(o=>o.text.trim()).join(', ');
  }
  function prependRecentCard(t){
    const list=document.getElementById('recentList'); if(!list) return;
    const created=formatDate(t.created_at); const due=t.due_date?formatDate(t.due_date):'-';
    const wrapper=document.createElement('div');
    wrapper.className="flex flex-col md:flex-row md:items-center justify-between rounded-2xl border border-slate-200 bg-white/90 shadow-sm p-4 hover:bg-slate-50 transition";
    wrapper.innerHTML=`
      <div class="flex-1 space-y-1">
        <div class="flex items-center gap-2">
          <span class="font-semibold text-slate-800">${escapeHtml(t.title)}</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">รอดำเนินการ</span>
        </div>
        <div class="text-sm text-slate-600">
          📄 ${escapeHtml(t.doc_type||'-')} • ฝ่าย: ${escapeHtml(t.department_name||'-')} • ผู้รับผิดชอบ: ${escapeHtml(t.assignee_name||'-')}
        </div>
      </div>
      <div class="mt-2 md:mt-0 text-sm text-slate-500 flex-shrink-0 text-right">
        <div>วันที่ส่ง ${escapeHtml(created)}</div>
        <div>ครบกำหนด ${escapeHtml(due)}</div>
      </div>`;
    if (list.classList.contains('text-center')) {
      list.classList.remove('text-center','p-6','bg-slate-50','border','border-slate-200','text-slate-600');
      list.innerHTML='';
    }
    list.prepend(wrapper);
  }
  function formatDate(iso){ try{const d=new Date(iso);return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear();}catch{return'-';}}
  function escapeHtml(s=''){return s.replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}
  function showToast(msg,isErr=false){
    const el=document.createElement('div'); el.textContent=msg;
    el.className=`fixed z-50 right-4 bottom-4 px-4 py-2 rounded-xl shadow text-white ${isErr?'bg-rose-600':'bg-emerald-600'}`;
    document.body.appendChild(el); setTimeout(()=>el.remove(),2500);
  }
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
