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

    if ($oldversion < 2025121001) {
        $table = new xmldb_table('learning_style');

        // Add is_completed field
        $field = new xmldb_field('is_completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'course');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add individual question response fields (q1 through q44)
        for ($i = 1; $i <= 44; $i++) {
            $prev_field = ($i == 1) ? 'is_completed' : 'q' . ($i - 1);
            $next_field = ($i == 44) ? 'act_ref' : 'q' . ($i + 1);
            
            $field = new xmldb_field('q' . $i, XMLDB_TYPE_INTEGER, '1', null, null, null, null, $prev_field);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Make result fields nullable
        // Text fields
        $text_fields = ['act_ref', 'sen_int', 'vis_vrb', 'seq_glo'];
        foreach ($text_fields as $fieldname) {
            $field = new xmldb_field($fieldname, XMLDB_TYPE_TEXT, '10', null, null, null, null);
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_notnull($table, $field);
            }
        }
        
        // Integer fields
        $int_fields = ['ap_active', 'ap_reflexivo', 'ap_sensorial', 'ap_intuitivo',
                      'ap_visual', 'ap_verbal', 'ap_secuencial', 'ap_global'];
        foreach ($int_fields as $fieldname) {
            $field = new xmldb_field($fieldname, XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_notnull($table, $field);
            }
        }

        // Add timemodified field
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'updated_at');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // For existing records, set is_completed = 1 and timemodified = updated_at
        $DB->execute("UPDATE {learning_style} SET is_completed = 1, timemodified = updated_at WHERE updated_at > 0");

        upgrade_block_savepoint(true, 2025121001, 'learning_style');
    }

    return true;
}
