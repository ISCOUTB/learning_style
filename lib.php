<?php 

require_once(dirname(__FILE__) . '/../../config.php');

function save_learning_style($course,$act_ref,$sen_int,$vis_vrb,$seq_glo,$act,$ref,$sen,$int,$vis,$vrb,$seq,$glo) {
    GLOBAL $DB, $USER, $CFG;
    if (!$entry = $DB->get_record('learning_style', array('user' => $USER->id, 'course' => $course))) {
        $entry = new stdClass();
        $entry->user = $USER->id;
        $entry->course = $course;
        $entry->state = "1";
        $entry->act_ref = $act_ref;
        $entry->sen_int = $sen_int;
        $entry->vis_vrb = $vis_vrb;
        $entry->seq_glo = $seq_glo;
        $entry->ap_active = $act;
        $entry->ap_reflexivo = $ref;
        $entry->ap_sensorial = $sen;
        $entry->ap_intuitivo = $int;
        $entry->ap_visual = $vis;
        $entry->ap_verbal = $vrb;
        $entry->ap_secuencial = $seq;
        $entry->ap_global = $glo;
        $entry->created_at = time();
        $entry->updated_at = time();
        $entry->id = $DB->insert_record('learning_style', $entry);

        // Data to be saved in the log file
        $data = "{$USER->id}, $act, $sen, $vis, $seq, $ref, $int, $vrb, $glo\n";

        // Path to the log file
        $file = dirname(__FILE__) . '/style.csv';

        // Check if the file exists
        if (!file_exists($file)) {
            // If the file does not exist, write the header first
            $header = "user, act, sen, vis, seq, ref, int, vrb, glo\n";
            file_put_contents($file, $header, FILE_APPEND);
        }

        // Write the data to the log file
        file_put_contents($file, $data, FILE_APPEND);
        return true;
    }else{
        return false;
    }
}
function get_metrics(){
    GLOBAL $DB, $USER, $CFG;
    $response = ["total_students" => 0, 
                "total_students_on_course" => 0, 
                "num_act" => 5, 
                "num_ref" => 10, 
                "num_vis" => 11, 
                "num_vrb" => 5, 
                "num_sen" => 2, 
                "num_int" => 14, 
                "num_sec" => 12, 
                "num_glo" => 5,
                "course" => 0
            ];
    $sql_registros = $DB->get_records("learning_style");
    $response["total_students"] = count($sql_registros);
    $course_id = $sql_registros[1]->course;
    $total_estudiantes = $DB->get_record_sql(
        "SELECT count(m.id) as cantidad
        FROM {user} m
        LEFT JOIN {role_assignments} m2 ON m.id = m2.userid
        LEFT JOIN {context} m3 ON m2.contextid = m3.id
        LEFT JOIN {course} m4 ON m3.instanceid = m4.id
        WHERE m3.contextlevel = 50 
        AND m2.roleid IN (5) 
        AND m4.id = :courseid", // Usamos :courseid como parámetro
        // Pasamos los parámetros de forma segura
        ['courseid' => $course_id]
    );
    $response["total_students_on_course"] = intval($total_estudiantes->cantidad);
    //SELECT COUNT(*) FROM learning_style WHERE ap_active > 0
    $response["course"] = $course_id;
    print_r(json_encode($response));
}

