DROP DATABASE IF EXISTS vms_db;
CREATE DATABASE vms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vms_db;

-- Admin table
CREATE TABLE IF NOT EXISTS tbl_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(100) NOT NULL,
  emailid VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

-- Members table
CREATE TABLE IF NOT EXISTS tbl_members (
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
);

-- Events table
CREATE TABLE IF NOT EXISTS tbl_events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(150) NOT NULL,
  event_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Department table
CREATE TABLE IF NOT EXISTS tbl_department (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department VARCHAR(100) NOT NULL UNIQUE,
  status TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Visitors table (updated with additional fields)
CREATE TABLE IF NOT EXISTS tbl_visitors (
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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_visitors_event FOREIGN KEY (event_id) REFERENCES tbl_events(event_id) ON DELETE CASCADE
);

-- Event registrations table
CREATE TABLE IF NOT EXISTS event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  event VARCHAR(100) NOT NULL,
  event_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory table
CREATE TABLE IF NOT EXISTS tbl_inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(100) NOT NULL,
  total_stock INT NOT NULL DEFAULT 0,
  used_count INT NOT NULL DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory change log
CREATE TABLE IF NOT EXISTS tbl_inventory_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NULL,
  item_name VARCHAR(100) NULL,
  delta INT NOT NULL,
  user_id INT NULL,
  action VARCHAR(50) NOT NULL, 
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(item_id)
);

-- Goodies distribution table
CREATE TABLE IF NOT EXISTS tbl_goodies_distribution (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visitor_id INT NULL,
  goodie_name VARCHAR(100) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  distribution_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  remarks TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (visitor_id) REFERENCES tbl_visitors(id) ON DELETE SET NULL
);

-- Event participation table
CREATE TABLE IF NOT EXISTS tbl_event_participation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  activity_name VARCHAR(100) NOT NULL,
  participant_count INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Coordinator notes table
CREATE TABLE IF NOT EXISTS tbl_coordinator_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  note_type ENUM('LOG', 'ACTION_ITEM') NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO tbl_admin (user_name, emailid, password) VALUES ('Administrator', 'admin@example.com', MD5('admin123')) ON DUPLICATE KEY UPDATE user_name=VALUES(user_name);

-- Insert sample departments
INSERT INTO tbl_department (department) VALUES ('Computer Science'), ('Electronics'), ('Mechanical'), ('Civil'), ('Electrical'), ('Chemical'), ('Biotechnology'), ('Management');

-- Insert sample events
INSERT INTO tbl_events (event_name, event_date) VALUES ('Annual Alumni Meet', '2025-01-15'), ('Tech Symposium', '2025-02-20'), ('Career Fair', '2025-03-10'), ('Cultural Festival', '2025-04-05');

-- Insert sample visitors
INSERT INTO tbl_visitors (event_id, name, email, mobile, address, department, gender, year_of_graduation, roll_number, in_time, out_time, status) VALUES (1, 'John Smith', 'john.smith@example.com', '9876543210', '123 Main St, City', 'Computer Science', 'Male', 2015, 'CS001', '2025-01-15 09:30:00', '2025-01-15 16:45:00', 1), (1, 'Emma Johnson', 'emma.j@example.com', '8765432109', '456 Oak Ave, Town', 'Electronics', 'Female', 2018, 'EC002', '2025-01-15 10:15:00', '2025-01-15 17:30:00', 1), (2, 'Michael Brown', 'michael.b@example.com', '7654321098', '789 Pine Rd, Village', 'Mechanical', 'Male', 2012, 'ME003', '2025-02-20 08:45:00', '2025-02-20 15:20:00', 1), (2, 'Sarah Davis', 'sarah.d@example.com', '6543210987', '321 Elm St, City', 'Civil', 'Female', 2016, 'CE004', '2025-02-20 09:30:00', NULL, 1), (3, 'Robert Wilson', 'robert.w@example.com', '5432109876', '654 Maple Ave, Town', 'Electrical', 'Male', 2019, 'EE005', '2025-03-10 10:00:00', '2025-03-10 16:00:00', 1), (3, 'Lisa Miller', 'lisa.m@example.com', '4321098765', '987 Cedar Rd, Village', 'Chemical', 'Female', 2014, 'CH006', '2025-03-10 11:30:00', NULL, 1), (4, 'David Taylor', 'david.t@example.com', '3210987654', '159 Birch St, City', 'Biotechnology', 'Male', 2017, 'BT007', '2025-04-05 09:15:00', '2025-04-05 17:45:00', 1), (4, 'Jennifer Lee', 'jennifer.l@example.com', '2109876543', '753 Walnut Ave, Town', 'Management', 'Female', 2020, 'MG008', '2025-04-05 10:45:00', NULL, 1);

