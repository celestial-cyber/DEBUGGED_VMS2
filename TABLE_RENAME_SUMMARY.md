# Table Rename Summary: tbl_ to vms_

## Overview
All database tables have been renamed from the `tbl_` prefix to the `vms_` prefix throughout the entire application.

## Tables Renamed

### Core Tables
- `tbl_admin` → `vms_admin`
- `tbl_members` → `vms_members`
- `tbl_events` → `vms_events`
- `tbl_department` → `vms_department`

### Visitor Management Tables
- `tbl_visitors` → `vms_visitors`
- `tbl_excel_imports` → `vms_excel_imports`
- `event_registrations` → (unchanged - no prefix)

### Inventory Management Tables
- `tbl_inventory` → `vms_inventory`
- `tbl_inventory_log` → `vms_inventory_log`
- `tbl_goodies_distribution` → `vms_goodies_distribution`

### Event Management Tables
- `tbl_event_participation` → `vms_event_participation`
- `tbl_coordinator_notes` → `vms_coordinator_notes`

## Files Updated

### SQL Files
1. **`database_schema.sql`** - Updated all table definitions and foreign key references
2. **`migrations/rename_tables_tbl_to_vms.sql`** - Migration script to rename existing tables
3. **`migrations/20251108_add_event_id_to_participation_and_notes.sql`** - Updated table references

### PHP Files (75+ files)
All PHP files have been updated to use the new `vms_` table names, including:
- `nostalgia.php`
- `add_department_ajax.php`
- `Admin_dashbaord/add_department_ajax.php`
- `manage-inventory.php`
- `manage-visitors.php`
- And 70+ other PHP files

### Documentation Files
- All `.md` files have been updated with new table names

### Utility Files
- `verify_db.php` - Updated table list
- `check_tbl_visitors.php` - Updated table name

## Migration Instructions

### For Existing Databases

If you have an existing database with `tbl_` prefixed tables, run the migration script:

```sql
-- Run this file to rename existing tables
SOURCE migrations/rename_tables_tbl_to_vms.sql;
```

Or execute it directly:
```bash
mysql -u root -p vms_db < migrations/rename_tables_tbl_to_vms.sql
```

### For New Databases

Simply use the updated `database_schema.sql` file which now creates all tables with the `vms_` prefix:

```bash
mysql -u root -p < database_schema.sql
```

## What Changed

1. **Table Names**: All `tbl_*` tables renamed to `vms_*`
2. **Foreign Key Constraints**: All foreign key references updated
3. **Code References**: All PHP code updated to use new table names
4. **Documentation**: All documentation files updated

## Verification

After running the migration, verify the changes:

1. Run `verify_db.php` to check all tables exist
2. Test key functionality:
   - Admin login
   - Member login
   - Visitor registration
   - Event management
   - Inventory management

## Notes

- The `event_registrations` table was not renamed as it doesn't use the `tbl_` prefix
- All foreign key constraints have been properly updated
- The migration script temporarily disables foreign key checks during renaming for safety

## Rollback

If you need to rollback, you can reverse the migration by running:

```sql
USE vms_db;
SET FOREIGN_KEY_CHECKS = 0;

RENAME TABLE vms_admin TO tbl_admin;
RENAME TABLE vms_members TO tbl_members;
RENAME TABLE vms_events TO tbl_events;
RENAME TABLE vms_department TO tbl_department;
RENAME TABLE vms_visitors TO tbl_visitors;
RENAME TABLE vms_excel_imports TO tbl_excel_imports;
RENAME TABLE vms_inventory TO tbl_inventory;
RENAME TABLE vms_inventory_log TO tbl_inventory_log;
RENAME TABLE vms_goodies_distribution TO tbl_goodies_distribution;
RENAME TABLE vms_event_participation TO tbl_event_participation;
RENAME TABLE vms_coordinator_notes TO tbl_coordinator_notes;

SET FOREIGN_KEY_CHECKS = 1;
```

Then revert the PHP files using git or your version control system.

---

**Date**: 2025-01-XX
**Status**: Complete
**Tables Renamed**: 10 tables
**Files Updated**: 75+ files

