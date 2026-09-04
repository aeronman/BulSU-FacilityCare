-- ============================================================
-- BulSU FacilityCare Database Schema
-- Facility Maintenance Reporting & Risk Prioritization System
-- ============================================================

CREATE DATABASE IF NOT EXISTS bulsu_facilitycare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bulsu_facilitycare;

-- -------------------------------------------------
-- Roles: student_staff, maintenance, admin
-- -------------------------------------------------
CREATE TABLE roles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50)  NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Departments
-- -------------------------------------------------
CREATE TABLE departments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL UNIQUE,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Users
-- -------------------------------------------------
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    role_id         INT NOT NULL,
    department_id   INT,
    employee_id     VARCHAR(20) UNIQUE,
    student_id      VARCHAR(20) UNIQUE,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20),
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id)       REFERENCES roles (id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Issue Categories (with criticality weights for priority)
-- -------------------------------------------------
CREATE TABLE categories (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    description     TEXT,
    criticality_weight DECIMAL(3,2) DEFAULT 1.00 COMMENT 'Weight multiplier for priority score',
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Facilities / Locations (buildings, floors, rooms)
-- -------------------------------------------------
CREATE TABLE facilities (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    building        VARCHAR(100) NOT NULL,
    floor           VARCHAR(50),
    room_number     VARCHAR(50),
    location_name   VARCHAR(100) NOT NULL,
    description     TEXT,
    criticality_weight DECIMAL(3,2) DEFAULT 1.00 COMMENT 'Weight multiplier for priority score',
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_building (building)
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Report Statuses
-- -------------------------------------------------
CREATE TABLE report_statuses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30) NOT NULL UNIQUE,
    label       VARCHAR(50) NOT NULL,
    display_order INT DEFAULT 0,
    color_class VARCHAR(30) DEFAULT 'secondary',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Reports (core table)
-- -------------------------------------------------
CREATE TABLE reports (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    report_number   VARCHAR(20) NOT NULL UNIQUE,
    title           VARCHAR(200) NOT NULL,
    description     TEXT NOT NULL,
    category_id     INT NOT NULL,
    facility_id     INT NOT NULL,
    urgency         ENUM('low','medium','high') DEFAULT 'medium',
    safety_risk     ENUM('no','minor','moderate','severe') DEFAULT 'no',
    severity        ENUM('minor','moderate','major','critical') DEFAULT 'moderate',
    affected_users  VARCHAR(200),
    photo_path      VARCHAR(500),
    additional_info TEXT,
    status_code     VARCHAR(30) NOT NULL DEFAULT 'submitted',
    reporter_id     INT,
    assigned_to     INT,
    priority_score  DECIMAL(5,2) DEFAULT 0.00,
    priority_level  ENUM('low','medium','high') DEFAULT 'low',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at     TIMESTAMP NULL,
    FOREIGN KEY (category_id)   REFERENCES categories (id),
    FOREIGN KEY (facility_id)   REFERENCES facilities (id),
    FOREIGN KEY (reporter_id)   REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to)   REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (status_code)   REFERENCES report_statuses (code)
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Priority Scores (multi-criteria breakdown)
-- -------------------------------------------------
CREATE TABLE priority_scores (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    report_id           INT NOT NULL,
    safety_risk_score   DECIMAL(5,2) DEFAULT 0.00,
    severity_score      DECIMAL(5,2) DEFAULT 0.00,
    urgency_score       DECIMAL(5,2) DEFAULT 0.00,
    location_score      DECIMAL(5,2) DEFAULT 0.00,
    operations_score    DECIMAL(5,2) DEFAULT 0.00,
    frequency_score     DECIMAL(5,2) DEFAULT 0.00,
    category_score      DECIMAL(5,2) DEFAULT 0.00,
    total_score         DECIMAL(5,2) DEFAULT 0.00,
    priority_level      ENUM('low','medium','high') DEFAULT 'low',
    assessed_by         INT,
    assessed_at         TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id)   REFERENCES reports (id) ON DELETE CASCADE,
    FOREIGN KEY (assessed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Report Status History
-- -------------------------------------------------
CREATE TABLE report_history (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    report_id   INT NOT NULL,
    status_code VARCHAR(30) NOT NULL,
    changed_by  INT,
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id)   REFERENCES reports (id) ON DELETE CASCADE,
    FOREIGN KEY (status_code) REFERENCES report_statuses (code),
    FOREIGN KEY (changed_by)  REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Report Comments / Replies (internal & external)
-- -------------------------------------------------
CREATE TABLE comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    report_id   INT NOT NULL,
    user_id     INT NOT NULL,
    message     TEXT NOT NULL,
    is_internal TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Duplicate Report Detection
-- -------------------------------------------------
CREATE TABLE duplicate_reports (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    report_id       INT NOT NULL,
    original_report_id INT NOT NULL,
    similarity_score DECIMAL(5,2) DEFAULT 0.00,
    is_merged       TINYINT(1) DEFAULT 0,
    reviewed_by     INT,
    reviewed_at     TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id)          REFERENCES reports (id) ON DELETE CASCADE,
    FOREIGN KEY (original_report_id) REFERENCES reports (id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by)        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Duplicate Detection Thresholds (configurable)
-- -------------------------------------------------
CREATE TABLE duplicate_thresholds (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    field_name      VARCHAR(50) NOT NULL,
    weight          DECIMAL(4,2) DEFAULT 1.00,
    min_similarity  DECIMAL(5,2) DEFAULT 0.70,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Maintenance Assignments & Updates
-- -------------------------------------------------
CREATE TABLE maintenance_updates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    report_id       INT NOT NULL,
    updated_by      INT NOT NULL,
    status_code     VARCHAR(30),
    work_notes      TEXT,
    materials_used  TEXT,
    time_spent      VARCHAR(50),
    photo_path      VARCHAR(500),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id)   REFERENCES reports (id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by)  REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (status_code) REFERENCES report_statuses (code)
) ENGINE=InnoDB;

-- -------------------------------------------------
-- Notifications
-- -------------------------------------------------
CREATE TABLE notifications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    report_id       INT,
    title           VARCHAR(200) NOT NULL,
    message         TEXT NOT NULL,
    type            VARCHAR(50),
    is_read         TINYINT(1) DEFAULT 0,
    url             VARCHAR(500),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES reports (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- System Settings
-- -------------------------------------------------
CREATE TABLE settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    setting_type VARCHAR(20) DEFAULT 'string',
    is_public   TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
