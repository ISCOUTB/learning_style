<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Guarda el resultado del estilo de aprendizaje de un usuario.
 */
function save_learning_style($course, $act_ref, $sen_int, $vis_vrb, $seq_glo, $act, $ref, $sen, $int, $vis, $vrb, $seq, $glo) {
    global $DB, $USER;

    // Verificar si el usuario ya tiene un registro (Globalmente)
    if ($DB->record_exists('learning_style', array('user' => $USER->id))) {
        return false;
    }

    $entry = new stdClass();
    $entry->user = $USER->id;
    $entry->is_completed = 1;
    
    // Dimensiones
    $entry->act_ref = clean_param($act_ref, PARAM_INT);
    $entry->sen_int = clean_param($sen_int, PARAM_INT);
    $entry->vis_vrb = clean_param($vis_vrb, PARAM_INT);
    $entry->seq_glo = clean_param($seq_glo, PARAM_INT);
    
    // Puntajes específicos
    $entry->ap_active = clean_param($act, PARAM_INT);
    $entry->ap_reflexivo = clean_param($ref, PARAM_INT);
    $entry->ap_sensorial = clean_param($sen, PARAM_INT);
    $entry->ap_intuitivo = clean_param($int, PARAM_INT);
    $entry->ap_visual = clean_param($vis, PARAM_INT);
    $entry->ap_verbal = clean_param($vrb, PARAM_INT);
    $entry->ap_secuencial = clean_param($seq, PARAM_INT);
    $entry->ap_global = clean_param($glo, PARAM_INT);
    
    $entry->created_at = time();
    $entry->updated_at = time();
    
    $entry->id = $DB->insert_record('learning_style', $entry);

    return true;
}

/**
 * Obtiene las métricas del curso para el dashboard.
 */
function get_metrics($id_course) {
    global $DB;

    $response = [
        "total_students" => 0, 
        "total_students_on_course" => 0,
        "course" => $id_course,
        "data" => [
            "num_act" => 0, "num_ref" => 0, "num_vis" => 0, "num_vrb" => 0, 
            "num_sen" => 0, "num_int" => 0, "num_sec" => 0, "num_glo" => 0,
        ],
        // Server-side dominance calculation (supports ties).
        // Keys match the ones in `data`.
        "dominant_keys" => [],
        "dominant_value" => 0,
        "least_dominant_keys" => [],
        "least_dominant_value" => 0,
        // True when all styles have the same count (avoids duplicated labels in UI).
        "dominance_is_flat" => true,
    ];
    
    $context = context_course::instance($id_course);
    $capability = 'block/learning_style:take_test'; 
    
    // 1. Obtenemos TODOS los estudiantes matriculados en ESTE curso
    $enrolled_users = get_enrolled_users($context, $capability, 0, 'u.id');
    $student_ids = array_keys($enrolled_users);
    
    $response["total_students_on_course"] = count($student_ids);
    
    if (empty($student_ids)) {
        return $response;
    }
    
    // 2. Buscamos si esos estudiantes tienen respuestas en la BD
    // Quitamos "AND course = :course" para que cuente respuestas hechas en otros cursos
    list($insql, $params) = $DB->get_in_or_equal($student_ids, SQL_PARAMS_NAMED, 'user');
    $params['completed'] = 1;
    
    // Solo filtramos por usuario y completado. NO por curso.
    $sql = "SELECT * FROM {learning_style} WHERE user $insql AND is_completed = :completed";
    $enrolled_results = $DB->get_records_sql($sql, $params);
    
    $response["total_students"] = count($enrolled_results);
    
    foreach ($enrolled_results as $result) {
        // Active vs Reflexive
        if ($result->ap_active > $result->ap_reflexivo) {
            $response["data"]["num_act"]++;
        } else {
            $response["data"]["num_ref"]++;
        }

        // Visual vs Verbal
        if ($result->ap_visual > $result->ap_verbal) {
            $response["data"]["num_vis"]++;
        } else {
            $response["data"]["num_vrb"]++;
        }

        // Sensorial vs Intuitive
        if ($result->ap_sensorial > $result->ap_intuitivo) {
            $response["data"]["num_sen"]++;
        } else {
            $response["data"]["num_int"]++;
        }

        // Sequential vs Global
        if ($result->ap_secuencial > $result->ap_global) {
            $response["data"]["num_sec"]++;
        } else {
            $response["data"]["num_glo"]++;
        }
    }

    // Compute dominant and least-dominant keys on the server to avoid duplicating logic in clients.
    $values = $response['data'];
    $max = null;
    $min = null;
    foreach ($values as $key => $value) {
        if ($max === null || $value > $max) {
            $max = $value;
        }
        if ($min === null || $value < $min) {
            $min = $value;
        }
    }

    // When no values exist (shouldn't happen), keep defaults.
    if ($max === null || $min === null) {
        return $response;
    }

    $dominant = [];
    $least = [];
    foreach ($values as $key => $value) {
        if ($value === $max) {
            $dominant[] = $key;
        }
        if ($value === $min) {
            $least[] = $key;
        }
    }

    $response['dominant_keys'] = $dominant;
    $response['dominant_value'] = (int)$max;
    $response['least_dominant_keys'] = $least;
    $response['least_dominant_value'] = (int)$min;
    $response['dominance_is_flat'] = ($max === $min);
    
    return $response;
}