-- Insert sample inventory
INSERT INTO tbl_inventory (item_name, total_stock, used_count, status) VALUES ('T-Shirts', 200, 150, 'Available'), ('Water Bottles', 300, 250, 'Available'), ('Notebooks', 500, 400, 'Available'), ('Pens', 1000, 800, 'Available'), ('Badges', 400, 350, 'Available'), ('Lanyards', 250, 200, 'Available'), ('Stickers', 600, 550, 'Available'), ('Bags', 150, 120, 'Available');

-- Insert sample goodies distribution
INSERT INTO tbl_goodies_distribution (visitor_id, goodie_name, quantity, distribution_time, remarks) VALUES (1, 'T-Shirt', 1, '2025-01-15 10:00:00', 'Medium size'), (1, 'Water Bottle', 1, '2025-01-15 10:00:00', NULL), (2, 'T-Shirt', 1, '2025-01-15 10:30:00', 'Small size'), (2, 'Notebook', 2, '2025-01-15 10:30:00', NULL), (3, 'T-Shirt', 1, '2025-02-20 09:00:00', 'Large size'), (3, 'Pen', 3, '2025-02-20 09:00:00', NULL), (4, 'Water Bottle', 1, '2025-02-20 09:30:00', NULL), (5, 'T-Shirt', 1, '2025-03-10 10:15:00', 'Medium size'), (5, 'Badge', 1, '2025-03-10 10:15:00', NULL), (6, 'Notebook', 1, '2025-03-10 11:45:00', NULL), (7, 'T-Shirt', 1, '2025-04-05 09:30:00', 'Large size'), (7, 'Bag', 1, '2025-04-05 09:30:00', NULL), (8, 'Water Bottle', 1, '2025-04-05 11:00:00', NULL);

-- Insert sample event participation
INSERT INTO tbl_event_participation (activity_name, participant_count) VALUES ('Keynote Speech', 120), ('Panel Discussion', 85), ('Workshop Session', 65), ('Networking Lunch', 200), ('Award Ceremony', 150), ('Campus Tour', 40), ('Alumni Dinner', 90), ('Career Counseling', 75);

-- Insert sample coordinator notes
INSERT INTO tbl_coordinator_notes (note_type, content) VALUES ('LOG', 'Registration desk opened at 9:00 AM'), ('LOG', 'First visitor checked in at 9:30 AM'), ('LOG', 'Lunch break from 1:00 PM to 2:00 PM'), ('LOG', 'Closing ceremony started at 5:00 PM'), ('ACTION_ITEM', 'Order more T-Shirts - running low on stock'), ('ACTION_ITEM', 'Schedule follow-up meeting with alumni committee'), ('ACTION_ITEM', 'Prepare feedback forms for next event'), ('ACTION_ITEM', 'Update visitor database with new contact information');

-- Insert sample members
INSERT INTO tbl_members (member_name, emailid, password, department, graduation_year, linkedin, instagram, whatsapp) VALUES ('Member', 'member@example.com', MD5('member123'), 'Computer Science', 2020, 'linkedin.com/in/member', '@member', '+91 9876543210'), ('Member', 'member2@example.com', MD5('member123'), 'Electronics', 2019, 'linkedin.com/in/member2', '@member2', '+91 8765432109'), ('Mike Johnson', 'mike.j@example.com', MD5('member123'), 'Mechanical', 2018, 'linkedin.com/in/mikejohnson', '@mikej', '+91 7654321098'), ('Sarah Wilson', 'sarah.w@example.com', MD5('member123'), 'Civil', 2021, 'linkedin.com/in/sarahwilson', '@sarahw', '+91 6543210987');
