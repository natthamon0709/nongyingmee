<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

error_reporting(E_ALL); ini_set('display_errors', '1');

/**
 * ตัวช่วย: มีคอลัมน์ไหม (ยังเก็บไว้ใช้ฝั่ง teacher ถ้าต้องเช็ค active)
 */
function column_exists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

/**
 * ยืนยันผู้ใช้
 * - admin:    แค่กรอกรหัส "Admin@12345" ถูก → เข้าได้เลย (ไม่เช็ค DB)
 * - reporter: แค่กรอกรหัส "Report@12345" ถูก → เข้าได้เลย (ไม่เช็ค DB)
 * - teacher:  เลือกครู + ตรวจรหัสผ่านกับ DB ตามปกติ
 *
 * หมายเหตุ: โหมดนี้เหมาะทดสอบเท่านั้น โปรดปิด MASTER เมื่อขึ้นโปรดักชัน
 */
function attempt_login(string $userType, ?string $password, ?int $teacherId): ?array
{
    $pdo = db(); // ใช้กับ teacher; admin/report ไม่แตะ DB

    // MASTER PASSWORD เฉพาะช่วงทดสอบ
    $MASTER = [
        'admin'    => 'Admin@12345',
        'reporter' => 'Report@12345',
    ];

    // ---- admin / reporter: ใช้รหัสคงที่อย่างเดียว ----
    if ($userType === 'admin' || $userType === 'reporter') {
        if (!$password) return null;

        // ถ้ารหัสตรงกับ master ของบทบาทนั้น → คืน user จำลอง แล้วให้ผ่านทันที
        if (isset($MASTER[$userType]) && hash_equals($MASTER[$userType], (string)$password)) {
            // user จำลองขั้นต่ำให้ระบบใช้งานต่อได้
            return [
                'id'       => 0,                                    // ไอดีจำลอง
                'name'     => ($userType === 'admin') ? 'ผู้ดูแลระบบ' : 'เจ้าหน้าที่แจ้งงาน',
                'email'    => null,
                'role'     => $userType,                            // สำคัญ: ใช้สำหรับ redirect / require_auth
                'rating'   => 0,
                // จะเพิ่มฟิลด์อื่น ๆ ที่ระบบใช้ก็ได้ เช่น position/status เป็นต้น
            ];
        }

        // รหัสไม่ตรง → ไม่อนุญาต (ไม่ตรวจ DB)
        start_session();
        $_SESSION['flash_error'] = 'รหัสผ่านไม่ถูกต้อง';
        return null;
    }

    // ---- teacher: ตรวจรหัสกับ DB ตามปกติ ----
    if ($userType === 'teacher') {
        if (!$teacherId || !$password) return null;

        // เงื่อนไข active ที่ทนหลายสคีม่า (ถ้ามีคอลัมน์พวกนี้)
        $activeClause = '';
        if (column_exists($pdo, 'users', 'account_status')) {
            $activeClause = " AND account_status = 'active' ";
        } elseif (column_exists($pdo, 'users', 'is_active')) {
            $activeClause = " AND is_active = 1 ";
        }

        $sql = "SELECT * FROM users WHERE id = ? AND role = 'teacher' {$activeClause} LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$teacherId]);
        $user = $stmt->fetch();

        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }

        start_session();
        $_SESSION['flash_error'] = 'รหัสผ่านไม่ถูกต้อง หรือผู้ใช้ครูถูกปิดใช้งาน';
        return null;
    }

    return null;
}
