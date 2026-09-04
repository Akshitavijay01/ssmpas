-- ============================================================
-- SSMPAS - Student/Staff Management & Performance Analysis System
-- Complete Database Schema
-- Version: 2.0
-- Compatible with: MySQL 8.0+ / MariaDB 10.3+
-- ============================================================

-- Drop existing database if exists and create fresh
DROP DATABASE IF EXISTS `ssmpas`;
CREATE DATABASE `ssmpas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ssmpas`;

-- ============================================================
-- USERS TABLE - Unified authentication for all roles
-- ============================================================
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    `profile_photo` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB;

-- ============================================================
-- DEPARTMENTS TABLE
-- ============================================================
CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_dept_code` (`code`)
) ENGINE=InnoDB;

-- ============================================================
-- CLASSES TABLE
-- ============================================================
CREATE TABLE `classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `section` VARCHAR(20) DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_dept` (`department_id`),
    INDEX `idx_year` (`academic_year`),
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- STUDENTS TABLE - Core student records
-- ============================================================
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `roll_no` VARCHAR(20) DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `class_id` INT DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `gender` ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `admission_date` DATE DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_class` (`class_id`),
    INDEX `idx_dept` (`department_id`),
    INDEX `idx_roll_no` (`roll_no`),
    INDEX `idx_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TEACHERS TABLE - Core teacher records
-- ============================================================
CREATE TABLE `teachers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `employee_id` VARCHAR(20) DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `qualification` VARCHAR(100) DEFAULT NULL,
    `specialization` VARCHAR(150) DEFAULT NULL,
    `joining_date` DATE DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_dept` (`department_id`),
    INDEX `idx_user` (`user_id`),
    UNIQUE KEY `uk_employee_id` (`employee_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SUBJECTS TABLE
-- ============================================================
CREATE TABLE `subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `class_id` INT DEFAULT NULL,
    `teacher_id` INT DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `credits` INT DEFAULT 3,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_class` (`class_id`),
    INDEX `idx_teacher` (`teacher_id`),
    INDEX `idx_dept` (`department_id`),
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- EXAMS TABLE
-- ============================================================
CREATE TABLE `exams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('Quiz', 'Midterm', 'Final', 'Assignment', 'Practical', 'Viva') NOT NULL,
    `class_id` INT DEFAULT NULL,
    `subject_id` INT DEFAULT NULL,
    `total_marks` INT NOT NULL DEFAULT 100,
    `passing_marks` INT NOT NULL DEFAULT 40,
    `exam_date` DATE DEFAULT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_class` (`class_id`),
    INDEX `idx_subject` (`subject_id`),
    INDEX `idx_type` (`type`),
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- MARKS TABLE
-- ============================================================
CREATE TABLE `marks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `exam_id` INT DEFAULT NULL,
    `subject_id` INT DEFAULT NULL,
    `subject` VARCHAR(100) DEFAULT NULL,
    `marks_obtained` INT NOT NULL DEFAULT 0,
    `total_marks` INT NOT NULL DEFAULT 100,
    `grade` VARCHAR(5) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `entered_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_student` (`student_id`),
    INDEX `idx_exam` (`exam_id`),
    INDEX `idx_subject` (`subject_id`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- ATTENDANCE TABLE
-- ============================================================
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `subject_id` INT DEFAULT NULL,
    `status` ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL DEFAULT 'Present',
    `date` DATE NOT NULL,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `marked_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_attendance` (`student_id`, `date`, `subject_id`),
    INDEX `idx_student` (`student_id`),
    INDEX `idx_date` (`date`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`marked_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- RESULTS TABLE
