
```
admin = Admin@12345
เจ้าหน้าที่แจ้งงาน = Report@12345
ครู = Teacher@12345

---


# .env.example


```
APP_ENV=local
APP_URL=http://localhost/GovTaskTracker/public


DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=work
DB_USER=root
DB_PASS=

### 1) ติดตั้ง
- PHP 8.1+, MySQL 8+, Apache/Nginx
- สร้างฐานข้อมูลด้วย `database.sql`
- คัดลอก `.env.example` เป็น `.env` แล้วแก้ค่าตามเครื่อง
- ตั้ง DocumentRoot ไปที่โฟลเดอร์ `public/`


### 2) Tailwind (CDN)
สำหรับ PoC นี้ใช้ Tailwind CDN เพื่อความรวดเร็ว หากจะไป production ควร build Tailwind จริงเพื่อ tree-shake class และกำหนด design token ชัดเจน


### 3) Login flow
- เลือกประเภทผู้ใช้
- **ผู้ดูแลระบบ / แจ้งงาน**: กรอก Email + Password
- **ครู**: เลือกชื่อจาก Dropdown + Password
- ตรวจสอบด้วย `attempt_login()` และจัดเก็บ session ใน `$_SESSION['user']`


### 4) ความปลอดภัยเบื้องต้น
- CSRF token ในแบบฟอร์ม
- ใช้ `password_hash/password_verify`
- PDO prepared statement ทุกจุด
- แยกไฟล์ ENV, config, db


### 5) ต่อขยายระบบ
- เพิ่มตาราง `tasks`, `departments`, `task_statuses`, `attachments` ฯลฯ
- ทำ RBAC ด้วย middleware `require_auth(['admin


admin = Admin@12345
เจ้าหน้าที่แจ้งงาน = Report@12345
ครู = Teacher@12345

# nongyingmee
