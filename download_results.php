<?php
/**
 * Learning Style Block - Download Results as CSV
 *
 * @package    block_learning_style
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

/**
 * Prevent CSV formula injection in spreadsheet apps.
 */
function block_learning_style_csv_safe($value): string {
    $value = (string)$value;
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        return "'" . $value;
    }
    return $value;
}

// Parámetros de entrada
$courseid = required_param('courseid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);

// Verificar clave de sesión para seguridad (CSRF)
require_sesskey();

// Obtener curso y contexto
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

// Requerir inicio de sesión
require_login($course, false);
require_capability('block/learning_style:viewreports', $context);

// Obtener solo estudiantes (usuarios que pueden tomar el test)
$enrolled_users = get_enrolled_users($context, 'block/learning_style:take_test', 0, 'u.id');
$enrolled_ids = array_keys($enrolled_users);

// Defensive: exclude any teacher/manager-type user even if misconfigured.
$student_ids = array();
foreach ($enrolled_ids as $candidateid) {
    $candidateid = (int)$candidateid;
    
    if (has_capability('block/learning_style:viewreports', $context, $candidateid)) {
        continue;
    }
    $student_ids[] = $candidateid;
}
$enrolled_ids = $student_ids;

// Consulta SQL para obtener los resultados
$results = array();
if (!empty($enrolled_ids)) {
    list($insql, $params) = $DB->get_in_or_equal($enrolled_ids, SQL_PARAMS_NAMED, 'user');
    
    // INNER JOIN para traer solo los completados
    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.username, u.idnumber,
                   ls.ap_active, ls.ap_reflexivo, ls.ap_sensorial, ls.ap_intuitivo,
                   ls.ap_visual, ls.ap_verbal, ls.ap_secuencial, ls.ap_global,
                   ls.act_ref, ls.sen_int, ls.vis_vrb, ls.seq_glo, ls.created_at, ls.updated_at
            FROM {user} u
            INNER JOIN {learning_style} ls ON u.id = ls.user
            WHERE u.deleted = 0 AND u.confirmed = 1 
            AND u.id $insql 
            AND ls.is_completed = 1
            ORDER BY u.lastname, u.firstname";
    
    $results = $DB->get_records_sql($sql, $params);
}

$headers = array(
    get_string('idnumber'), 
    get_string('firstname'),
    get_string('lastname'),
    get_string('email'),
    get_string('username'),
    get_string('active', 'block_learning_style'),
    get_string('reflexive', 'block_learning_style'),
    get_string('sensorial', 'block_learning_style'),
    get_string('intuitive', 'block_learning_style'),
    get_string('visual', 'block_learning_style'),
    get_string('verbal', 'block_learning_style'),
    get_string('sequential', 'block_learning_style'),
    get_string('global', 'block_learning_style'),
    get_string('result_act_ref', 'block_learning_style'),
    get_string('result_sen_int', 'block_learning_style'),
    get_string('result_vis_vrb', 'block_learning_style'),
    get_string('result_seq_glo', 'block_learning_style'),
    get_string('date')
);

// Crear el archivo CSV con nombre limpio
$course_name = preg_replace('/[^a-z0-9]/i', '_', strtolower($course->shortname));
$date_str = date('Y-m-d');
// Usamos un string por defecto si no existe la traducción 'export_filename'
$filename_prefix = get_string_manager()->string_exists('export_filename', 'block_learning_style') 
    ? get_string('export_filename', 'block_learning_style') 
    : 'learning_styles';
    
$filename = $filename_prefix . '_' . $course_name . '_' . $date_str . '.csv';

// Configurar cabeceras HTTP para descarga forzada
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

// Abrir flujo de salida
$output = fopen('php://output', 'w');

// BOM para UTF-8 (para que Excel abra los acentos correctamente)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Escribir cabeceras
fputcsv($output, $headers);

// Agregar los datos
if (!empty($results)) {
    foreach ($results as $result) {
        $row = array(
            block_learning_style_csv_safe($result->idnumber),
            block_learning_style_csv_safe($result->firstname),
            block_learning_style_csv_safe($result->lastname),
            block_learning_style_csv_safe($result->email),
            block_learning_style_csv_safe($result->username),
            block_learning_style_csv_safe($result->ap_active),
            block_learning_style_csv_safe($result->ap_reflexivo),
            block_learning_style_csv_safe($result->ap_sensorial),
            block_learning_style_csv_safe($result->ap_intuitivo),
            block_learning_style_csv_safe($result->ap_visual),
            block_learning_style_csv_safe($result->ap_verbal),
            block_learning_style_csv_safe($result->ap_secuencial),
            block_learning_style_csv_safe($result->ap_global),
            block_learning_style_csv_safe($result->act_ref),
            block_learning_style_csv_safe($result->sen_int),
            block_learning_style_csv_safe($result->vis_vrb),
            block_learning_style_csv_safe($result->seq_glo),
            block_learning_style_csv_safe(userdate(($result->updated_at > 0 ? $result->updated_at : $result->created_at), get_string('strftimedatetime', 'langconfig'))) // Formato de fecha local del usuario
        );
        fputcsv($output, $row);
    }
}

// Cerrar y terminar
fclose($output);
exit;
