-- sql/schema.sql
-- Training Enrollment System: database schema + sample data.
-- Run this once in phpMyAdmin (Import tab) or the MySQL command line.

CREATE DATABASE IF NOT EXISTS training_db;
USE training_db;

-- Remove old tables first so this script can be run again safely.
-- (Child tables are dropped before the tables they point to.)
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100),
    phone      VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    course_id   INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE classes (
    class_id   INT AUTO_INCREMENT PRIMARY KEY,
    course_id  INT NOT NULL,
    class_code VARCHAR(20) NOT NULL,
    schedule   VARCHAR(100),
    instructor VARCHAR(100),
    slots      INT NOT NULL DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

CREATE TABLE enrollments (
    enrollment_id   INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT NOT NULL,
    class_id        INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status          VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (class_id)   REFERENCES classes(class_id)
);

-- ---------------------------------------------------------------
-- Sample data
-- ---------------------------------------------------------------

INSERT INTO courses (course_id, course_code, course_name, description) VALUES
(1, 'WEB101', 'Web Development Fundamentals', 'HTML, CSS and basic PHP.'),
(2, 'DB101',  'Database Design Basics',       'Tables, keys and SQL queries.'),
(3, 'NET101', 'Networking Essentials',        'IP addressing and basic network setup.');

-- "slots" means the number of slots still available.
-- Class WEB101-B is already FULL (0 slots). Use it to test the rollback case.
INSERT INTO classes (class_id, course_id, class_code, schedule, instructor, slots) VALUES
(1, 1, 'WEB101-A', 'Mon/Wed 9:00-11:00 AM', 'Maria Santos',  5),
(2, 1, 'WEB101-B', 'Tue/Thu 1:00-3:00 PM',  'Jose Reyes',    0),
(3, 2, 'DB101-A',  'Fri 9:00 AM-12:00 PM',  'Ana Cruz',      3),
(4, 3, 'NET101-A', 'Sat 8:00 AM-12:00 PM',  'Ramon Garcia', 10);

INSERT INTO students (student_id, full_name, email, phone) VALUES
(1, 'Juan Dela Cruz', 'juan@example.com',  '09171234567'),
(2, 'Liza Mendoza',   'liza@example.com',  '09181234567'),
(3, 'Mark Villanueva', 'mark@example.com', '09191234567');

-- Juan and Liza took the only two slots of WEB101-B. Mark is in DB101-A.
INSERT INTO enrollments (enrollment_id, student_id, class_id, status) VALUES
(1, 1, 2, 'active'),
(2, 2, 2, 'active'),
(3, 3, 3, 'active');
