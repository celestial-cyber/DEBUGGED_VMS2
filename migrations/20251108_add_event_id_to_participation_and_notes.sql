-- Migration: add event_id to participation and coordinator notes
-- Run this on your MySQL database to scope participation and notes to events

<<<<<<< HEAD
ALTER TABLE vms_event_participation
  ADD COLUMN IF NOT EXISTS event_id INT NULL AFTER id;

ALTER TABLE vms_coordinator_notes
  ADD COLUMN IF NOT EXISTS event_id INT NULL AFTER id;

-- Optional: add foreign key constraints if you want strict referential integrity
ALTER TABLE vms_event_participation
  ADD CONSTRAINT IF NOT EXISTS fk_participation_event FOREIGN KEY (event_id) REFERENCES vms_events(event_id) ON DELETE SET NULL;

ALTER TABLE vms_coordinator_notes
  ADD CONSTRAINT IF NOT EXISTS fk_notes_event FOREIGN KEY (event_id) REFERENCES vms_events(event_id) ON DELETE SET NULL;
=======
ALTER TABLE tbl_event_participation
  ADD COLUMN IF NOT EXISTS event_id INT NULL AFTER id;

ALTER TABLE tbl_coordinator_notes
  ADD COLUMN IF NOT EXISTS event_id INT NULL AFTER id;

-- Optional: add foreign key constraints if you want strict referential integrity
ALTER TABLE tbl_event_participation
  ADD CONSTRAINT IF NOT EXISTS fk_participation_event FOREIGN KEY (event_id) REFERENCES tbl_events(event_id) ON DELETE SET NULL;

ALTER TABLE tbl_coordinator_notes
  ADD CONSTRAINT IF NOT EXISTS fk_notes_event FOREIGN KEY (event_id) REFERENCES tbl_events(event_id) ON DELETE SET NULL;
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337

-- End migration
