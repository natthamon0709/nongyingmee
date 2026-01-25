<?php
// ===== admin.php =====

// (แนะนำช่วงพัฒนา) เปิด error:
error_reporting(E_ALL); ini_set('display_errors', '1');

require_once __DIR__ . '/../src/helpers.php';              // ต้องมาก่อน header.php
require_once __DIR__ . '/../src/SettingsRepository.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/TaskRepository.php';
require_once __DIR__ . '/../templates/header.php';

// อนุญาตเฉพาะ admin
require_auth('admin');
$user = auth_user();

// อ่านแท็บปัจจุบัน
$tab = $_GET['tab'] ?? 'settings';

// ฟังก์ชันทำคลาสให้แท็บ
function tabClass($current, $target) {
  $base = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm border transition';
  $on   = 'bg-white text-blue-700 border-white shadow';
  $off  = 'bg-white/60 text-slate-700 border-white/70 hover:bg-white';
  return $base . ' ' . ($current === $target ? $on : $off);
}
?>

<div class="max-w-6xl mx-auto p-6">

  <!-- แถบหัวสีน้ำเงิน + เมนู -->
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
        <div class="flex items-center gap-2">
          <a href="logout.php" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 text-sm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 13v-2H7V7l-5 5 5 5v-4h9zM20 3h-8v2h8v14h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
            ออกจากระบบ
          </a>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-gradient-to-b from-blue-200 to-blue-100 px-3 py-2">
      <nav class="flex flex-wrap gap-2">
        <a href="?tab=settings" class="<?= tabClass($tab,'settings') ?>">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7Z" stroke="currentColor" stroke-width="1.5"/><path d="M19 12a7 7 0 11-14 0 7 7 0 0114 0Z" stroke="currentColor" stroke-width="1.5"/></svg>
          ตั้งค่า
        </a>
        <a href="?tab=users" class="<?= tabClass($tab,'users') ?>">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M16 14a4 4 0 10-8 0v1h8v-1Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5"/></svg>
          เพิ่มผู้ใช้
        </a>
        <a href="?tab=review" class="<?= tabClass($tab,'review') ?>">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          ตรวจงาน
        </a>
        <a href="?tab=all" class="<?= tabClass($tab,'all') ?>">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="14" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="14" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/></svg>
          แสดงผลทั้งหมด
        </a>
        <a href="?tab=summary" class="<?= tabClass($tab,'summary') ?>">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 19V5m0 0l4 4M4 5l4-4M10 19h10M10 13h10M10 7h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          สรุปผล
        </a>
        <a href="?tab=dashboard" class="<?= tabClass($tab,'dashboard') ?>">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
            <path d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-18v6h8V3h-8Z"
                  stroke="currentColor" stroke-width="1.5"/>
          </svg>
          Dashboard บุคคล
        </a>
      </nav>
    </div>
  </div>

  <!-- เนื้อหาแท็บ -->
  <div class="rounded-2xl border border-blue-100 bg-white shadow-sm">
    <div class="h-2 w-full rounded-t-2xl bg-gradient-to-r from-blue-200 via-blue-300 to-yellow-200"></div>
    <div class="p-6">

    <?php if ($tab === 'settings'): ?>

        <!-- ========== SETTINGS TAB ========== -->
        <?php if (!empty($_SESSION['flash_success']) || !empty($_SESSION['flash_error'])): ?>
          <div class="mb-3">
            <?php if (!empty($_SESSION['flash_success'])): ?>
              <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-3 py-2 text-sm">
                <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['flash_error'])): ?>
              <div class="mt-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 px-3 py-2 text-sm">
                <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-slate-600" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M6 12h12M6 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          ตั้งค่าระบบ
        </h2>

        <?php
          $listPosition   = settings_list('position');
          $listRank       = settings_list('rank');
          $listStatus     = settings_list('status');
          $listDepartment = settings_list('department');
        ?>

        <div class="grid md:grid-cols-2 gap-5">
          <!-- ตำแหน่ง -->
          <section class="rounded-2xl border border-blue-100 bg-white/80 backdrop-blur p-4 shadow-sm">
            <h3 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">ตำแหน่ง</h3>
            <form action="settings_save.php" method="post" class="flex gap-2 mb-3">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
              <input type="hidden" name="type" value="position">
              <input name="name" required class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-300" placeholder="เพิ่มตำแหน่งใหม่">
              <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3 py-2">เพิ่ม</button>
            </form>
            <ul class="space-y-2">
              <?php foreach ($listPosition as $row): ?>
                <li class="flex items-center justify-between rounded-xl border bg-white px-3 py-2">
                  <span><?= htmlspecialchars($row['name']) ?></span>
                  <form action="settings_delete.php" method="post" onsubmit="return confirm('ลบรายการนี้?')">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="type" value="position">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button class="text-rose-500 hover:text-rose-700" title="ลบ">ลบ</button>
                  </form>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>

          <!-- วิทยฐานะ -->
          <section class="rounded-2xl border border-blue-100 bg-white/80 backdrop-blur p-4 shadow-sm">
            <h3 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">วิทยฐานะ</h3>
            <form action="settings_save.php" method="post" class="flex gap-2 mb-3">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
              <input type="hidden" name="type" value="rank">
              <input name="name" required class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-300" placeholder="เพิ่มวิทยฐานะใหม่">
              <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3 py-2">เพิ่ม</button>
            </form>
            <ul class="space-y-2">
              <?php foreach ($listRank as $row): ?>
                <li class="flex items-center justify-between rounded-xl border bg-white px-3 py-2">
                  <span><?= htmlspecialchars($row['name']) ?></span>
                  <form action="settings_delete.php" method="post" onsubmit="return confirm('ลบรายการนี้?')">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="type" value="rank">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button class="text-rose-500 hover:text-rose-700">ลบ</button>
                  </form>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>

          <!-- สถานะ -->
          <section class="rounded-2xl border border-blue-100 bg-white/80 backdrop-blur p-4 shadow-sm">
            <h3 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">สถานะ</h3>
            <form action="settings_save.php" method="post" class="flex gap-2 mb-3">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
              <input type="hidden" name="type" value="status">
              <input name="name" required class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-300" placeholder="เพิ่มสถานะใหม่">
              <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3 py-2">เพิ่ม</button>
            </form>
            <ul class="space-y-2">
              <?php foreach ($listStatus as $row): ?>
                <li class="flex items-center justify-between rounded-xl border bg-white px-3 py-2">
                  <span><?= htmlspecialchars($row['name']) ?></span>
                  <form action="settings_delete.php" method="post" onsubmit="return confirm('ลบรายการนี้?')">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="type" value="status">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button class="text-rose-500 hover:text-rose-700">ลบ</button>
                  </form>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>

          <!-- ฝ่ายงาน -->
          <section class="rounded-2xl border border-blue-100 bg-white/80 backdrop-blur p-4 shadow-sm">
            <h3 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">ฝ่ายงาน</h3>
            <form action="settings_save.php" method="post" class="flex gap-2 mb-3">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
              <input type="hidden" name="type" value="department">
              <input name="name" required class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-300" placeholder="เพิ่มฝ่ายงานใหม่">
              <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3 py-2">เพิ่ม</button>
            </form>
            <ul class="space-y-2">
              <?php foreach ($listDepartment as $row): ?>
                <li class="flex items-center justify-between rounded-xl border bg-white px-3 py-2">
                  <span><?= htmlspecialchars($row['name']) ?></span>
                  <form action="settings_delete.php" method="post" onsubmit="return confirm('ลบรายการนี้?')">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="type" value="department">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button class="text-rose-500 hover:text-rose-700">ลบ</button>
                  </form>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        </div>

    <?php elseif ($tab === 'users'): ?>

