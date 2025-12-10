<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_block_learning_style_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025120901) {
        // Define index to be added to learning_style
        $table = new xmldb_table('learning_style');
        
        // Drop old non-unique index if exists
        $old_index = new xmldb_index('block_learning_style_user_idc', XMLDB_INDEX_NOTUNIQUE, ['user']);
        if ($dbman->index_exists($table, $old_index)) {
            $dbman->drop_index($table, $old_index);
        }
        
        // Add unique index on user to prevent duplicate tests per user
        $index = new xmldb_index('user_unique', XMLDB_INDEX_UNIQUE, ['user']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Learning Style savepoint reached.
        upgrade_block_savepoint(true, 2025120901, 'learning_style');
    }

    return true;
}
