<?php

require_once('../../config.php');

// Parámetros de entrada
$courseid = required_param('courseid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);

// Verificar clave de sesión para seguridad
require_sesskey();

// Obtener curso y contexto
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

// Requerir inicio de sesión y capacidad de profesor (usando capacidad estándar de Moodle)
require_login($course, false);
require_capability('moodle/course:viewhiddensections', $context);

// Get enrolled students in this course
$enrolled_students = get_enrolled_users($context, '', 0, 'u.id');
$enrolled_ids = array_keys($enrolled_students);

// Consulta SQL para obtener los resultados de los estudiantes inscritos (solo completados)
$results = array();
if (!empty($enrolled_ids)) {
    list($insql, $params) = $DB->get_in_or_equal($enrolled_ids, SQL_PARAMS_NAMED, 'user');
    
    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.username, u.idnumber,
                   ls.ap_active, ls.ap_reflexivo, ls.ap_sensorial, ls.ap_intuitivo,
                   ls.ap_visual, ls.ap_verbal, ls.ap_secuencial, ls.ap_global,
                   ls.act_ref, ls.sen_int, ls.vis_vrb, ls.seq_glo, ls.created_at
            FROM {user} u
            INNER JOIN {learning_style} ls ON u.id = ls.user
            WHERE u.deleted = 0 AND u.confirmed = 1 AND u.id $insql AND ls.is_completed = 1
            ORDER BY u.lastname, u.firstname";
    
    $results = $DB->get_records_sql($sql, $params);
}

// Definir las cabeceras del CSV
$headers = array(
    'ID Usuario',
    'Nombre',
    'Apellido',
    'Email',
    'Username',
    'Activo',
    'Reflexivo',
    'Sensorial',
    'Intuitivo',
    'Visual',
    'Verbal',
    'Secuencial',
    'Global',
    'Resultado Act/Ref',
    'Resultado Sen/Int',
    'Resultado Vis/Vrb',
    'Resultado Seq/Glo',
    'Fecha de Test'
);

// Crear el archivo CSV con nombre elegante usando string de idioma
$course_name = preg_replace('/[^a-z0-9]/i', '_', strtolower($course->shortname));
$date_str = date('Y-m-d');
$filename = get_string('export_filename', 'block_learning_style') . '_' . $course_name . '_' . $date_str . '.csv';

// Configurar cabeceras HTTP para descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Abrir flujo de salida
$output = fopen('php://output', 'w');

// BOM para UTF-8 (para que Excel lo reconozca correctamente)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Escribir cabeceras
fputcsv($output, $headers);

// Agregar los datos de los estudiantes
if (!empty($results)) {
    foreach ($results as $result) {
        $row = array(
            $result->idnumber,
            $result->firstname,
            $result->lastname,
            $result->email,
            $result->username,
            $result->ap_active,
            $result->ap_reflexivo,
            $result->ap_sensorial,
            $result->ap_intuitivo,
            $result->ap_visual,
            $result->ap_verbal,
            $result->ap_secuencial,
            $result->ap_global,
            $result->act_ref,
            $result->sen_int,
            $result->vis_vrb,
            $result->seq_glo,
            userdate($result->created_at, '%Y-%m-%d %H:%M')
        );
        fputcsv($output, $row);
    }
}

// Cerrar y descargar
fclose($output);
exit;