-- ============================================================
CREATE TABLE `results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `exam_id` INT DEFAULT NULL,
    `class_id` INT DEFAULT NULL,
    `total_marks` INT NOT NULL DEFAULT 0,
    `marks_obtained` INT NOT NULL DEFAULT 0,
    `percentage` DECIMAL(5,2) DEFAULT 0.00,
    `grade` VARCHAR(5) DEFAULT NULL,
    `rank` INT DEFAULT NULL,
    `result_status` ENUM('Pass', 'Fail', 'Pending') NOT NULL DEFAULT 'Pending',
    `remarks` TEXT DEFAULT NULL,
    `published_by` INT DEFAULT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `published_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_student` (`student_id`),
    INDEX `idx_exam` (`exam_id`),
    INDEX `idx_class` (`class_id`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`published_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'warning', 'success', 'danger') NOT NULL DEFAULT 'info',
    `target_role` ENUM('all', 'admin', 'teacher', 'student') NOT NULL DEFAULT 'all',
    `target_user_id` INT DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_target_role` (`target_role`),
    INDEX `idx_target_user` (`target_user_id`),
    INDEX `idx_is_read` (`is_read`),
    FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- ACTIVITY_LOGS TABLE
-- ============================================================
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SETTINGS TABLE
-- ============================================================
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` VARCHAR(20) DEFAULT 'text',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_setting_key` (`setting_key`),
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- INSERT DEFAULT DATA
-- ============================================================

-- Default admin account (password: Admin@123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('System Administrator', 'admin@ssmpas.com', '$2b$10$VtzkUJHLgvcFBEcmFl56o.uC6nsOz5SYA6KWDgBXY9Ud2IIkQ0FzW', 'admin');

-- Default teacher account (password: Teacher@123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Dr. Sarah Johnson', 'teacher@ssmpas.com', '$2b$10$TtkG1Sr1X5c6lHsjrGt6euOeP8vj/tZ4QVjWOup38EtxHV7YknkwG', 'teacher');

-- Default student account (password: Student@123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Rahul Sharma', 'student@ssmpas.com', '$2b$10$jVafzMhEWejO87C0Jy8lvOQKLZYfumcLrU9crX6BM6g72n7UujC0a', 'student');

-- Default departments
INSERT INTO `departments` (`name`, `code`, `description`) VALUES
('Computer Science', 'CS', 'Department of Computer Science and Engineering'),
('Information Technology', 'IT', 'Department of Information Technology'),
('Electronics', 'EC', 'Department of Electronics and Communication'),
('Mechanical', 'ME', 'Department of Mechanical Engineering'),
('Civil Engineering', 'CE', 'Department of Civil Engineering');

-- Default classes
INSERT INTO `classes` (`name`, `section`, `department_id`, `academic_year`) VALUES
('First Year', 'A', 1, '2025-2026'),
('First Year', 'B', 1, '2025-2026'),
('Second Year', 'A', 1, '2025-2026'),
('Second Year', 'B', 1, '2025-2026'),
('Third Year', 'A', 1, '2025-2026'),
('Third Year', 'B', 1, '2025-2026'),
('First Year', 'A', 2, '2025-2026'),
('Second Year', 'A', 2, '2025-2026');

-- Default subjects
INSERT INTO `subjects` (`name`, `code`, `class_id`, `teacher_id`, `department_id`, `credits`) VALUES
('Introduction to Programming', 'CS101', 1, NULL, 1, 4),
('Data Structures', 'CS201', 3, NULL, 1, 4),
('Database Management', 'CS301', 5, NULL, 1, 3),
('Operating Systems', 'CS302', 5, NULL, 1, 3),
('Computer Networks', 'CS303', 5, NULL, 1, 3),
('Web Development', 'IT101', 7, NULL, 2, 3),
('Software Engineering', 'IT201', 8, NULL, 2, 3);

-- Default teachers
INSERT INTO `teachers` (`user_id`, `employee_id`, `name`, `email`, `department_id`, `qualification`, `specialization`) VALUES
(2, 'EMP001', 'Dr. Sarah Johnson', 'teacher@ssmpas.com', 1, 'Ph.D Computer Science', 'Data Structures & Algorithms'),
(2, 'EMP002', 'Prof. Michael Chen', 'michael.chen@ssmpas.com', 1, 'M.Tech', 'Database Systems'),
(2, 'EMP003', 'Dr. Emily Williams', 'emily.williams@ssmpas.com', 2, 'Ph.D IT', 'Web Technologies');

-- Update subjects with teacher_id
UPDATE `subjects` SET `teacher_id` = 1 WHERE `code` = 'CS101';
UPDATE `subjects` SET `teacher_id` = 2 WHERE `code` = 'CS201';
UPDATE `subjects` SET `teacher_id` = 2 WHERE `code` = 'CS301';
UPDATE `subjects` SET `teacher_id` = 1 WHERE `code` = 'CS302';
UPDATE `subjects` SET `teacher_id` = 1 WHERE `code` = 'CS303';
UPDATE `subjects` SET `teacher_id` = 3 WHERE `code` = 'IT101';
UPDATE `subjects` SET `teacher_id` = 3 WHERE `code` = 'IT201';

-- Link student user to student record
INSERT INTO `students` (`user_id`, `roll_no`, `name`, `email`, `class_id`, `department_id`, `gender`) VALUES
(3, 'CS2025001', 'Rahul Sharma', 'student@ssmpas.com', 3, 1, 'Male');

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('school_name', 'SSMPAS University', 'text', 'Institution name'),
('academic_year', '2025-2026', 'text', 'Current academic year'),
('attendance_warning_threshold', '75', 'number', 'Minimum attendance percentage'),
('max_marks', '100', 'number', 'Default maximum marks per exam'),
('passing_percentage', '40', 'number', 'Minimum passing percentage');

-- Sample notifications
INSERT INTO `notifications` (`title`, `message`, `type`, `target_role`, `created_by`) VALUES
('Welcome to SSMPAS', 'Welcome to the Student Management and Performance Analysis System. Please update your profile.', 'info', 'all', 1),
('Midterm Schedule Released', 'Midterm examination schedule has been published. Check your dashboard for details.', 'warning', 'student', 1),
('System Update', 'System has been upgraded to version 2.0 with enhanced features.', 'success', 'all', 1);
