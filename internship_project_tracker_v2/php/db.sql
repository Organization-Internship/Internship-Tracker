-- DB
CREATE DATABASE IF NOT EXISTS internship_tracker DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE internship_tracker;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','faculty','company') NOT NULL,
  resume_path VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  phone VARCHAR(30) DEFAULT NULL,
  year VARCHAR(20) DEFAULT NULL,
  branch VARCHAR(50) DEFAULT NULL,
  linkedin VARCHAR(255) DEFAULT NULL,
  github VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS faculty (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  department VARCHAR(100) DEFAULT NULL,
  designation VARCHAR(100) DEFAULT '',
  contact_info VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  company_name VARCHAR(150) DEFAULT 'Company',
  website VARCHAR(255) DEFAULT NULL,
  contact_info VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mentorship (
  id INT AUTO_INCREMENT PRIMARY KEY,
  faculty_user_id INT NOT NULL,
  student_user_id INT NOT NULL,
  FOREIGN KEY (faculty_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS internships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  posted_by_user_id INT NOT NULL,
  kind ENUM('manual','ai') DEFAULT 'manual',
  stipend VARCHAR(100) DEFAULT NULL,
  duration VARCHAR(100) DEFAULT NULL,
  skills_required TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  internship_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('submitted','reviewing','selected','rejected','in-progress','completed') DEFAULT 'submitted',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  tech_stack VARCHAR(255) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('in-progress','completed') DEFAULT 'in-progress',
  project_link VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS project_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  token VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Demo users (password: password123)
INSERT INTO users (name,email,password_hash,role) VALUES
('Alice Student','student@example.com', '$2y$10$V0kM3p3rA3KcJxqNcoYfXOiB5N4XyB6gspL8w6s8bZpK1mCMV0QqS', 'student'),
('Bob Faculty','faculty@example.com', '$2y$10$V0kM3p3rA3KcJxqNcoYfXOiB5N4XyB6gspL8w6s8bZpK1mCMV0QqS', 'faculty'),
('Com Co','company@example.com', '$2y$10$V0kM3p3rA3KcJxqNcoYfXOiB5N4XyB6gspL8w6s8bZpK1mCMV0QqS', 'company');

INSERT INTO students(user_id) SELECT id FROM users WHERE role='student' AND id NOT IN (SELECT user_id FROM students);
INSERT INTO faculty(user_id)   SELECT id FROM users WHERE role='faculty' AND id NOT IN (SELECT user_id FROM faculty);
INSERT INTO companies(user_id) SELECT id FROM users WHERE role='company' AND id NOT IN (SELECT user_id FROM companies);

INSERT INTO mentorship(faculty_user_id, student_user_id)
SELECT (SELECT id FROM users WHERE email='faculty@example.com'),
       (SELECT id FROM users WHERE email='student@example.com')
WHERE NOT EXISTS (SELECT 1 FROM mentorship WHERE faculty_user_id=(SELECT id FROM users WHERE email='faculty@example.com') AND student_user_id=(SELECT id FROM users WHERE email='student@example.com'));
