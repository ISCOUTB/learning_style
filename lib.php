<?php

require_once(dirname(__FILE__) . '/../../config.php');

function save_learning_style($course,$act_ref,$sen_int,$vis_vrb,$seq_glo,$act,$ref,$sen,$int,$vis,$vrb,$seq,$glo) {
    GLOBAL $DB, $USER, $CFG;
    // Check if user already has a learning style record (in any course)
    if (!$entry = $DB->get_record('learning_style', array('user' => $USER->id))) {
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
        //file_put_contents($file, $data, FILE_APPEND);
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
    
    // Obtener estudiantes inscritos en el curso
    $context = context_course::instance($id_course);
    $enrolled_students = get_enrolled_users($context, '', 0, 'u.id', null, 0, 0, true);
    
    // Filtrar solo estudiantes (rol 5)
    $student_ids = array();
    foreach ($enrolled_students as $user) {
        $roles = get_user_roles($context, $user->id);
        foreach ($roles as $role) {
            if ($role->roleid == 5) { // 5 = student
                $student_ids[] = $user->id;
                break;
            }
        }
    }
    
    $response["total_students_on_course"] = count($student_ids);
    
    if (empty($student_ids)) {
        echo json_encode($response);
        return;
    }
    
    // Obtener solo respuestas de estudiantes inscritos que completaron el test
    list($insql, $params) = $DB->get_in_or_equal($student_ids, SQL_PARAMS_NAMED, 'user');
    $params['completed'] = 1;
    $sql = "SELECT * FROM {learning_style} WHERE user $insql AND is_completed = :completed";
    $enrolled_results = $DB->get_records_sql($sql, $params);
    
    $response["total_students"] = count($enrolled_results);
    
    // Contar estilos de aprendizaje solo de estudiantes inscritos
    $num_act = 0;
    $num_ref = 0;
    $num_vis = 0;
    $num_vrb = 0;
    $num_sen = 0;
    $num_int = 0;
    $num_sec = 0;
    $num_glo = 0;
    
    foreach ($enrolled_results as $result) {
        if ($result->ap_active > 0) $num_act++;
        if ($result->ap_reflexivo > 0) $num_ref++;
        if ($result->ap_visual > 0) $num_vis++;
        if ($result->ap_verbal > 0) $num_vrb++;
        if ($result->ap_sensorial > 0) $num_sen++;
        if ($result->ap_intuitivo > 0) $num_int++;
        if ($result->ap_secuencial > 0) $num_sec++;
        if ($result->ap_global > 0) $num_glo++;
    }
    
    $response["data"]["num_act"] = $num_act;
    $response["data"]["num_ref"] = $num_ref;
    $response["data"]["num_vis"] = $num_vis;
    $response["data"]["num_vrb"] = $num_vrb;
    $response["data"]["num_sen"] = $num_sen;
    $response["data"]["num_int"] = $num_int;
    $response["data"]["num_sec"] = $num_sec;
    $response["data"]["num_glo"] = $num_glo;
    
    echo json_encode($response);
}
