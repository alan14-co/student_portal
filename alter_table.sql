-- Run this if you already imported the OLD database.sql
USE student_portal;

-- Remove old security question columns (if they exist)
ALTER TABLE students
  DROP COLUMN IF EXISTS security_question,
  DROP COLUMN IF EXISTS security_answer;

-- Add new student columns
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS password_changed_by_admin TINYINT(1) DEFAULT 0 AFTER status,
  ADD COLUMN IF NOT EXISTS temp_password VARCHAR(255) DEFAULT NULL AFTER password_changed_by_admin;

-- Token-based auth has been removed in favor of session-only auth
DROP TABLE IF EXISTS user_tokens;

-- OTP codes table
CREATE TABLE IF NOT EXISTS otp_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) NOT NULL,
  otp VARCHAR(6) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Notices table
CREATE TABLE IF NOT EXISTS notices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  type ENUM('public','specific') DEFAULT 'public',
  student_id INT DEFAULT NULL,
  priority ENUM('normal','important','urgent') DEFAULT 'normal',
  is_active TINYINT(1) DEFAULT 1,
  created_by INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME DEFAULT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE
);
