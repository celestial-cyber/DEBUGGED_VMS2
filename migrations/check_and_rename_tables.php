<?php
/**
 * Script to check current table names and rename from tbl_ to vms_ if needed
 * Run this from command line: php migrations/check_and_rename_tables.php
 */

require_once __DIR__ . '/../config/connection.php';

$tables_to_rename = [
    'tbl_admin' => 'vms_admin',
    'tbl_members' => 'vms_members',
    'tbl_events' => 'vms_events',
    'tbl_department' => 'vms_department',
    'tbl_visitors' => 'vms_visitors',
    'tbl_excel_imports' => 'vms_excel_imports',
    'tbl_inventory' => 'vms_inventory',
    'tbl_inventory_log' => 'vms_inventory_log',
    'tbl_goodies_distribution' => 'vms_goodies_distribution',
    'tbl_event_participation' => 'vms_event_participation',
    'tbl_coordinator_notes' => 'vms_coordinator_notes',
];

echo "Checking current table state...\n\n";

$tables_to_rename_list = [];
foreach ($tables_to_rename as $old_name => $new_name) {
    // Check if old table exists
    $check_old = $conn->query("SHOW TABLES LIKE '$old_name'");
    $old_exists = $check_old && $check_old->num_rows > 0;
    
    // Check if new table exists
    $check_new = $conn->query("SHOW TABLES LIKE '$new_name'");
    $new_exists = $check_new && $check_new->num_rows > 0;
    
    if ($old_exists && !$new_exists) {
        echo "✓ $old_name exists, will rename to $new_name\n";
        $tables_to_rename_list[$old_name] = $new_name;
    } elseif ($new_exists) {
        echo "✓ $new_name already exists (already renamed)\n";
    } elseif (!$old_exists && !$new_exists) {
        echo "⚠ Neither $old_name nor $new_name exists (table not created yet)\n";
    }
}

if (empty($tables_to_rename_list)) {
    echo "\n✅ All tables are already using vms_ prefix or don't exist yet.\n";
    echo "No migration needed.\n";
    exit(0);
}

echo "\n\nRenaming " . count($tables_to_rename_list) . " tables...\n\n";

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$success_count = 0;
$error_count = 0;

foreach ($tables_to_rename_list as $old_name => $new_name) {
    $sql = "RENAME TABLE `$old_name` TO `$new_name`";
    if ($conn->query($sql)) {
        echo "✓ Renamed $old_name → $new_name\n";
        $success_count++;
    } else {
        echo "✗ Failed to rename $old_name: " . $conn->error . "\n";
        $error_count++;
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n";
echo "Migration Summary:\n";
echo "  Successfully renamed: $success_count\n";
echo "  Errors: $error_count\n";

if ($success_count > 0) {
    echo "\nUpdating foreign key constraints...\n";
    
    // Update foreign keys
    $fk_updates = [
        "ALTER TABLE vms_visitors DROP FOREIGN KEY IF EXISTS fk_visitors_event",
        "ALTER TABLE vms_visitors DROP FOREIGN KEY IF EXISTS fk_visitors_added_by",
        "ALTER TABLE vms_visitors ADD CONSTRAINT fk_visitors_event FOREIGN KEY (event_id) REFERENCES vms_events(event_id) ON DELETE CASCADE",
        "ALTER TABLE vms_visitors ADD CONSTRAINT fk_visitors_added_by FOREIGN KEY (added_by) REFERENCES vms_members(id) ON DELETE SET NULL",
        "ALTER TABLE event_registrations DROP FOREIGN KEY IF EXISTS fk_event_reg_user",
        "ALTER TABLE event_registrations DROP FOREIGN KEY IF EXISTS fk_event_registrations_event",
        "ALTER TABLE event_registrations ADD CONSTRAINT fk_event_reg_user FOREIGN KEY (user_id) REFERENCES vms_members(id) ON DELETE CASCADE",
        "ALTER TABLE event_registrations ADD CONSTRAINT fk_event_registrations_event FOREIGN KEY (event_id) REFERENCES vms_events(event_id) ON DELETE CASCADE",
    ];
    
    foreach ($fk_updates as $sql) {
        // Remove IF EXISTS for MySQL versions that don't support it
        $sql = str_replace('IF EXISTS ', '', $sql);
        @$conn->query($sql);
    }
    
    echo "✓ Foreign key constraints updated\n";
}

echo "\n✅ Migration complete!\n";
$conn->close();

