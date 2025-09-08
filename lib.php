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
function get_metrics($id_course){
    GLOBAL $DB, $USER, $CFG, $PAGE;
    //inicializacion de la respuesta
    $response = ["total_students" => 0, 
                "total_students_on_course" => 0,
                "course" => $id_course,
                "data" => [
                "num_act" => 0, 
                "num_ref" => 0, 
                "num_vis" => 0, 
                "num_vrb" => 0, 
                "num_sen" => 0, 
                "num_int" => 0, 
                "num_sec" => 0, 
                "num_glo" => 0,
                ]
            ];
    //Se obtiene el numero de estudiantes encuestados en un curso
    $sql_registros = $DB->get_record_sql(
        "SELECT count(id) as total_students
        FROM {learning_style} 
        WHERE course = :courseid
        ",
        ['courseid' => $id_course]
    );
    $response["total_students"] = intval($sql_registros->total_students);
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
        ['courseid' => $id_course]
    );
    //Los 8 estilos de aprendizaje
    $total_act = $DB->get_record_sql(
        "SELECT COUNT(id) as cantidad FROM {learning_style} WHERE ap_active > 0"
    );
    $total_ref = $DB->get_record_sql(
        "SELECT COUNT(id) as cantidad FROM {learning_style} WHERE ap_reflexivo > 0"
    );
    $total_vis = $DB->get_record_sql(
        "SELECT COUNT(id) as cantidad FROM {learning_style} WHERE ap_sensorial > 0"
    );
    $total_vrb = $DB->get_record_sql(
        "SELECT COUNT(id) as cantidad FROM {learning_style} WHERE ap_verbal > 0"
    );
    $total_sen = $DB->get_record_sql(
        "SELECT COUNT(id) as cantidad FROM {learning_style} WHERE ap_sensorial > 0"
    );
    $total_int = $DB->get_record_sql(
        "SELECT COUNT(id) as cantidad FROM {learning_style} WHERE ap_intuitivo > 0"
    );
    $total_sec = $DB->get_record_sql(
        "SELECT COUNT(id) as cantidad FROM {learning_style} WHERE ap_secuencial > 0"
    );
    $total_glo = $DB->get_record_sql(
        "SELECT COUNT(id) as cantidad FROM {learning_style} WHERE ap_global > 0"
    );
    $response["total_students_on_course"] = intval($total_estudiantes->cantidad);
    $response["data"]["num_act"] = intval($total_act->cantidad);
    $response["data"]["num_ref"] = intval($total_ref->cantidad);
    $response["data"]["num_vis"] = intval($total_vis->cantidad);
    $response["data"]["num_vrb"] = intval($total_vrb->cantidad);
    $response["data"]["num_sen"] = intval($total_sen->cantidad);
    $response["data"]["num_int"] = intval($total_int->cantidad);
    $response["data"]["num_sec"] = intval($total_sec->cantidad);
    $response["data"]["num_glo"] = intval($total_glo->cantidad);
    
    echo json_encode($response);
}
