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

    if ($oldversion < 2025121301) {
        // Fix existing data: recalculate dimension scores with corrected logic
        // Previous versions stored only differences instead of actual counts
        // Now we store actual counts (each dimension pair must sum to 11)
        
        mtrace('Recalculating learning style dimension scores for existing records...');
        
        $records = $DB->get_records('learning_style', array('is_completed' => 1));
        $fixed_count = 0;
        $error_count = 0;
        
        foreach ($records as $record) {
            // Rebuild learning_style_a array from stored questions
            $learning_style_a = array();
            $missing_questions = array();
            
            for ($i = 1; $i <= 44; $i++) {
                $field = "q{$i}";
                if (isset($record->$field) && $record->$field !== null) {
                    $learning_style_a[$i] = $record->$field;
                } else {
                    $missing_questions[] = $i;
                }
            }
            
            // Skip if incomplete
            if (!empty($missing_questions)) {
                mtrace("  Skipped user {$record->user}: missing questions " . implode(', ', $missing_questions));
                $error_count++;
                continue;
            }
            
            // Recalculate dimensions using CORRECTED logic
            // Active/Reflexive - Questions: 1,5,9,13,17,21,25,29,33,37,41
            $act_ref_eval = [1,5,9,13,17,21,25,29,33,37,41];
            $act_ref = ["a" => 0, "b" => 0];
            
            // Sensorial/Intuitive - Questions: 2,6,10,14,18,22,26,30,34,38,42
            $sen_int_eval = [2,6,10,14,18,22,26,30,34,38,42];
            $sen_int = ["a" => 0, "b" => 0];
            
            // Visual/Verbal - Questions: 3,7,11,15,19,23,27,31,35,39,43
            $vis_vrb_eval = [3,7,11,15,19,23,27,31,35,39,43];
            $vis_vrb = ["a" => 0, "b" => 0];
            
            // Sequential/Global - Questions: 4,8,12,16,20,24,28,32,36,40,44
            $seq_glo_eval = [4,8,12,16,20,24,28,32,36,40,44];
            $seq_glo = ["a" => 0, "b" => 0];
            
            // Count Active/Reflexive (0 = Active, 1 = Reflexive)
            foreach($act_ref_eval as $item){
                if ($learning_style_a[$item] == 0){
                    $act_ref["a"]++;
                }else{
                    $act_ref["b"]++;
                }
            }
            
            // Count Sensorial/Intuitive (0 = Sensorial, 1 = Intuitive)
            foreach($sen_int_eval as $item){
                if ($learning_style_a[$item] == 0){
                    $sen_int["a"]++;
                }else{
                    $sen_int["b"]++;
                }
            }
            
            // Count Visual/Verbal (0 = Visual, 1 = Verbal)
            foreach($vis_vrb_eval as $item){
                if ($learning_style_a[$item] == 0){
                    $vis_vrb["a"]++;
                }else{
                    $vis_vrb["b"]++;
                }
            }
            
            // Count Sequential/Global (0 = Sequential, 1 = Global)
            foreach($seq_glo_eval as $item){
                if ($learning_style_a[$item] == 0){
                    $seq_glo["a"]++;
                }else{
                    $seq_glo["b"]++;
                }
            }
            
            // Validate sums (each dimension pair must equal 11)
            $validation_ok = true;
            if (($act_ref["a"] + $act_ref["b"]) != 11) {
                mtrace("  ERROR user {$record->user}: Active/Reflexive sum is " . ($act_ref["a"] + $act_ref["b"]) . " instead of 11");
                $validation_ok = false;
            }
            if (($sen_int["a"] + $sen_int["b"]) != 11) {
                mtrace("  ERROR user {$record->user}: Sensorial/Intuitive sum is " . ($sen_int["a"] + $sen_int["b"]) . " instead of 11");
                $validation_ok = false;
            }
            if (($vis_vrb["a"] + $vis_vrb["b"]) != 11) {
                mtrace("  ERROR user {$record->user}: Visual/Verbal sum is " . ($vis_vrb["a"] + $vis_vrb["b"]) . " instead of 11");
                $validation_ok = false;
            }
            if (($seq_glo["a"] + $seq_glo["b"]) != 11) {
                mtrace("  ERROR user {$record->user}: Sequential/Global sum is " . ($seq_glo["a"] + $seq_glo["b"]) . " instead of 11");
                $validation_ok = false;
            }
            
            if (!$validation_ok) {
                $error_count++;
                continue;
            }
            
            // Update record with CORRECT counts (0-11 for each dimension)
            $update = new stdClass();
            $update->id = $record->id;
            $update->ap_active = $act_ref["a"];      // Count of Active answers (0-11)
            $update->ap_reflexivo = $act_ref["b"];   // Count of Reflexive answers (0-11)
            $update->ap_sensorial = $sen_int["a"];   // Count of Sensorial answers (0-11)
            $update->ap_intuitivo = $sen_int["b"];   // Count of Intuitive answers (0-11)
            $update->ap_visual = $vis_vrb["a"];      // Count of Visual answers (0-11)
            $update->ap_verbal = $vis_vrb["b"];      // Count of Verbal answers (0-11)
            $update->ap_secuencial = $seq_glo["a"];  // Count of Sequential answers (0-11)
            $update->ap_global = $seq_glo["b"];      // Count of Global answers (0-11)
            
            try {
                $DB->update_record('learning_style', $update);
                $fixed_count++;
            } catch (Exception $e) {
                mtrace("  ERROR user {$record->user}: " . $e->getMessage());
                $error_count++;
            }
        }
        
        mtrace("Dimension score recalculation complete:");
        mtrace("  - Fixed: {$fixed_count} records");
        mtrace("  - Errors/Skipped: {$error_count} records");
        
        upgrade_block_savepoint(true, 2025121301, 'learning_style');
    }
    
    if ($oldversion < 2025121701) {
        // Remove redundant timemodified column (use updated_at instead).
        $table = new xmldb_table('learning_style');
        $field = new xmldb_field('timemodified');
        
        if ($dbman->field_exists($table, $field)) {
            // Best-effort data migration: if updated_at is empty (0), copy timemodified.
            try {
                $DB->execute('UPDATE {learning_style} SET updated_at = timemodified WHERE updated_at = 0');
            } catch (Exception $e) {
                // Ignore and continue with schema change.
            }
            
            $dbman->drop_field($table, $field);
        }
        
        upgrade_block_savepoint(true, 2025121701, 'learning_style');
    }

    // Remove course field as functionality is now cross-course
    if ($oldversion < 2025121702) {
        $table = new xmldb_table('learning_style');
        
        // Drop the course field if it exists
        $field = new xmldb_field('course');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        upgrade_block_savepoint(true, 2025121702, 'learning_style');
    }

    // Remove obsolete state field
    if ($oldversion < 2025121703) {
        $table = new xmldb_table('learning_style');
        
        // Drop the state field if it exists
        $field = new xmldb_field('state');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        upgrade_block_savepoint(true, 2025121703, 'learning_style');
    }

    return true;
}
