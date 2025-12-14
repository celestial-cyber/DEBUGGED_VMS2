-- =====================================================
-- Visitor Management System - Complete Database Schema
-- =====================================================
-- This file contains the complete database schema for the VMS application.
-- It consolidates all SQL files into a single, organized schema definition.
-- =====================================================

-- Drop and recreate database (use with caution in production)
DROP DATABASE IF EXISTS vms_db;
CREATE DATABASE vms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vms_db;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Admin table
CREATE TABLE IF NOT EXISTS vms_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(100) NOT NULL,
  emailid VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Members table
CREATE TABLE IF NOT EXISTS vms_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_name VARCHAR(100) NOT NULL,
  emailid VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  department VARCHAR(100) NULL,
  graduation_year YEAR NULL,
  linkedin VARCHAR(255) NULL,
  instagram VARCHAR(255) NULL,
  whatsapp VARCHAR(30) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table
CREATE TABLE IF NOT EXISTS vms_events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(150) NOT NULL,
  event_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Department table
CREATE TABLE IF NOT EXISTS vms_department (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department VARCHAR(100) NOT NULL UNIQUE,
  status TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISITOR MANAGEMENT TABLES
-- =====================================================

-- Visitors table (comprehensive schema with all fields)
CREATE TABLE IF NOT EXISTS vms_visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NULL,
  mobile VARCHAR(30) NULL,
  address TEXT NULL,
  department VARCHAR(100) NULL,
  gender ENUM('Male', 'Female', 'Other') NULL,
  year_of_graduation YEAR NULL,
  roll_number VARCHAR(20) NULL,
  in_time DATETIME NULL,
  out_time DATETIME NULL,
  status TINYINT(1) DEFAULT 1,
  added_by INT NULL COMMENT 'User ID who added this visitor',
  relation VARCHAR(100) NULL COMMENT 'Relation to existing guest',
  visitor_type ENUM('regular', 'spot_entry', 'additional_member') DEFAULT 'regular',
  registration_type ENUM('beforehand', 'spot') DEFAULT 'beforehand',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_visitors_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE CASCADE,
  CONSTRAINT fk_visitors_added_by FOREIGN KEY (added_by) 
    REFERENCES vms_members(id) ON DELETE SET NULL,
  INDEX idx_visitors_event (event_id),
  INDEX idx_visitors_email (email),
  INDEX idx_visitors_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event registrations table
CREATE TABLE IF NOT EXISTS event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  event VARCHAR(100) NOT NULL,
  event_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_registrations_event (event),
  INDEX idx_registrations_email (email),
  INDEX idx_registrations_event_id (event_id),
  CONSTRAINT fk_event_reg_user FOREIGN KEY (user_id) 
    REFERENCES vms_members(id) ON DELETE CASCADE,
  CONSTRAINT fk_event_registrations_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Excel imports table
CREATE TABLE IF NOT EXISTS vms_excel_imports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NULL,
  mobile VARCHAR(30) NULL,
  address TEXT NULL,
  department VARCHAR(100) NULL,
  gender ENUM('Male', 'Female', 'Other') NULL,
  year_of_graduation YEAR NULL,
  excel_file_name VARCHAR(255) NOT NULL,
  import_status ENUM('pending', 'imported', 'failed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_excel_imports_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE CASCADE,
  INDEX idx_excel_imports_status (import_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INVENTORY MANAGEMENT TABLES
-- =====================================================

-- Inventory table
CREATE TABLE IF NOT EXISTS vms_inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(100) NOT NULL,
  total_stock INT NOT NULL DEFAULT 0,
  used_count INT NOT NULL DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_inventory_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory change log
CREATE TABLE IF NOT EXISTS vms_inventory_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NULL,
  item_name VARCHAR(100) NULL,
  delta INT NOT NULL,
  user_id INT NULL,
  action VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inventory_log_item (item_id),
  INDEX idx_inventory_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goodies distribution table
CREATE TABLE IF NOT EXISTS vms_goodies_distribution (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visitor_id INT NULL,
  goodie_name VARCHAR(100) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  distribution_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  remarks TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_goodies_visitor FOREIGN KEY (visitor_id) 
    REFERENCES vms_visitors(id) ON DELETE SET NULL,
  INDEX idx_goodies_visitor (visitor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EVENT MANAGEMENT TABLES
-- =====================================================

-- Event participation table
CREATE TABLE IF NOT EXISTS vms_event_participation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NULL,
  activity_name VARCHAR(100) NOT NULL,
  participant_count INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_participation_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE SET NULL,
  INDEX idx_participation_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coordinator notes table
CREATE TABLE IF NOT EXISTS vms_coordinator_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NULL,
  note_type ENUM('LOG', 'ACTION_ITEM') NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notes_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE SET NULL,
  INDEX idx_notes_event (event_id),
  INDEX idx_notes_type (note_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA (Optional - Comment out for production)
-- =====================================================

-- Insert default admin user
INSERT INTO vms_admin (user_name, emailid, password) 
VALUES ('Administrator', 'admin@example.com', MD5('admin123')) 
ON DUPLICATE KEY UPDATE user_name=VALUES(user_name);

-- Insert sample departments
INSERT INTO vms_department (department) VALUES 
  ('CSE'), 
  ('ECE'), 
  ('Mechanical'), 
  ('Civil'), 
  ('EEE'), 
  ('Chemical'), 
  ('AIML'), 
   ('AIDS'), 
  ('Management')
ON DUPLICATE KEY UPDATE department=VALUES(department);

-- Insert sample events
INSERT INTO vms_events (event_name, event_date) VALUES 
  ('Annual Alumni Meet', '2025-01-15'), 
  ('Tech Symposium', '2025-02-20'), 
  ('Career Fair', '2025-03-10'), 
  ('Cultural Festival', '2025-04-05'),
  ('Nostalgia', NULL)
ON DUPLICATE KEY UPDATE event_name=VALUES(event_name);

-- Insert sample members
INSERT INTO vms_members (member_name, emailid, password, department, graduation_year, linkedin, instagram, whatsapp) VALUES 
  ('Member', 'member@example.com', MD5('member123'), 'Computer Science', 2020, 'linkedin.com/in/member', '@member', '+91 9876543210'),
  ('Member', 'member2@example.com', MD5('member123'), 'Electronics', 2019, 'linkedin.com/in/member2', '@member2', '+91 8765432109'),
  ('Mike Johnson', 'mike.j@example.com', MD5('member123'), 'Mechanical', 2018, 'linkedin.com/in/mikejohnson', '@mikej', '+91 7654321098'),
  ('Sarah Wilson', 'sarah.w@example.com', MD5('member123'), 'Civil', 2021, 'linkedin.com/in/sarahwilson', '@sarahw', '+91 6543210987')
ON DUPLICATE KEY UPDATE member_name=VALUES(member_name);

-- =====================================================
-- END OF SCHEMA
-- =====================================================

