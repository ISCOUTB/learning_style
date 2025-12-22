<?php
/**
 * Individual Learning Style View
 *
 * @package    block_learning_style
 * @copyright  2025 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);

// Verificar permisos de administrador o profesor (redirect amigable al curso si no puede ver).
$canview = is_siteadmin()
    || has_capability('block/learning_style:viewreports', $context)
    || has_capability('moodle/course:viewhiddensections', $context);

if (!$canview) {
    $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($redirecturl);
}

// PRIVACY: Teachers/managers in a course must not be able to view other (non-enrolled) users.
if (!is_siteadmin() && !is_enrolled($context, $user, 'block/learning_style:take_test', true)) {
    $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($redirecturl);
}

// Obtener datos del estilo de aprendizaje (en cualquier curso)
$learning_style = $DB->get_record('learning_style', array('user' => $userid));

if (!$learning_style) {
    throw new moodle_exception('learning_style_not_found', 'block_learning_style');
}

// Check if test is completed
if (!$learning_style->is_completed) {
    $PAGE->set_url('/blocks/learning_style/view_individual.php', array('courseid' => $courseid, 'userid' => $userid));
    $PAGE->set_context($context);
    $PAGE->set_course($course);
    $PAGE->set_title(get_string('individual_results_title', 'block_learning_style'));
    $PAGE->set_heading(get_string('individual_results_title', 'block_learning_style'));
    
    echo $OUTPUT->header();
    
    // Count answered questions
    $answered = 0;
    for ($i = 1; $i <= 44; $i++) {
        $field = "q{$i}";
        if (isset($learning_style->$field) && $learning_style->$field !== null) {
            $answered++;
        }
    }
    
    $progress_percentage = round(($answered / 44) * 100, 1);
    
    echo '<div class="container-fluid">';
    echo '<div class="alert alert-warning" role="alert">';
    echo '<h4 class="alert-heading"><i class="fa fa-clock-o"></i> ' . get_string('test_in_progress', 'block_learning_style') . '</h4>';
    echo '<p>' . get_string('test_in_progress_message', 'block_learning_style', fullname($user)) . '</p>';
    echo '<hr>';
    echo '<p class="mb-1"><strong>' . get_string('progress_label', 'block_learning_style') . ':</strong></p>';
    echo '<div class="progress mb-2" style="height: 30px;">';
    echo '<div class="progress-bar bg-warning" role="progressbar" style="width: ' . $progress_percentage . '%" aria-valuenow="' . $progress_percentage . '" aria-valuemin="0" aria-valuemax="100">';
    echo '<strong>' . $progress_percentage . '%</strong>';
    echo '</div>';
    echo '</div>';
    echo '<p><strong>' . get_string('has_answered', 'block_learning_style') . ':</strong> ' . $answered . '/44 ' . get_string('questions', 'block_learning_style') . '</p>';
    
    // Special message if all questions answered but not submitted
    if ($answered == 44) {
        echo '<div class="alert alert-info mt-2" role="alert">';
        echo '<i class="fa fa-info-circle"></i> ';
        echo '<strong>' . get_string('remind_submit_test', 'block_learning_style') . '</strong>';
        echo '</div>';
    }
    
    echo '<p class="mb-0"><em>' . get_string('results_available_when_complete', 'block_learning_style', fullname($user)) . '</em></p>';
    echo '</div>';
    
    echo '</div>';
    
    // Navigation buttons (match personality_test pattern)
    echo html_writer::start_div('mt-5 text-center d-flex gap-3 justify-content-center');
    echo html_writer::link(
        new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid)),
        '<i class="fa fa-arrow-left mr-2"></i>' . get_string('back_to_admin', 'block_learning_style'),
        array('class' => 'btn btn-secondary btn-modern mr-3')
    );
    echo html_writer::link(
        new moodle_url('/course/view.php', array('id' => $courseid)),
        '<i class="fa fa-home mr-2"></i>' . get_string('back_to_course', 'block_learning_style'),
        array('class' => 'btn btn-modern', 'style' => 'background: linear-gradient(135deg, #1567f9 0%, #0054ce 100%); border: none; color: white;')
    );
    echo html_writer::end_div();
    
    echo $OUTPUT->footer();
    exit;
}

$PAGE->set_url('/blocks/learning_style/view_individual.php', array('courseid' => $courseid, 'userid' => $userid));
$PAGE->set_context($context);
$PAGE->set_course($course); 
$PAGE->set_title(get_string('individual_results_title', 'block_learning_style'));
$PAGE->set_heading(get_string('individual_results_title', 'block_learning_style'));
$PAGE->requires->css('/blocks/learning_style/styles.css');

echo $OUTPUT->header();
echo html_writer::start_div('learning-style-individual-container');

// Información del estudiante con diseño moderno
$fullname = fullname($user);
$completion_date = userdate($learning_style->updated_at, get_string('strftimedaydatetime'));

echo "<div class='header-card'>";
echo "<div class='d-flex align-items-center mb-3'>";
echo "<div>";
echo "<h2 class='mb-1' style='color: white;'>" . s($fullname) . "</h2>";
echo "<p class='mb-0' style='color: rgba(255,255,255,0.9); font-size: 1.1rem;'>";
echo "<i class='fa fa-calendar mr-2'></i>" . get_string('completed_on', 'block_learning_style') . ': ' . $completion_date;
echo "</p>";
echo "</div>";
echo "</div>";
echo "<div class='d-flex gap-3 mt-3'>";
echo "<span class='badge bg-white' style='font-size: 1rem; padding: 0.5rem 1rem;'>";
echo "<i class='fa fa-check-circle mr-1' style='background: linear-gradient(135deg, #1567f9 0%, #0054ce 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'></i>";
echo "<span style='background: linear-gradient(135deg, #1567f9 0%, #0054ce 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>" . get_string('test_completed', 'block_learning_style') . "</span>";
echo "</span>";
echo "</div>";
echo "</div>";

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

// Gráficos de barras para cada dimensión con diseño moderno
echo "<h4 class='mb-4 mt-5'><i class='fa fa-chart-bar me-2'></i>" . get_string('learning_dimensions', 'block_learning_style') . "</h4>";
echo html_writer::start_div('row g-4');

foreach ($dimensions as $index => $dimension) {
    $total = $dimension['active'] + $dimension['reflexive'];
    $active_percentage = $total > 0 ? round(($dimension['active'] / $total) * 100, 1) : 0;
    $reflexive_percentage = $total > 0 ? round(($dimension['reflexive'] / $total) * 100, 1) : 0;
    
    echo html_writer::start_div('col-lg-6 mt-5');
    echo html_writer::start_div('dimension-card card');
    echo html_writer::start_div('card-header', array('style' => 'background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%) !important; color: #0054ce;'));
    echo html_writer::tag('h5', '<i class="fa fa-brain"></i>' . $dimension['name'], array('class' => 'mb-0', 'style' => 'font-size: 1.1rem;'));
    echo html_writer::end_div();
    echo html_writer::start_div('card-body p-4');
    
    // Gráfico de barras horizontal moderno
    echo html_writer::start_div('mb-4');
    echo html_writer::tag('div', $dimension['active_label'], 
        array('class' => 'fw-bold mb-2', 'style' => 'font-size: 0.95rem; color: #495057;'));
    echo html_writer::start_div('progress-wrapper', array('data-percentage' => $active_percentage));
    $active_text = $dimension['active'] . ' (' . $active_percentage . '%)';
    $label_class = $active_percentage == 0 ? 'progress-value center-all' : ($active_percentage < 18 ? 'progress-value outside' : 'progress-value inside');
    echo html_writer::tag('span', $active_text, array(
        'class' => $label_class,
        'style' => '--bar-width: ' . $active_percentage . '%;'
    ));
    echo html_writer::tag('div', '', array(
        'class' => 'progress-bar-modern',
        'style' => 'width: ' . $active_percentage . '%; background-color: ' . $dimension['color_active'] . '; position: absolute; left: 0; top: 0; height: 100%; border-radius: 8px;'
    ));
    echo html_writer::end_div();
    
    echo html_writer::tag('div', $dimension['reflexive_label'], 
        array('class' => 'fw-bold mb-2', 'style' => 'font-size: 0.95rem; color: #495057;'));
    echo html_writer::start_div('progress-wrapper', array('data-percentage' => $reflexive_percentage));
    $reflexive_text = $dimension['reflexive'] . ' (' . $reflexive_percentage . '%)';
    $label_class_ref = $reflexive_percentage == 0 ? 'progress-value center-all' : ($reflexive_percentage < 18 ? 'progress-value outside' : 'progress-value inside');
    echo html_writer::tag('span', $reflexive_text, array(
        'class' => $label_class_ref,
        'style' => '--bar-width: ' . $reflexive_percentage . '%;'
    ));
    echo html_writer::tag('div', '', array(
        'class' => 'progress-bar-modern',
        'style' => 'width: ' . $reflexive_percentage . '%; background-color: ' . $dimension['color_reflexive'] . '; position: absolute; left: 0; top: 0; height: 100%; border-radius: 8px;'
    ));
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    // Interpretación moderna
    $dominant_style = $dimension['active'] > $dimension['reflexive'] ? 
        $dimension['active_label'] : $dimension['reflexive_label'];
    $dominant_value = max($dimension['active'], $dimension['reflexive']);
    $total_dimension = $dimension['active'] + $dimension['reflexive'];
    $dominant_percentage = $total_dimension > 0 ? round(($dominant_value / $total_dimension) * 100, 1) : 0;
    
    echo html_writer::start_div('alert', array('style' => 'background-color: #f0f8ff; border-left: 4px solid ' . ($dimension['active'] > $dimension['reflexive'] ? $dimension['color_active'] : $dimension['color_reflexive']) . '; border-radius: 8px;'));
    echo '<i class="fa fa-star mr-2"></i>';
    echo html_writer::tag('strong', get_string('dominant_style', 'block_learning_style') . ': ');
    echo html_writer::tag('span', $dominant_style . ' (' . $dominant_percentage . '%)', 
        array('style' => 'color: ' . ($dimension['active'] > $dimension['reflexive'] ? $dimension['color_active'] : $dimension['color_reflexive']) . '; font-weight: 600;'));
    echo html_writer::end_div();
    
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();

// Resumen del perfil de aprendizaje con diseño moderno
echo html_writer::start_div('profile-summary-card card mt-5 p-4');
echo "<div class='d-flex align-items-center mb-4'>";
echo "<i class='fa fa-lightbulb' style='font-size: 2rem; color: #1567f9;'></i>";
echo html_writer::tag('h4', get_string('learning_profile_summary', 'block_learning_style'), array('class' => 'mb-0'));
echo "</div>";

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

echo html_writer::start_tag('ul', array('class' => 'list-unstyled mb-0'));
foreach ($profile_characteristics as $characteristic) {
    echo "<li class='mb-3' style='font-size: 1.05rem;'>";
    echo "<i class='fa fa-check-circle mr-2' style='color: #28a745;'></i>";
    echo $characteristic;
    echo "</li>";
}
echo html_writer::end_tag('ul');

echo html_writer::end_div();

// Datos técnicos con diseño moderno 
echo html_writer::start_div('card mt-4', array('style' => 'border-radius: 12px; overflow: hidden;'));
echo html_writer::start_div('card-header', array('style' => 'background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%) !important; color: #0054ce;'));
echo "<i class='fa fa-database mr-2'></i>";
echo html_writer::tag('h5', get_string('technical_data', 'block_learning_style'), array('class' => 'mb-0 d-inline ', 'style' => 'color: #0054ce !important;'));
echo html_writer::end_div();
echo html_writer::start_div('card-body p-4');

echo html_writer::start_div('row g-4');

// Active
echo html_writer::start_div('col-6 col-md-3 text-center mb-4');
echo html_writer::tag('div', get_string('dimension_active', 'block_learning_style'), array('style' => 'font-weight: 600; color: #6c757d;'));
echo html_writer::tag('span', $learning_style->ap_active, array('class' => 'technical-badge', 'style' => 'background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white;'));
echo html_writer::end_div();

// Reflexive
echo html_writer::start_div('col-6 col-md-3 text-center mb-4');
echo html_writer::tag('div', get_string('dimension_reflexive', 'block_learning_style'), array('style' => 'font-weight: 600; color: #6c757d;'));
echo html_writer::tag('span', $learning_style->ap_reflexivo, array('class' => 'technical-badge', 'style' => 'background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white;'));
echo html_writer::end_div();

// Sensorial
echo html_writer::start_div('col-6 col-md-3 text-center mb-4');
echo html_writer::tag('div', get_string('dimension_sensorial', 'block_learning_style'), array('style' => 'font-weight: 600; color: #6c757d;'));
echo html_writer::tag('span', $learning_style->ap_sensorial, array('class' => 'technical-badge', 'style' => 'background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white;'));
echo html_writer::end_div();

// Intuitive
echo html_writer::start_div('col-6 col-md-3 text-center mb-4');
echo html_writer::tag('div', get_string('dimension_intuitive', 'block_learning_style'), array('style' => 'font-weight: 600; color: #6c757d;'));
echo html_writer::tag('span', $learning_style->ap_intuitivo, array('class' => 'technical-badge', 'style' => 'background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white;'));
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::start_div('row g-4 mt-2');

// Visual
echo html_writer::start_div('col-6 col-md-3 text-center mb-4');
echo html_writer::tag('div', get_string('dimension_visual', 'block_learning_style'), array('style' => 'font-weight: 600; color: #6c757d;'));
echo html_writer::tag('span', $learning_style->ap_visual, array('class' => 'technical-badge', 'style' => 'background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: white;'));
echo html_writer::end_div();

// Verbal
echo html_writer::start_div('col-6 col-md-3 text-center mb-4');
echo html_writer::tag('div', get_string('dimension_verbal', 'block_learning_style'), array('style' => 'font-weight: 600; color: #6c757d;'));
echo html_writer::tag('span', $learning_style->ap_verbal, array('class' => 'technical-badge', 'style' => 'background: linear-gradient(135deg, #e67e22 0%, #d35400 100%); color: white;'));
echo html_writer::end_div();

// Sequential
echo html_writer::start_div('col-6 col-md-3 text-center mb-4');
echo html_writer::tag('div', get_string('dimension_sequential', 'block_learning_style'), array('style' => 'font-weight: 600; color: #6c757d;'));
echo html_writer::tag('span', $learning_style->ap_secuencial, array('class' => 'technical-badge', 'style' => 'background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%); color: white;'));
echo html_writer::end_div();

// Global
echo html_writer::start_div('col-6 col-md-3 text-center mb-4');
echo html_writer::tag('div', get_string('dimension_global', 'block_learning_style'), array('style' => 'font-weight: 600; color: #6c757d;'));
echo html_writer::tag('span', $learning_style->ap_global, array('class' => 'technical-badge', 'style' => 'background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); color: white;'));
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::end_div();

// Botones de navegación con diseño moderno
echo html_writer::start_div('mt-5 text-center d-flex gap-3 justify-content-center');
echo html_writer::link(
    new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid)),
    '<i class="fa fa-arrow-left mr-2"></i>' . get_string('back_to_admin', 'block_learning_style'),
    array('class' => 'btn btn-secondary mr-3')
);  
echo html_writer::link(
    new moodle_url('/course/view.php', array('id' => $courseid)),
    '<i class="fa fa-home mr-2"></i>' . get_string('back_to_course', 'block_learning_style'),
    array('class' => 'btn', 'style' => 'background: linear-gradient(135deg, #1567f9 0%, #0054ce 100%); border: none; color: white;')
);

echo $OUTPUT->footer();
