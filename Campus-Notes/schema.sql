-- Campus Notes (repaired) - disposable local lab database only.
CREATE DATABASE IF NOT EXISTS notes_lab CHARACTER SET utf8mb4;
USE notes_lab;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(254) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,           -- password_hash() output, never MD5
  role VARCHAR(20) NOT NULL DEFAULT 'student',
  CONSTRAINT chk_users_role CHECK (role IN ('student', 'admin'))
);

CREATE TABLE notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  body TEXT NOT NULL,
  INDEX idx_notes_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Synthetic accounts for local use only (same public throwaway passwords as the
-- starter README). Hashes are bcrypt from PHP password_hash(PASSWORD_DEFAULT).
-- If you imported the old MD5 schema instead, run: php tools/reset_passwords.php
INSERT INTO users (id, email, password_hash, role) VALUES
  (1, 'alex@example.test', '$2y$10$axGMYqoXKSlBBS5YnetYMOTElZ/keaqr.GwboF3IdIdxqJXNyk9JS', 'student'),
  (2, 'sam@example.test',  '$2y$10$QXCgk84EhW.MUilZsGzNSOxobTIIiXwI2awDrqi06dOn7LrhbpOgW', 'student');

INSERT INTO notes (user_id, title, body) VALUES
  (1, 'Alex: course reminder', 'Review the security reading before class.'),
  (1, 'Alex: project idea', 'Build a small student directory.'),
  (2, 'Sam: study plan', 'Compare authentication and authorization.'),
  (2, 'Sam: meeting', 'Prepare questions for the next group discussion.');
