-- =====================================================
-- Migration: Rename all tables from tbl_ to vms_ prefix
-- =====================================================
-- This script renames all database tables from tbl_ prefix to vms_ prefix
-- Run this script on your existing database to migrate table names
-- =====================================================

USE vms_db;

-- Disable foreign key checks temporarily to allow renaming
SET FOREIGN_KEY_CHECKS = 0;

-- Rename core tables
RENAME TABLE tbl_admin TO vms_admin;
RENAME TABLE tbl_members TO vms_members;
RENAME TABLE tbl_events TO vms_events;
RENAME TABLE tbl_department TO vms_department;

-- Rename visitor management tables
RENAME TABLE tbl_visitors TO vms_visitors;
RENAME TABLE tbl_excel_imports TO vms_excel_imports;

-- Rename inventory management tables
RENAME TABLE tbl_inventory TO vms_inventory;
RENAME TABLE tbl_inventory_log TO vms_inventory_log;
RENAME TABLE tbl_goodies_distribution TO vms_goodies_distribution;

-- Rename event management tables
RENAME TABLE tbl_event_participation TO vms_event_participation;
RENAME TABLE tbl_coordinator_notes TO vms_coordinator_notes;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Update Foreign Key Constraints
-- =====================================================

-- Drop and recreate foreign keys for vms_visitors
ALTER TABLE vms_visitors 
  DROP FOREIGN KEY IF EXISTS fk_visitors_event,
  DROP FOREIGN KEY IF EXISTS fk_visitors_added_by;

ALTER TABLE vms_visitors
  ADD CONSTRAINT fk_visitors_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_visitors_added_by FOREIGN KEY (added_by) 
    REFERENCES vms_members(id) ON DELETE SET NULL;

-- Update foreign keys for event_registrations
ALTER TABLE event_registrations
  DROP FOREIGN KEY IF EXISTS fk_event_reg_user,
  DROP FOREIGN KEY IF EXISTS fk_event_registrations_event;

ALTER TABLE event_registrations
  ADD CONSTRAINT fk_event_reg_user FOREIGN KEY (user_id) 
    REFERENCES vms_members(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_event_registrations_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE CASCADE;

-- Update foreign keys for vms_excel_imports
ALTER TABLE vms_excel_imports
  DROP FOREIGN KEY IF EXISTS fk_excel_imports_event;

ALTER TABLE vms_excel_imports
  ADD CONSTRAINT fk_excel_imports_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE CASCADE;

-- Update foreign keys for vms_goodies_distribution
ALTER TABLE vms_goodies_distribution
  DROP FOREIGN KEY IF EXISTS fk_goodies_visitor;

ALTER TABLE vms_goodies_distribution
  ADD CONSTRAINT fk_goodies_visitor FOREIGN KEY (visitor_id) 
    REFERENCES vms_visitors(id) ON DELETE SET NULL;

-- Update foreign keys for vms_event_participation
ALTER TABLE vms_event_participation
  DROP FOREIGN KEY IF EXISTS fk_participation_event;

ALTER TABLE vms_event_participation
  ADD CONSTRAINT fk_participation_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE SET NULL;

-- Update foreign keys for vms_coordinator_notes
ALTER TABLE vms_coordinator_notes
  DROP FOREIGN KEY IF EXISTS fk_notes_event;

ALTER TABLE vms_coordinator_notes
  ADD CONSTRAINT fk_notes_event FOREIGN KEY (event_id) 
    REFERENCES vms_events(event_id) ON DELETE SET NULL;

-- =====================================================
-- Migration Complete
-- =====================================================
-- All tables have been renamed from tbl_ to vms_ prefix
-- All foreign key constraints have been updated
-- =====================================================

