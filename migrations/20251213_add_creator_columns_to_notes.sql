-- Add creator_type and member_id columns to vms_coordinator_notes table
-- This allows distinguishing between coordinator and member notes

ALTER TABLE vms_coordinator_notes
ADD COLUMN creator_type ENUM('coordinator', 'member') NOT NULL DEFAULT 'coordinator' AFTER note_type,
ADD COLUMN member_id INT NULL AFTER creator_type,
ADD CONSTRAINT fk_notes_member FOREIGN KEY (member_id)
  REFERENCES vms_members(id) ON DELETE SET NULL,
ADD INDEX idx_notes_creator (creator_type),
ADD INDEX idx_notes_member (member_id);