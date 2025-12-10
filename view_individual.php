<?php
/**
 * Individual Learning Style View
 *
 * @package    block_learning_style
 * @copyright  2024 Planificación Educativa
 * @author     Desenvolvido por Gabriel Haz Sistemas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);

// Verificar permisos de administrador o profesor
$is_teacher = has_capability('moodle/course:manageactivities', $context);
$is_admin = is_siteadmin();

if (!$is_teacher && !$is_admin) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('admin_access_denied', 'block_learning_style'));
}

// Obtener datos del estilo de aprendizaje (en cualquier curso)
$learning_style = $DB->get_record('learning_style', array('user' => $userid));

if (!$learning_style) {
    throw new moodle_exception('learning_style_not_found', 'block_learning_style');
}

$PAGE->set_url('/blocks/learning_style/view_individual.php', array('courseid' => $courseid, 'userid' => $userid));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('individual_results_title', 'block_learning_style'));
$PAGE->set_heading(get_string('individual_results_title', 'block_learning_style'));

echo $OUTPUT->header();

echo html_writer::start_div('learning-style-individual-container');

// Información del estudiante
$fullname = fullname($user);
echo html_writer::tag('h3', get_string('learning_style_results_for', 'block_learning_style') . ': ' . $fullname, 
    array('class' => 'text-primary mb-4'));

// Fecha de completado
$completion_date = userdate($learning_style->updated_at, get_string('strftimedaydatetime'));
echo html_writer::tag('p', get_string('completed_on', 'block_learning_style') . ': ' . $completion_date, 
    array('class' => 'text-muted mb-4'));

// Dimensiones del estilo de aprendizaje
$dimensions = array(
    array(
        'name' => get_string('processing_dimension', 'block_learning_style'),
        'active' => $learning_style->ap_active,
        'reflexive' => $learning_style->ap_reflexivo,
        'active_label' => get_string('active_learner', 'block_learning_style'),
        'reflexive_label' => get_string('reflexive_learner', 'block_learning_style'),
        'color_active' => '#e74c3c',
        'color_reflexive' => '#3498db'
    ),
    array(
        'name' => get_string('perception_dimension', 'block_learning_style'),
        'active' => $learning_style->ap_sensorial,
        'reflexive' => $learning_style->ap_intuitivo,
        'active_label' => get_string('sensorial_learner', 'block_learning_style'),
        'reflexive_label' => get_string('intuitive_learner', 'block_learning_style'),
        'color_active' => '#27ae60',
        'color_reflexive' => '#f39c12'
    ),
    array(
        'name' => get_string('input_dimension', 'block_learning_style'),
        'active' => $learning_style->ap_visual,
        'reflexive' => $learning_style->ap_verbal,
        'active_label' => get_string('visual_learner', 'block_learning_style'),
        'reflexive_label' => get_string('verbal_learner', 'block_learning_style'),
        'color_active' => '#9b59b6',
        'color_reflexive' => '#e67e22'
    ),
    array(
        'name' => get_string('understanding_dimension', 'block_learning_style'),
        'active' => $learning_style->ap_secuencial,
        'reflexive' => $learning_style->ap_global,
        'active_label' => get_string('sequential_learner', 'block_learning_style'),
        'reflexive_label' => get_string('global_learner', 'block_learning_style'),
        'color_active' => '#1abc9c',
        'color_reflexive' => '#34495e'
    )
);

// Gráficos de barras para cada dimensión
echo html_writer::start_div('row');

foreach ($dimensions as $index => $dimension) {
    $total = $dimension['active'] + $dimension['reflexive'];
    $active_percentage = $total > 0 ? round(($dimension['active'] / $total) * 100, 1) : 0;
    $reflexive_percentage = $total > 0 ? round(($dimension['reflexive'] / $total) * 100, 1) : 0;
    
    echo html_writer::start_div('col-lg-6 mb-4');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-header bg-secondary text-white');
    echo html_writer::tag('h6', $dimension['name'], array('class' => 'mb-0'));
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    
    // Gráfico de barras horizontal
    echo html_writer::start_div('mb-3');
    echo html_writer::tag('div', $dimension['active_label'] . ': ' . $dimension['active'] . ' (' . $active_percentage . '%)', 
        array('class' => 'small fw-bold mb-1'));
    echo html_writer::start_div('progress mb-2', array('style' => 'height: 20px;'));
    echo html_writer::tag('div', '', array(
        'class' => 'progress-bar',
        'style' => 'width: ' . $active_percentage . '%; background-color: ' . $dimension['color_active'] . ';'
    ));
    echo html_writer::end_div();
    
    echo html_writer::tag('div', $dimension['reflexive_label'] . ': ' . $dimension['reflexive'] . ' (' . $reflexive_percentage . '%)', 
        array('class' => 'small fw-bold mb-1'));
    echo html_writer::start_div('progress', array('style' => 'height: 20px;'));
    echo html_writer::tag('div', '', array(
        'class' => 'progress-bar',
        'style' => 'width: ' . $reflexive_percentage . '%; background-color: ' . $dimension['color_reflexive'] . ';'
    ));
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    // Interpretación
    $dominant_style = $dimension['active'] > $dimension['reflexive'] ? 
        $dimension['active_label'] : $dimension['reflexive_label'];
    $dominant_value = max($dimension['active'], $dimension['reflexive']);
    $total_dimension = $dimension['active'] + $dimension['reflexive'];
    $dominant_percentage = $total_dimension > 0 ? round(($dominant_value / $total_dimension) * 100, 1) : 0;
    
    echo html_writer::start_div('alert alert-light');
    echo html_writer::tag('strong', get_string('dominant_style', 'block_learning_style') . ': ');
    echo html_writer::tag('span', $dominant_style . ' (' . $dominant_percentage . '%)', 
        array('class' => 'text-primary'));
    echo html_writer::end_div();
    
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();

// Resumen del perfil de aprendizaje
echo html_writer::start_div('card mt-4');
echo html_writer::start_div('card-header bg-info text-white');
echo html_writer::tag('h5', get_string('learning_profile_summary', 'block_learning_style'), array('class' => 'mb-0'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');

$profile_characteristics = array();

// Procesamiento
if ($learning_style->ap_active > $learning_style->ap_reflexivo) {
    $profile_characteristics[] = get_string('profile_active_description', 'block_learning_style');
} else {
    $profile_characteristics[] = get_string('profile_reflexive_description', 'block_learning_style');
}

// Percepción
if ($learning_style->ap_sensorial > $learning_style->ap_intuitivo) {
    $profile_characteristics[] = get_string('profile_sensorial_description', 'block_learning_style');
} else {
    $profile_characteristics[] = get_string('profile_intuitive_description', 'block_learning_style');
}

// Entrada
if ($learning_style->ap_visual > $learning_style->ap_verbal) {
    $profile_characteristics[] = get_string('profile_visual_description', 'block_learning_style');
} else {
    $profile_characteristics[] = get_string('profile_verbal_description', 'block_learning_style');
}

// Comprensión
if ($learning_style->ap_secuencial > $learning_style->ap_global) {
    $profile_characteristics[] = get_string('profile_sequential_description', 'block_learning_style');
} else {
    $profile_characteristics[] = get_string('profile_global_description', 'block_learning_style');
}

echo html_writer::start_tag('ul', array('class' => 'list-unstyled'));
foreach ($profile_characteristics as $characteristic) {
    echo html_writer::tag('li', '• ' . $characteristic, array('class' => 'mb-2'));
}
echo html_writer::end_tag('ul');

echo html_writer::end_div();
echo html_writer::end_div();

// Datos técnicos
echo html_writer::start_div('card mt-4');
echo html_writer::start_div('card-header bg-dark text-white');
echo html_writer::tag('h6', get_string('technical_data', 'block_learning_style'), array('class' => 'mb-0'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3');
echo html_writer::tag('strong', get_string('dimension_active', 'block_learning_style') . ':');
echo html_writer::tag('br', '');
echo html_writer::tag('span', $learning_style->ap_active, array('class' => 'badge bg-primary'));
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('strong', get_string('dimension_reflexive', 'block_learning_style') . ':');
echo html_writer::tag('br', '');
echo html_writer::tag('span', $learning_style->ap_reflexivo, array('class' => 'badge bg-info'));
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('strong', get_string('dimension_sensorial', 'block_learning_style') . ':');
echo html_writer::tag('br', '');
echo html_writer::tag('span', $learning_style->ap_sensorial, array('class' => 'badge bg-success'));
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('strong', get_string('dimension_intuitive', 'block_learning_style') . ':');
echo html_writer::tag('br', '');
echo html_writer::tag('span', $learning_style->ap_intuitivo, array('class' => 'badge bg-warning'));
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('row mt-3');
echo html_writer::start_div('col-md-3');
echo html_writer::tag('strong', get_string('dimension_visual', 'block_learning_style') . ':');
echo html_writer::tag('br', '');
echo html_writer::tag('span', $learning_style->ap_visual, array('class' => 'badge bg-secondary'));
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('strong', get_string('dimension_verbal', 'block_learning_style') . ':');
echo html_writer::tag('br', '');
echo html_writer::tag('span', $learning_style->ap_verbal, array('class' => 'badge bg-dark'));
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('strong', get_string('dimension_sequential', 'block_learning_style') . ':');
echo html_writer::tag('br', '');
echo html_writer::tag('span', $learning_style->ap_secuencial, array('class' => 'badge bg-primary'));
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('strong', get_string('dimension_global', 'block_learning_style') . ':');
echo html_writer::tag('br', '');
echo html_writer::tag('span', $learning_style->ap_global, array('class' => 'badge bg-info'));
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

// Botones de navegación
echo html_writer::start_div('mt-4 text-center');
echo html_writer::link(
    new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid)),
    get_string('back_to_admin', 'block_learning_style'),
    array('class' => 'btn btn-secondary me-2')
);
echo html_writer::link(
    new moodle_url('/course/view.php', array('id' => $courseid)),
    get_string('back_to_course', 'block_learning_style'),
    array('class' => 'btn btn-primary')
);
echo html_writer::end_div();

echo html_writer::end_div();

echo $OUTPUT->footer();
