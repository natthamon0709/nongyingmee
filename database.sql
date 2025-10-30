-- 1) ตำแหน่ง
CREATE TABLE IF NOT EXISTS positions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  sort_order INT UNSIGNED DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO positions (name, sort_order) VALUES
('ผู้อำนวยการสถานศึกษา',1),
('รองผู้อำนวยการสถานศึกษา',2),
('ครู',3),
('ครูผู้ช่วย',4);

-- 2) วิทยฐานะ (ranks)
CREATE TABLE IF NOT EXISTS ranks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  sort_order INT UNSIGNED DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO ranks (name, sort_order) VALUES
('ครูชำนาญการ',1),
('ครูชำนาญการพิเศษ',2),
('ครูเชี่ยวชาญ',3),
('ครูเชี่ยวชาญพิเศษ',4);

-- 3) สถานะการจ้างงาน (employment_statuses)
CREATE TABLE IF NOT EXISTS employment_statuses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  sort_order INT UNSIGNED DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO employment_statuses (name, sort_order) VALUES
('ครู',1),
('ข้าราชการ',2),
('ลูกจ้างประจำ',3),
('พนักงานราชการ',4);

-- 4) ฝ่ายงาน (departments)
CREATE TABLE IF NOT EXISTS departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  sort_order INT UNSIGNED DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO departments (name, sort_order) VALUES
('วิชาการ',1),
('งบประมาณ',2),
('บริหารทั่วไป',3),
('บุคลากร',4),
('กิจการนักเรียน',5);

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) DEFAULT NULL,
  position_id INT UNSIGNED DEFAULT NULL,
  status_id INT UNSIGNED DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  rating TINYINT DEFAULT 0,
  role ENUM('admin','reporter','teacher') DEFAULT 'teacher',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
  FOREIGN KEY (status_id) REFERENCES employment_statuses(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  doc_type VARCHAR(100) DEFAULT NULL,     -- ประเภทเอกสาร
  code_no  VARCHAR(100) DEFAULT NULL,     -- เลขหนังสือ/เลขที่งาน
  department_id INT UNSIGNED DEFAULT NULL,
  assignee_name VARCHAR(120) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  due_date   DATE DEFAULT NULL,
  status ENUM('pending','in_progress','done','overdue','cancelled') DEFAULT 'pending',
  attachments JSON DEFAULT NULL,
  note TEXT NULL,
  CONSTRAINT fk_tasks_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ตารางงาน (มีอยู่แล้วข้ามได้)
CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  doc_type VARCHAR(100) DEFAULT NULL,
  code_no  VARCHAR(100) DEFAULT NULL,
  department_id INT UNSIGNED DEFAULT NULL,
  assignee_name VARCHAR(120) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  due_date   DATE DEFAULT NULL,
  status ENUM('pending','in_progress','done','overdue','cancelled') DEFAULT 'pending',
  attachments JSON DEFAULT NULL,
  note TEXT NULL
) ENGINE=InnoDB;

-- รายการส่งงานของแต่ละงาน
CREATE TABLE IF NOT EXISTS task_submissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id INT UNSIGNED NOT NULL,
  sender_name VARCHAR(120) NOT NULL,
  content TEXT DEFAULT NULL,
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  -- ผลการตรวจ (ผู้ตรวจจะกรอกทีหลัง)
  review_status ENUM('waiting','approved','rework') DEFAULT 'waiting',
  score TINYINT UNSIGNED DEFAULT NULL,  -- 1-10
  reviewer_comment TEXT DEFAULT NULL,
  reviewed_at DATETIME DEFAULT NULL,

  CONSTRAINT fk_sub_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB;
