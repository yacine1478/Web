-- Create a simple database schema for the grade program
-- RESET DATABASE
DROP DATABASE IF EXISTS lab3;
CREATE DATABASE lab3;
USE lab3;

-- USERS (ALL ROLES)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','professor','student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SEMESTERS
CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(20) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- COURSES (LINKED TO SEMESTER)
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    credits INT NOT NULL CHECK (credits > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

-- ENROLLMENTS (STUDENT ↔ SEMESTER)
CREATE TABLE enrollments (
    student_id INT NOT NULL,
    semester_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, semester_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

-- ASSIGNMENTS (PROFESSOR ↔ COURSE ↔ SEMESTER)
CREATE TABLE assignments (
    professor_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (professor_id, course_id, semester_id),
    FOREIGN KEY (professor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

-- GRADES (stores numeric 0.0-4.0)
CREATE TABLE grades (
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    professor_id INT NOT NULL,
    grade DECIMAL(3,1) NOT NULL CHECK (grade BETWEEN 0.0 AND 4.0),
    entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, course_id, semester_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- GPA RECORDS
CREATE TABLE gpa_records (
    student_id INT NOT NULL,
    semester_id INT NOT NULL,
    gpa DECIMAL(4,2) NOT NULL,
    computed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, semester_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

-- =========================
-- TEST DATA
-- =========================

-- USERS (password = 'password123' hashed with BCRYPT)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr. Smith', 'prof@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professor'),
('Alice Johnson', 'student@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Bob Williams', 'bob@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- SEMESTERS
INSERT INTO semesters (label, academic_year, is_active) VALUES
('Fall', '2024-2025', TRUE),
('Spring', '2024-2025', FALSE);

-- COURSES
INSERT INTO courses (semester_id, name, credits) VALUES
(1, 'Mathematics', 3),
(1, 'Physics', 4),
(1, 'Computer Science', 3),
(2, 'English Literature', 3),
(2, 'History', 3);

-- ENROLL STUDENTS
INSERT INTO enrollments (student_id, semester_id) VALUES
(3, 1),
(3, 2),
(4, 1);

-- ASSIGN PROFESSORS
INSERT INTO assignments (professor_id, course_id, semester_id) VALUES
(2, 1, 1),
(2, 2, 1),
(2, 3, 1),
(2, 4, 2),
(2, 5, 2);