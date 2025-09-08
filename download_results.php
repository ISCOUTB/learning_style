<?php

require_once('../../config.php');
require_once($CFG->libdir.'/csvlib.class.php');

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

// Consulta SQL para obtener los resultados de los estudiantes
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.username,
               ls.ap_active, ls.ap_reflexivo, ls.ap_sensorial, ls.ap_intuitivo,
               ls.ap_visual, ls.ap_verbal, ls.ap_secuencial, ls.ap_global,
               ls.act_ref, ls.sen_int, ls.vis_vrb, ls.seq_glo, ls.created_at
        FROM {user} u
        INNER JOIN {learning_style} ls ON u.id = ls.user
        INNER JOIN {enrol} e ON e.courseid = ?
        INNER JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = u.id)
        WHERE u.deleted = 0 AND u.confirmed = 1 AND ls.course = ?
        ORDER BY u.lastname, u.firstname";

$params = array($courseid, $courseid);
$results = $DB->get_records_sql($sql, $params);

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

// Crear el archivo CSV
$filename = 'learning_styles_course_' . $courseid . '_' . date('Y-m-d') . '.csv';
$csvexport = new csv_export_writer();
$csvexport->set_filename($filename);
$csvexport->add_data($headers);

// Agregar los datos de los estudiantes
if (!empty($results)) {
    foreach ($results as $result) {
        $row = array(
            $result->id,
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
        $csvexport->add_data($row);
    }
}

// Descargar el archivo
$csvexport->download_file();
exit;
