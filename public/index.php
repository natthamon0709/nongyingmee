<?php require_once __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../src/TeacherRepository.php'; ?>
<?php $teachers = teacher_list(); ?>

<div class="min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-md rounded-2xl shadow-xl border border-blue-100/40 bg-white/80 backdrop-blur">
    <div class="h-2 w-full rounded-t-2xl bg-gradient-to-r from-blue-200 via-blue-300 to-yellow-200"></div>

    <div class="p-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="h-12 w-12 rounded-xl bg-blue-100 grid place-items-center">
          <img
            src="assets/logo-school.png"
            alt="โลโก้โรงเรียนบ้านหนองยิงหมี"
            class="w-6 h-6 object-contain rounded-full bg-white border border-white shadow"
          />
        </div>
        <div>
          <h1 class="text-xl font-bold text-slate-800">ระบบติดตามงานราชการ</h1>
          <p class="text-slate-500 text-sm">โรงเรียนบ้านหนองยิงหมี • สพป.ประจวบคีรีขันธ์ เขต 2</p>
        </div>
      </div>

      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 px-3 py-2 text-sm">
          <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
      <?php endif; ?>

      <form action="login.php" method="post" id="loginForm" class="space-y-5">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">

        <!-- ประเภทผู้ใช้งาน -->
        <div class="space-y-1.5">
          <label for="user_type" class="block text-sm font-medium text-slate-700">ประเภทผู้ใช้งาน</label>
          <select id="user_type" name="user_type"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-800 shadow-sm
                   focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition">
            <option value="admin">ผู้ดูแลระบบ (admin)</option>
            <option value="reporter">แจ้งงาน</option>
            <option value="teacher">ครู</option>
          </select>
          <p class="text-xs text-slate-500">admin/แจ้งงาน: เลือกบทบาทแล้วใส่รหัสผ่าน • ครู: เลือกชื่อครูแล้วใส่รหัสผ่าน</p>
        </div>

        <!-- เลือกชื่อครู (แสดงเมื่อเลือกครู) -->
        <div id="teacherWrap" class="hidden space-y-1.5">
          <label for="teacher_id" class="block text-sm font-medium text-slate-700">เลือกชื่อครู</label>
          <select id="teacher_id" name="teacher_id"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-800 shadow-sm
                   focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition">
            <option value="">— กรุณาเลือก —</option>
            <?php foreach($teachers as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- รหัสผ่าน -->
        <div class="space-y-1.5">
          <label for="password" class="block text-sm font-medium text-slate-700">รหัสผ่าน</label>
          <div class="relative">
            <input id="password" type="password" name="password" placeholder="••••••••"
              class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 pr-10 text-slate-800 shadow-sm
                     focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition">
            <button type="button" id="togglePw"
              class="absolute inset-y-0 right-0 px-3 grid place-items-center text-slate-500 hover:text-slate-700"
              aria-label="สลับการแสดงรหัสผ่าน">
              <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24">
                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.5"/>
                <path d="M6.2 6.2C3.76 7.78 2 12 2 12s3.5 7 10 7a10.9 10.9 0 005.8-1.69M14.12 9.88A3 3 0 009.88 14.12" stroke="currentColor" stroke-width="1.5"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- ปุ่ม -->
        <button
          class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-500 px-4 py-3
                 text-white font-semibold shadow hover:bg-blue-600 active:bg-blue-700
                 focus:outline-none focus:ring-2 focus:ring-blue-300 transition">
          เข้าสู่ระบบ
        </button>
      </form>
    </div>

    <!-- <div class="px-6 py-4 bg-yellow-100/60 rounded-b-2xl text-sm text-slate-700">
      <span class="font-semibold">ธีมสี:</span> ฟ้า–เหลืองพาสเทล • ปุ่ม/ช่องกรอกมาตรฐาน ลดความฟุ้ง เน้นความชัดเจน
    </div> -->
  </div>
</div>

<script>
  const userTypeEl   = document.getElementById('user_type');
  const teacherWrap  = document.getElementById('teacherWrap');

  function toggleFields() {
    const v = userTypeEl.value;
    teacherWrap.classList.toggle('hidden', v !== 'teacher');
  }
  userTypeEl.addEventListener('change', toggleFields);
  toggleFields();

  // toggle password visibility
  const pw = document.getElementById('password');
  const toggle = document.getElementById('togglePw');
  const eyeOpen = document.getElementById('eyeOpen');
  const eyeClosed = document.getElementById('eyeClosed');
  toggle.addEventListener('click', () => {
    const isPwd = pw.type === 'password';
    pw.type = isPwd ? 'text' : 'password';
    eyeOpen.classList.toggle('hidden', !isPwd);
    eyeClosed.classList.toggle('hidden', isPwd);
  });
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