<?php
    $positions = settings_list('position');
    $statuses  = settings_list('status');
    $users     = user_list();
    $users_summary = user_performance_summary();  
    // ตรวจว่าเป็นโหมด "แก้ไข"
    $editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $editUser = $editId ? user_find($editId) : null;
?>

<h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
  <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="none">
    <path d="M16 14a4 4 0 10-8 0v1h8v-1Z" stroke="currentColor" stroke-width="1.5"/>
    <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5"/>
  </svg>
  <?= $editUser ? 'แก้ไขข้อมูลผู้ใช้' : 'เพิ่มผู้ใช้ใหม่' ?>
</h2>

<!-- ฟอร์มเพิ่ม/แก้ไข -->
<form action="<?= $editUser ? 'user_update.php' : 'user_create.php' ?>" method="post"
      class="grid md:grid-cols-2 gap-4 bg-white/80 border border-blue-100 rounded-2xl p-4 shadow-sm">

  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
  <?php if ($editUser): ?>
    <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
  <?php endif; ?>

  <div>
    <label class="block text-sm text-slate-700 mb-1">ชื่อ-นามสกุล</label>
    <input name="name" required
           value="<?= htmlspecialchars($editUser['name'] ?? '') ?>"
           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
  </div>

  <div>
    <label class="block text-sm text-slate-700 mb-1">อีเมล</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($editUser['email'] ?? '') ?>"
           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
  </div>

  <div>
    <label class="block text-sm text-slate-700 mb-1">ตำแหน่ง</label>
    <select name="position_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
      <option value="">— เลือกตำแหน่ง —</option>
      <?php foreach($positions as $p): ?>
        <option value="<?= (int)$p['id'] ?>"
          <?= ($editUser && (int)$editUser['position_id'] === (int)$p['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="block text-sm text-slate-700 mb-1">สถานะ</label>
    <select name="status_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
      <option value="">— เลือกสถานะ —</option>
      <?php foreach($statuses as $s): ?>
        <option value="<?= (int)$s['id'] ?>"
          <?= ($editUser && (int)$editUser['status_id'] === (int)$s['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน</label>
    <div style="position:relative;">
      <input 
        type="password"
        name="password"
        value=""
        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-800"
        id="passwordField"
      >
      <button type="button" 
        onclick="togglePassword()" 
        style="position:absolute; right:12px; top:8px; font-size:14px; color:#64748b;">
        👁
      </button>
    </div>
  </div>

  <div>
    <label class="block text-sm text-slate-700 mb-1">คะแนนประเมิน (ดาว)</label>
    <select name="rating" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
      <?php for($i=0;$i<=5;$i++): ?>
        <option value="<?= $i ?>" <?= ($editUser && (int)$editUser['rating'] === $i) ? 'selected' : '' ?>>
          <?= $i ? str_repeat('⭐', $i) : '—' ?>
        </option>
      <?php endfor; ?>
    </select>
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm text-slate-700 mb-1">บทบาท</label>
    <select name="role" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
      <option value="teacher"  <?= ($editUser && $editUser['role']==='teacher')  ? 'selected':'' ?>>ครู</option>
      <option value="reporter" <?= ($editUser && $editUser['role']==='reporter') ? 'selected':'' ?>>แจ้งงาน</option>
      <option value="admin"    <?= ($editUser && $editUser['role']==='admin')    ? 'selected':'' ?>>ผู้ดูแลระบบ</option>
    </select>
  </div>

  <div class="md:col-span-2 flex gap-2">
    <button class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-medium">
      <?= $editUser ? '💾 บันทึกการแก้ไข' : '➕ เพิ่มผู้ใช้' ?>
    </button>
    <?php if ($editUser): ?>
      <a href="admin.php?tab=users" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800">
        ยกเลิก
      </a>
    <?php endif; ?>
  </div>
</form>

<!-- ตารางรายชื่อ -->
<div class="mt-6 rounded-2xl border border-blue-100 bg-white/80 p-4 shadow-sm overflow-x-auto">
  <h3 class="font-semibold text-blue-800 mb-3">รายชื่อผู้ใช้ทั้งหมด</h3>
  <table class="min-w-full text-sm border-collapse">
    <thead class="bg-blue-50">
      <tr class="text-left">
        <th class="px-3 py-2">ชื่อ-นามสกุล</th>
        <th class="px-3 py-2">อีเมล</th>
        <th class="px-3 py-2">ตำแหน่ง</th>
        <th class="px-3 py-2">สถานะ</th>
        <th class="px-3 py-2">คะแนน</th>
        <th class="px-3 py-2">ผลงาน</th>
        <th class="px-3 py-2">ประสิทธิภาพ</th>
        <th class="px-3 py-2">จัดการ</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users_summary as $u): ?>
      <tr class="border-t hover:bg-blue-50">
        <td class="px-3 py-2"><?= htmlspecialchars($u['name']) ?></td>
        <td class="px-3 py-2"><?= htmlspecialchars($u['email'] ?? '-') ?></td>
        <td class="px-3 py-2"><?= htmlspecialchars($u['position'] ?? '-') ?></td>
        <td class="px-3 py-2"><?= htmlspecialchars($u['status'] ?? '-') ?></td>
        <td class="px-3 py-2"><?= str_repeat('⭐', (int)$u['rating']) ?></td>
        <td class="px-3 py-2 text-xs">
          <div>📌 ทั้งหมด: <?= $u['total_tasks'] ?></div>
          <div class="text-emerald-600">✅ ผ่าน: <?= $u['approved_tasks'] ?></div>
          <div class="text-rose-600">🔁 แก้ไข: <?= $u['rework_tasks'] ?></div>
          <div class="text-amber-600">⏳ รอ: <?= $u['waiting_tasks'] ?></div>
        </td>

        <td class="px-3 py-2">
          <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
            <div 
              class="h-2 rounded-full
                <?= $u['performance'] >= 80 ? 'bg-emerald-500' : 
                    ($u['performance'] >= 50 ? 'bg-amber-400' : 'bg-rose-500') ?>"
              style="width: <?= $u['performance'] ?>%">
            </div>
          </div>
          <div class="text-xs text-slate-600 mt-1">
            <?= $u['performance'] ?>% | ⭐ <?= $u['avg_score'] ?: '-' ?>
          </div>
        </td>

        <td class="px-3 py-2">
          <a href="admin.php?tab=users&id=<?= (int)$u['id'] ?>" class="text-blue-600 hover:text-blue-800 mr-2" title="แก้ไข">✎</a>
          <form action="user_delete.php" method="post" class="inline" onsubmit="return confirm('ลบผู้ใช้นี้หรือไม่?')">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="text-rose-600 hover:text-rose-700" title="ลบ">🗑</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($users_summary)): ?>
      <tr class="border-t">
        <td class="px-3 py-4 text-slate-500 italic" colspan="6">ยังไม่มีผู้ใช้ในระบบ</td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php elseif ($tab === 'review'): ?>
<?php
  // ฟิลเตอร์
  $filterReview = isset($_GET['r'])    && $_GET['r']    !== '' ? $_GET['r'] : null;     // waiting/approved/rework
  $filterDept   = isset($_GET['dept']) && $_GET['dept'] !== '' ? (int)$_GET['dept'] : null;

  $departments = settings_list('department');

  $reviewMeta = [
    'waiting'  => ['label'=>'รอตรวจ','cls'=>'bg-amber-100 text-amber-700'],
    'approved' => ['label'=>'เสร็จสิ้น','cls'=>'bg-emerald-100 text-emerald-700'],
    'rework'   => ['label'=>'รอแก้ไข','cls'=>'bg-rose-100 text-rose-700'],
  ];

  $rows = tasks_review_list($filterReview, $filterDept);

  $decodeAttachments = function($raw) {
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
    return array_values(array_filter($parts, fn($u) => filter_var($u, FILTER_VALIDATE_URL)));
  };

  $renderFileChip = function($f) {
    if (is_string($f)) {
      $url   = $f;
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
?>

<h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
  <svg class="w-5 h-5 text-slate-700" viewBox="0 0 24 24" fill="none">
    <path d="M4 6h16M4 12h16M4 18h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
  </svg>
  ตรวจงานและอนุมัติ
</h2>

<!-- ตัวกรอง -->
<form method="get" class="flex flex-wrap items-center gap-3 mb-4">
  <input type="hidden" name="tab" value="review">

  <select name="r" class="rounded-xl border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
    <option value="">ทุกสถานะ</option>
    <?php foreach ($reviewMeta as $k=>$m): ?>
      <option value="<?= $k ?>" <?= $filterReview===$k?'selected':'' ?>><?= $m['label'] ?></option>
    <?php endforeach; ?>
  </select>

  <select name="dept" class="rounded-xl border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
    <option value="">ทุกฝ่าย</option>
    <?php foreach ($departments as $d): ?>
      <option value="<?= (int)$d['id'] ?>" <?= $filterDept===(int)$d['id']?'selected':'' ?>>
        <?= htmlspecialchars($d['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 text-white px-4 py-2.5 hover:bg-blue-700">
    กรอง
  </button>
  <?php if ($filterReview || $filterDept): ?>
    <a href="admin.php?tab=review" class="text-slate-600 hover:underline">ล้างตัวกรอง</a>
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
  $taskFiles = $decodeAttachments($r['task_attachments'] ?? null);
  $subFiles  = $decodeAttachments($r['attachments']      ?? null);
  $revKeyNorm  = strtolower(trim((string)$revKey));
  if (!in_array($revKeyNorm, ['waiting','approved','rework'], true)) {
    $revKeyNorm = 'waiting';
  }
  $hasSubmission = !empty($r['submission_id']);

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
    </div>
    <div class="space-y-2 text-sm text-slate-700">
      <div class="flex items-center gap-2">📅 วันที่สั่ง: <?= htmlspecialchars($created) ?></div>
      <div class="flex items-center gap-2">📅 กำหนดส่ง: <?= htmlspecialchars($due) ?></div>
      <div class="flex items-center gap-2">👤 ผู้รับผิดชอบ: <?= htmlspecialchars($r['assignee_name'] ?: '-') ?></div>
    </div>
  </div>

  <hr class="my-4">

  <?php if (!empty($taskFiles)): ?>
    <div class="mb-3">
      <div class="text-slate-700 font-medium mb-2">ไฟล์แนบของงาน</div>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($taskFiles as $f) echo $renderFileChip($f); ?>
      </div>
    </div>
  <?php endif; ?>

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

        <div class="mt-3 text-right">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm open-review"
            data-submission-id="<?= (int)$r['submission_id'] ?>"
            data-title="<?= htmlspecialchars($r['title'] ?? '', ENT_QUOTES) ?>"
            data-doc-type="<?= htmlspecialchars($r['doc_type'] ?? '', ENT_QUOTES) ?>"
            data-code-no="<?= htmlspecialchars($r['code_no'] ?? '', ENT_QUOTES) ?>"
            data-dept="<?= htmlspecialchars($r['department_name'] ?? '', ENT_QUOTES) ?>"
            data-sender="<?= htmlspecialchars($r['sender_name'] ?? '', ENT_QUOTES) ?>"
            data-sentat="<?= htmlspecialchars(date('j F Y เวลา H:i', strtotime($r['sent_at'])), ENT_QUOTES) ?>"
            data-content="<?= htmlspecialchars($r['content'] ?? '', ENT_QUOTES) ?>"
            data-review="<?= htmlspecialchars($revKey, ENT_QUOTES) ?>"
            data-score="<?= (int)($r['score'] ?? 0) ?>"
            data-comment="<?= htmlspecialchars($r['reviewer_comment'] ?? '', ENT_QUOTES) ?>"
          >
            🗂 ตรวจงาน
          </button>
          <form
              action="task_delete.php"
              method="post"
              class="inline ml-2"
              onsubmit="return confirm('⚠️ ต้องการลบงานนี้ทั้งหมดหรือไม่?\nการส่งงานและไฟล์แนบจะถูกลบด้วย')"
            >
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
              <input type="hidden" name="task_id" value="<?= (int)$r['id'] ?>">

              <button
                type="submit"
                class="text-rose-500 hover:text-rose-700 text-sm"
                title="ลบงาน"
              >
              ลบ
            </button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-slate-600">
        ยังไม่มีการส่งงาน
      </div>
    <?php endif; ?>
  </div>
</article>
<?php endforeach; ?>

<?php if (empty($rows)): ?>
  <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 text-slate-600">
    ไม่พบรายการตามเงื่อนไขที่เลือก
  </div>
<?php endif; ?>
</div>

<!-- Modal ตรวจงาน -->
<dialog id="reviewModal" class="rounded-2xl w-[min(920px,95vw)] backdrop:bg-black/40 p-0">
  <form action="review_save.php" method="post" class="max-h-[85vh] overflow-y-auto">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
    <input type="hidden" name="submission_id" id="rv_submission_id">

    <div class="sticky top-0 z-10 bg-white border-b rounded-t-2xl px-5 py-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span>📝</span>
        <div class="font-semibold">ตรวจงานและให้คะแนน</div>
      </div>
      <button type="button" class="text-slate-500 hover:text-slate-700" onclick="this.closest('dialog').close()">✖</button>
    </div>

    <div class="p-5 space-y-4">
      <section class="rounded-xl border bg-slate-50 px-4 py-3 text-sm">
        <div class="grid md:grid-cols-2 gap-2">
          <div>เรื่อง: <span id="rv_title" class="font-medium"></span></div>
          <div>ฝ่าย: <span id="rv_dept"></span></div>
          <div>ประเภทเอกสาร: <span id="rv_doc_type"></span></div>
          <div>เลขที่: <span id="rv_code_no"></span></div>
        </div>
      </section>

      <section class="rounded-xl border bg-blue-50/40 px-4 py-3 text-sm">
        <div class="font-semibold text-blue-900 mb-1">รายละเอียดการส่งงาน</div>
        <div class="text-slate-700"><span id="rv_content"></span></div>
        <div class="text-xs text-slate-600 mt-2">ส่งโดย <span id="rv_sender"></span> • <span id="rv_sent_at"></span></div>
      </section>

      <section class="rounded-xl border px-4 py-3">
        <div class="font-semibold mb-3">การตรวจงานและให้คะแนน</div>

        <div class="mb-3">
          <label class="block text-sm mb-1">สถานะการตรวจ</label>
          <select name="review_status" id="rv_status" class="w-full rounded-xl border-slate-300 px-3 py-2.5">
            <option value="waiting">— เลือกสถานะ —</option>
            <option value="approved">ผ่าน/เสร็จสิ้น</option>
            <option value="rework">ต้องแก้ไข</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="block text-sm mb-1">คะแนน (1–10)</label>
          <input type="range" min="1" max="10" step="1" name="score" id="rv_score" class="w-full">
          <div class="flex justify-between text-xs text-slate-500 mt-1">
            <span>ต้องปรับปรุง</span>
            <span><span id="rv_score_val">5</span> /10</span>
            <span>ดีเยี่ยม</span>
          </div>
        </div>

        <div>
          <label class="block text-sm mb-1">ความคิดเห็นและข้อเสนอแนะ</label>
          <textarea name="comment" id="rv_comment" rows="4" class="w-full rounded-xl border-slate-300 px-3 py-2.5" placeholder="ใส่ความเห็น..."></textarea>
        </div>
      </section>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300" onclick="this.closest('dialog').close()">✗ ยกเลิก</button>
        <button class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white">✓ บันทึกการตรวจ</button>
      </div>
    </div>
  </form>
</dialog>

<script>
  const dlg = document.getElementById('reviewModal');
  const score = document.getElementById('rv_score');
  const scoreVal = document.getElementById('rv_score_val');

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.open-review');
    if (!btn) return;

    document.getElementById('rv_submission_id').value = btn.dataset.submissionId;
    document.getElementById('rv_title').textContent    = btn.dataset.title    || '-';
    document.getElementById('rv_doc_type').textContent = btn.dataset.docType  || '-';
    document.getElementById('rv_code_no').textContent  = btn.dataset.codeNo   || '-';
    document.getElementById('rv_dept').textContent     = btn.dataset.dept     || '-';
    document.getElementById('rv_sender').textContent   = btn.dataset.sender   || '-';
    document.getElementById('rv_sent_at').textContent  = btn.dataset.sentat   || '-';
    document.getElementById('rv_content').textContent  = btn.dataset.content  || '-';

    document.getElementById('rv_status').value = btn.dataset.review || 'waiting';
    score.value = btn.dataset.score && parseInt(btn.dataset.score) > 0 ? btn.dataset.score : 5;
    scoreVal.textContent = score.value;
    document.getElementById('rv_comment').value = btn.dataset.comment || '';

    dlg.showModal();
  });

  score.addEventListener('input', () => {
    scoreVal.textContent = score.value;
  });
</script>

    <?php elseif ($tab === 'all'): ?>
<?php
  $filterDept   = isset($_GET['dept']) && $_GET['dept'] !== '' ? (int)$_GET['dept'] : null;
  $filterReview = isset($_GET['r'])    && $_GET['r']    !== '' ? $_GET['r']         : null;

  $departments = settings_list('department');

  $reviewMeta = [
    'waiting'  => ['label'=>'รอตรวจ','cls'=>'bg-amber-100 text-amber-700'],
    'approved' => ['label'=>'เสร็จสิ้น','cls'=>'bg-emerald-100 text-emerald-700'],
    'rework'   => ['label'=>'รอแก้ไข','cls'=>'bg-rose-100 text-rose-700'],
  ];

  $rows = tasks_review_list($filterReview, $filterDept);

  $decodeAttachments = function($raw) {
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
    return array_values(array_filter($parts, fn($u) => filter_var($u, FILTER_VALIDATE_URL)));
  };

  $renderFileChip = function($f) {
    if (is_string($f)) {
      $url = $f; $label = basename(parse_url($url, PHP_URL_PATH)) ?: 'เปิดไฟล์';
    } else {
      $url = $f['url'] ?? ($f['path'] ?? '');
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
?>

<h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
  <svg class="w-5 h-5 text-slate-700" viewBox="0 0 24 24" fill="none">
    <path d="M4 6h16M4 12h16M4 18h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
  </svg>
  แสดงผลการแจ้งงานและการส่งงานทั้งหมด
</h2>

<form method="get" class="flex flex-wrap items-center gap-3 mb-4">
  <input type="hidden" name="tab" value="all">
  <select name="dept" class="rounded-xl border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
    <option value="">ทุกฝ่าย</option>
    <?php foreach ($departments as $d): ?>
      <option value="<?= (int)$d['id'] ?>" <?= $filterDept===(int)$d['id']?'selected':'' ?>>
        <?= htmlspecialchars($d['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="r" class="rounded-xl border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
    <option value="">ทุกสถานะ</option>
    <?php foreach ($reviewMeta as $k=>$m): ?>
      <option value="<?= $k ?>" <?= $filterReview===$k?'selected':'' ?>><?= $m['label'] ?></option>
    <?php endforeach; ?>
  </select>

  <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 text-white px-4 py-2.5 hover:bg-blue-700">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10 18l6-6-6-6v12z"/></svg>
    กรอง
  </button>

  <?php if ($filterDept !== null || $filterReview !== null): ?>
    <a href="admin.php?tab=all" class="ml-1 text-slate-600 hover:underline">ล้างตัวกรอง</a>
  <?php endif; ?>
</form>

<div class="space-y-4">
<?php foreach ($rows as $r): ?>
<?php
  $revKey  = $r['review_status'] ?? 'waiting';
  $badge   = $reviewMeta[$revKey] ?? $reviewMeta['waiting'];
  $created = $r['created_at'] ? date('j F Y', strtotime($r['created_at'])) : '-';
  $due     = $r['due_date']   ? date('j F Y', strtotime($r['due_date']))   : '-';
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
    </div>
    <div class="space-y-2 text-sm text-slate-700">
      <div class="flex items-center gap-2">📅 วันที่สั่ง: <?= htmlspecialchars($created) ?></div>
      <div class="flex items-center gap-2">📅 กำหนดส่ง: <?= htmlspecialchars($due) ?></div>
      <div class="flex items-center gap-2">👤 ผู้รับผิดชอบ: <?= htmlspecialchars($r['assignee_name'] ?: '-') ?></div>
    </div>
  </div>

  <hr class="my-4">

  <?php if (!empty($taskFiles)): ?>
    <div class="mb-3">
      <div class="text-slate-700 font-medium mb-2">ไฟล์แนบของงาน</div>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($taskFiles as $f) echo $renderFileChip($f); ?>
      </div>
    </div>
  <?php endif; ?>

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

        <!-- <div class="mt-3 text-right">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm open-review"
            data-submission-id="<?= (int)$r['submission_id'] ?>"
            data-title="<?= htmlspecialchars($r['title'] ?? '', ENT_QUOTES) ?>"
            data-doc-type="<?= htmlspecialchars($r['doc_type'] ?? '', ENT_QUOTES) ?>"
            data-code-no="<?= htmlspecialchars($r['code_no'] ?? '', ENT_QUOTES) ?>"
            data-dept="<?= htmlspecialchars($r['department_name'] ?? '', ENT_QUOTES) ?>"
            data-sender="<?= htmlspecialchars($r['sender_name'] ?? '', ENT_QUOTES) ?>"
            data-sentat="<?= htmlspecialchars(date('j F Y เวลา H:i', strtotime($r['sent_at'])), ENT_QUOTES) ?>"
            data-content="<?= htmlspecialchars($r['content'] ?? '', ENT_QUOTES) ?>"
            data-review="<?= htmlspecialchars($revKey, ENT_QUOTES) ?>"
            data-score="<?= (int)($r['score'] ?? 0) ?>"
            data-comment="<?= htmlspecialchars($r['reviewer_comment'] ?? '', ENT_QUOTES) ?>"
          >🗂 ตรวจงาน</button>
        </div> -->
      </div>
    <?php else: ?>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-slate-600">
        ยังไม่มีการส่งงาน
      </div>
    <?php endif; ?>
  </div>
</article>
<?php endforeach; ?>

<?php if (empty($rows)): ?>
  <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 text-slate-600">
    ไม่พบรายการตามเงื่อนไขที่เลือก
  </div>
<?php endif; ?>
</div>

<?php elseif ($tab === 'summary'): ?>
<?php
  $sum    = task_summary_overall();
  $byDept = task_summary_by_department();
  $fmt = fn($n) => '<span class="text-xl font-bold">'.(int)$n.'</span>';
?>
<h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
  <svg class="w-5 h-5 text-slate-700" viewBox="0 0 24 24" fill="none">
    <path d="M4 6h16M4 12h16M4 18h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
  </svg>
  สรุปผล
</h2>

<div class="grid md:grid-cols-4 gap-4 mb-5">
  <div class="rounded-2xl bg-white/90 border border-slate-200 p-4 shadow-sm">
    <div class="flex items-center gap-2 text-slate-700 mb-2">
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      งานทั้งหมด
    </div>
    <div class="text-blue-700"><?= $fmt($sum['total']) ?></div>
  </div>
  <div class="rounded-2xl bg-white/90 border border-slate-200 p-4 shadow-sm">
    <div class="flex items-center gap-2 text-slate-700 mb-2">
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      เสร็จสิ้น
    </div>
    <div class="text-emerald-700"><?= $fmt($sum['done']) ?></div>
  </div>
  <div class="rounded-2xl bg-white/90 border border-slate-200 p-4 shadow-sm">
    <div class="flex items-center gap-2 text-slate-700 mb-2">
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      กำลังดำเนินการ
    </div>
    <div class="text-amber-700"><?= $fmt($sum['in_progress']) ?></div>
  </div>
  <div class="rounded-2xl bg-white/90 border border-slate-200 p-4 shadow-sm">
    <div class="flex items-center gap-2 text-slate-700 mb-2">
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      เลยกำหนด
    </div>
    <div class="text-rose-700"><?= $fmt($sum['overdue']) ?></div>
  </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
  <div class="px-4 py-3 border-b bg-slate-50 rounded-t-2xl font-semibold">สรุปตามฝ่ายงาน</div>
  <div class="divide-y">
    <?php foreach ($byDept as $d): ?>
      <div class="px-4 py-3 grid md:grid-cols-4 items-center gap-3">
        <div class="flex items-center gap-2 text-slate-800">
          <svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4h8v6H3zM13 4h8v6h-8zM3 14h8v6H3zM13 14h8v6h-8z"/></svg>
          <?= htmlspecialchars($d['name']) ?>
        </div>
        <div class="text-slate-700">
          <div class="text-xs text-slate-500">ทั้งหมด</div>
          <div class="text-blue-700"><?= (int)$d['total'] ?></div>
        </div>
        <div class="text-slate-700">
          <div class="text-xs text-slate-500">เสร็จสิ้น</div>
          <div class="text-emerald-700"><?= (int)$d['done'] ?></div>
        </div>
        <div class="grid grid-cols-2 gap-4 md:col-span-1">
          <div class="text-slate-700">
            <div class="text-xs text-slate-500">กำลังดำเนินการ</div>
            <div class="text-amber-700"><?= (int)$d['in_progress'] ?></div>
          </div>
          <div class="text-slate-700">
            <div class="text-xs text-slate-500">เลยกำหนด</div>
            <div class="text-rose-700"><?= (int)$d['overdue'] ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($byDept)): ?>
      <div class="px-4 py-6 text-slate-600">ยังไม่มีข้อมูลฝ่ายงาน</div>
    <?php endif; ?>
  </div>
</div>
<?php elseif ($tab === 'dashboard'): ?>
<?php
  $dashUsers = user_performance_summary();
?>

<h2 class="text-lg font-semibold mb-5 flex items-center gap-2">
  📊 Dashboard ประสิทธิภาพรายบุคคล
</h2>

<!-- GRID DASHBOARD -->
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">

<?php foreach ($dashUsers as $u): ?>
  <?php
    $perf = (int)$u['performance'];
    $barColor =
      $perf >= 80 ? 'bg-emerald-500' :
      ($perf >= 50 ? 'bg-amber-400' : 'bg-rose-500');
  ?>

  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5 hover:shadow-md transition">

    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <div>
        <div class="font-semibold text-slate-800">
          <?= htmlspecialchars($u['name']) ?>
        </div>
        <div class="text-xs text-slate-500">
          <?= htmlspecialchars($u['position'] ?? '-') ?>
        </div>
      </div>
      <div class="text-sm">
        <?= str_repeat('⭐', (int)$u['rating']) ?>
      </div>
    </div>

    <!-- Progress -->
    <div class="mb-3">
      <div class="flex justify-between text-xs text-slate-600 mb-1">
        <span>ประสิทธิภาพ</span>
        <span><?= $perf ?>%</span>
      </div>
      <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
        <div class="h-2 <?= $barColor ?>" style="width: <?= $perf ?>%"></div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-2 text-xs text-center">
      <div class="rounded-xl bg-slate-50 p-2">
        📌<div class="font-semibold"><?= $u['total_tasks'] ?></div>
        <div class="text-slate-500">ทั้งหมด</div>
      </div>
      <div class="rounded-xl bg-emerald-50 p-2">
        ✅<div class="font-semibold text-emerald-700"><?= $u['approved_tasks'] ?></div>
        <div class="text-emerald-600">ผ่าน</div>
      </div>
      <div class="rounded-xl bg-amber-50 p-2">
        ⏳<div class="font-semibold text-amber-700"><?= $u['waiting_tasks'] ?></div>
        <div class="text-amber-600">รอ</div>
      </div>
      <div class="rounded-xl bg-rose-50 p-2">
        🔁<div class="font-semibold text-rose-700"><?= $u['rework_tasks'] ?></div>
        <div class="text-rose-600">แก้ไข</div>
      </div>
    </div>

  </section>
<?php endforeach; ?>

<?php if (empty($dashUsers)): ?>
  <div class="col-span-full text-slate-500 italic">
    ยังไม่มีข้อมูลประสิทธิภาพผู้ใช้
  </div>
<?php endif; ?>

</div>

    <?php endif; ?>
    </div>
  </div>
</div>



<script>
function togglePassword() {
  const f = document.getElementById('passwordField');
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
