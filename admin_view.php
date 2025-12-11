<?php
/**
 * Learning Style Admin View
 *
 * @package    block_learning_style
 * @copyright  2024 Planificación Educativa
 * @author     Desenvolvido por Gabriel Haz Sistemas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);

// Verificar permisos de administrador o profesor
$is_teacher = has_capability('moodle/course:manageactivities', $context);
$is_admin = is_siteadmin();

if (!$is_teacher && !$is_admin) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('admin_access_denied', 'block_learning_style'));
}

$PAGE->set_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('admin_title', 'block_learning_style'));
$PAGE->set_heading(get_string('admin_title', 'block_learning_style'));

// Manejar acciones de eliminación
if ($action === 'delete' && $userid && confirm_sesskey()) {
    if (has_capability('moodle/course:manageactivities', $context)) {
        // Delete global test result for this user
        $DB->delete_records('learning_style', array('user' => $userid));
        redirect($PAGE->url, get_string('learning_style_deleted', 'block_learning_style'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();

// Título y navegación
echo html_writer::start_div('learning-style-admin-container');
echo html_writer::tag('h3', get_string('admin_title', 'block_learning_style'), array('class' => 'text-primary mb-4'));

// Estadísticas generales
// Get enrolled students in this course
$context = context_course::instance($courseid);
$enrolled_students = get_enrolled_users($context, '', 0, 'u.id');
$enrolled_ids = array_keys($enrolled_students);

// Count participants who are enrolled in this course AND completed the test
$total_participants = 0;
$total_in_progress = 0;
if (!empty($enrolled_ids)) {
    list($insql, $params) = $DB->get_in_or_equal($enrolled_ids, SQL_PARAMS_NAMED);
    
    // Count completed tests (is_completed = 1)
    $params_completed = array_merge($params, ['completed' => 1]);
    $total_participants = $DB->count_records_select('learning_style', "user $insql AND is_completed = :completed", $params_completed);
    
    // Count in progress tests (is_completed = 0)
    $params_progress = array_merge($params, ['completed' => 0]);
    $total_in_progress = $DB->count_records_select('learning_style', "user $insql AND is_completed = :completed", $params_progress);
}

// Estadísticas por dimensión
$stats = array();
$dimensions = array(
    'ap_active' => get_string('dimension_active', 'block_learning_style'),
    'ap_reflexivo' => get_string('dimension_reflexive', 'block_learning_style'),
    'ap_sensorial' => get_string('dimension_sensorial', 'block_learning_style'),
    'ap_intuitivo' => get_string('dimension_intuitive', 'block_learning_style'),
    'ap_visual' => get_string('dimension_visual', 'block_learning_style'),
    'ap_verbal' => get_string('dimension_verbal', 'block_learning_style'),
    'ap_secuencial' => get_string('dimension_sequential', 'block_learning_style'),
    'ap_global' => get_string('dimension_global', 'block_learning_style')
);

foreach ($dimensions as $dimension => $label) {
    $avg = null;
    if (!empty($enrolled_ids)) {
        list($insql, $params) = $DB->get_in_or_equal($enrolled_ids, SQL_PARAMS_NAMED);
        $params_completed = array_merge($params, ['completed' => 1]);
        $avg = $DB->get_record_sql(
            "SELECT AVG({$dimension}) as average FROM {learning_style} WHERE user $insql AND is_completed = :completed",
            $params_completed
        );
    }
    $stats[$dimension] = round($avg->average ?? 0, 2);
}

// Tarjeta de estadísticas
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-header bg-info text-white');
echo html_writer::tag('h5', get_string('admin_statistics', 'block_learning_style'), array('class' => 'mb-0'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');

echo html_writer::start_div('row mb-3');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('p', get_string('total_participants', 'block_learning_style') . ': ' . html_writer::tag('strong', $total_participants), array('class' => 'mb-2'));
echo html_writer::end_div();
echo html_writer::start_div('col-md-6');
echo html_writer::tag('p', get_string('in_progress', 'block_learning_style') . ': ' . html_writer::tag('strong', $total_in_progress), array('class' => 'mb-2'));
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('row');
foreach ($stats as $dimension => $value) {
    echo html_writer::start_div('col-md-3 mb-2');
    echo html_writer::tag('small', $dimensions[$dimension] . ': ', array('class' => 'text-muted'));
    echo html_writer::tag('strong', $value);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

$userfieldsapi = \core_user\fields::for_name();
$userfields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;

$participants = array();
if (!empty($enrolled_ids)) {
    list($insql, $params) = $DB->get_in_or_equal($enrolled_ids, SQL_PARAMS_NAMED);
    
    $sql = "SELECT ls.*, {$userfields}
            FROM {learning_style} ls
            JOIN {user} u ON ls.user = u.id
            WHERE ls.user $insql
            ORDER BY ls.updated_at DESC";
    
    $participants = $DB->get_records_sql($sql, $params);
}

// Tabla de participantes
echo html_writer::start_div('card');
echo html_writer::start_div('card-header bg-primary text-white');
echo html_writer::tag('h5', get_string('participants_list', 'block_learning_style'), array('class' => 'mb-0'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');

if (!empty($participants)) {
    echo html_writer::start_tag('table', array('class' => 'table table-striped table-hover'));
    echo html_writer::start_tag('thead', array('class' => 'table-dark'));
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('participant', 'block_learning_style'));
    echo html_writer::tag('th', get_string('completion_date', 'block_learning_style'));
    echo html_writer::tag('th', get_string('learning_profile', 'block_learning_style'));
    echo html_writer::tag('th', get_string('actions', 'block_learning_style'), array('class' => 'text-center'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($participants as $participant) {
        $fullname = fullname($participant);
        $completion_date = userdate($participant->updated_at, get_string('strftimedaydatetime'));
        
        // Check if completed or in progress
        $status = $participant->is_completed ? get_string('completed_status', 'block_learning_style') : get_string('in_progress_status', 'block_learning_style');
        $status_badge = $participant->is_completed ? 'badge bg-success' : 'badge bg-warning text-dark';
        
        // Determinar perfil de aprendizaje predominante (solo si está completado)
        $profile_summary = array();
        if ($participant->is_completed) {
            if ($participant->ap_active > $participant->ap_reflexivo) {
                $profile_summary[] = get_string('profile_active', 'block_learning_style');
            } else {
                $profile_summary[] = get_string('profile_reflexive', 'block_learning_style');
            }
            
            if ($participant->ap_sensorial > $participant->ap_intuitivo) {
                $profile_summary[] = get_string('profile_sensorial', 'block_learning_style');
            } else {
                $profile_summary[] = get_string('profile_intuitive', 'block_learning_style');
            }
            
            if ($participant->ap_visual > $participant->ap_verbal) {
                $profile_summary[] = get_string('profile_visual', 'block_learning_style');
            } else {
                $profile_summary[] = get_string('profile_verbal', 'block_learning_style');
            }
            
            if ($participant->ap_secuencial > $participant->ap_global) {
                $profile_summary[] = get_string('profile_sequential', 'block_learning_style');
            } else {
                $profile_summary[] = get_string('profile_global', 'block_learning_style');
            }
        } else {
            // Count answered questions for progress
            $answered = 0;
            for ($i = 1; $i <= 44; $i++) {
                $field = "q{$i}";
                if (isset($participant->$field) && $participant->$field !== null) {
                    $answered++;
                }
            }
            $profile_summary[] = get_string('progress_label', 'block_learning_style') . ": {$answered}/44 " . get_string('questions_label', 'block_learning_style');
        }

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $fullname . ' ' . html_writer::tag('span', $status, array('class' => $status_badge)));
        echo html_writer::tag('td', $completion_date);
        echo html_writer::tag('td', implode(', ', $profile_summary));
        
        // Acciones
        echo html_writer::start_tag('td', array('class' => 'text-center'));
        
        // Botón ver detalles
        $view_url = new moodle_url('/blocks/learning_style/view_individual.php', 
            array('courseid' => $courseid, 'userid' => $participant->user));
        echo html_writer::link($view_url, 
            get_string('view_details', 'block_learning_style'),
            array('class' => 'btn btn-sm btn-info me-1')
        );
        
        // Botón eliminar (solo para profesores/administradores)
        if (has_capability('moodle/course:manageactivities', $context)) {
            $delete_url = new moodle_url('/blocks/learning_style/admin_view.php', 
                array('courseid' => $courseid, 'action' => 'delete', 'userid' => $participant->user, 'sesskey' => sesskey()));
            echo html_writer::link($delete_url, 
                get_string('delete', 'core'),
                array(
                    'class' => 'btn btn-sm btn-danger',
                    'onclick' => 'return confirm("' . get_string('confirm_delete_learning_style', 'block_learning_style') . '");'
                )
            );
        }
        
        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo html_writer::tag('p', get_string('no_participants', 'block_learning_style'), array('class' => 'text-muted text-center'));
}

echo html_writer::end_div();
echo html_writer::end_div();

// Botón para volver al curso
echo html_writer::start_div('mt-4 text-center');
echo html_writer::link(
    new moodle_url('/course/view.php', array('id' => $courseid)),
    get_string('back_to_course', 'block_learning_style'),
    array('class' => 'btn btn-secondary')
);
echo html_writer::end_div();

echo html_writer::end_div();

echo $OUTPUT->footer();
