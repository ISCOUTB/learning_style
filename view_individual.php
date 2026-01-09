<?php
/**
 * Individual Learning Style View
 *
 * @package    block_learning_style
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
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

// Check if the block is added to the course
if (!$DB->record_exists('block_instances', array('blockname' => 'learning_style', 'parentcontextid' => $context->id))) {
    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
}

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

$PAGE->set_url('/blocks/learning_style/view_individual.php', array('courseid' => $courseid, 'userid' => $userid));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('individual_results_title', 'block_learning_style'));
$PAGE->set_heading(get_string('individual_results_title', 'block_learning_style'));
$PAGE->requires->css('/blocks/learning_style/styles.css');

echo $OUTPUT->header();

// Check if test is completed
if (!$learning_style->is_completed) {
    // Count answered questions
    $answered = 0;
    for ($i = 1; $i <= 44; $i++) {
        $field = "q{$i}";
        if (isset($learning_style->$field) && $learning_style->$field !== null) {
            $answered++;
        }
    }
    
    $progress_percentage = round(($answered / 44) * 100, 1);
    
    $template_data = [
        'user_fullname' => fullname($user),
        'progress_percentage' => $progress_percentage,
        'answered_count' => $answered,
        'all_answered' => ($answered == 44),
        'admin_view_url' => (new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid)))->out(false),
        'course_url' => (new moodle_url('/course/view.php', array('id' => $courseid)))->out(false)
    ];

    echo $OUTPUT->render_from_template('block_learning_style/view_individual_progress', $template_data);
    
    echo $OUTPUT->footer();
    exit;
}

// Completed Results Logic
$fullname = fullname($user);
$completion_date = userdate($learning_style->updated_at, get_string('strftimedaydatetime'));

// Helper function to calculate percentage and label
$calculate_dimension_data = function($active, $reflexive, $label_active, $label_reflexive, $color_active, $color_reflexive, $name) {
    $total = $active + $reflexive;
    $active_pct = $total > 0 ? round(($active / $total) * 100, 1) : 0;
    $reflexive_pct = $total > 0 ? round(($reflexive / $total) * 100, 1) : 0;
    
    // Class logic for label positioning
    // 0% -> center-all
    // < 18% -> outside
    // >= 18% -> inside
    $class_active = $active_pct == 0 ? 'progress-value center-all' : ($active_pct < 18 ? 'progress-value outside' : 'progress-value inside');
    $class_reflexive = $reflexive_pct == 0 ? 'progress-value center-all' : ($reflexive_pct < 18 ? 'progress-value outside' : 'progress-value inside');

    $is_active_dominant = $active > $reflexive;
    
    return [
        'name' => $name,
        'active_label' => $label_active,
        'reflexive_label' => $label_reflexive,
        'active_count' => $active,
        'reflexive_count' => $reflexive,
        'active_percentage' => $active_pct,
        'reflexive_percentage' => $reflexive_pct,
        'color_active' => $color_active,
        'color_reflexive' => $color_reflexive,
        'progress_label_class_active' => $class_active,
        'progress_label_class_reflexive' => $class_reflexive,
        'dominant_style_label' => $is_active_dominant ? $label_active : $label_reflexive,
        'dominant_percentage' => $total > 0 ? round((max($active, $reflexive) / $total) * 100, 1) : 0,
        'dominant_color' => $is_active_dominant ? $color_active : $color_reflexive
    ];
};

$dimensions = [
    $calculate_dimension_data(
        $learning_style->ap_active, $learning_style->ap_reflexivo,
        get_string('active_learner', 'block_learning_style'), get_string('reflexive_learner', 'block_learning_style'),
        '#e74c3c', '#3498db', get_string('processing_dimension', 'block_learning_style')
    ),
    $calculate_dimension_data(
        $learning_style->ap_sensorial, $learning_style->ap_intuitivo,
        get_string('sensorial_learner', 'block_learning_style'), get_string('intuitive_learner', 'block_learning_style'),
        '#27ae60', '#f39c12', get_string('perception_dimension', 'block_learning_style')
    ),
    $calculate_dimension_data(
        $learning_style->ap_visual, $learning_style->ap_verbal,
        get_string('visual_learner', 'block_learning_style'), get_string('verbal_learner', 'block_learning_style'),
        '#9b59b6', '#e67e22', get_string('input_dimension', 'block_learning_style')
    ),
    $calculate_dimension_data(
        $learning_style->ap_secuencial, $learning_style->ap_global,
        get_string('sequential_learner', 'block_learning_style'), get_string('global_learner', 'block_learning_style'),
        '#1abc9c', '#34495e', get_string('understanding_dimension', 'block_learning_style')
    )
];

// Profile Characteristics Strings
$profile_characteristics = [];

if ($learning_style->ap_active > $learning_style->ap_reflexivo) {
    $profile_characteristics[] = get_string('profile_active_description', 'block_learning_style');
} else {
    $profile_characteristics[] = get_string('profile_reflexive_description', 'block_learning_style');
}
if ($learning_style->ap_sensorial > $learning_style->ap_intuitivo) {
    $profile_characteristics[] = get_string('profile_sensorial_description', 'block_learning_style');
} else {
    $profile_characteristics[] = get_string('profile_intuitive_description', 'block_learning_style');
}
if ($learning_style->ap_visual > $learning_style->ap_verbal) {
    $profile_characteristics[] = get_string('profile_visual_description', 'block_learning_style');
} else {
    $profile_characteristics[] = get_string('profile_verbal_description', 'block_learning_style');
}
if ($learning_style->ap_secuencial > $learning_style->ap_global) {
    $profile_characteristics[] = get_string('profile_sequential_description', 'block_learning_style');
} else {
    $profile_characteristics[] = get_string('profile_global_description', 'block_learning_style');
}

$template_data = [
    'fullname' => $fullname,
    'completion_date' => $completion_date,
    'dimensions' => $dimensions,
    'profile_characteristics' => $profile_characteristics,
    'technical_data' => [
        'active' => $learning_style->ap_active,
        'reflexive' => $learning_style->ap_reflexivo,
        'sensorial' => $learning_style->ap_sensorial,
        'intuitive' => $learning_style->ap_intuitivo,
        'visual' => $learning_style->ap_visual,
        'verbal' => $learning_style->ap_verbal,
        'sequential' => $learning_style->ap_secuencial,
        'global' => $learning_style->ap_global
    ],
    'admin_view_url' => (new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid)))->out(false),
    'course_url' => (new moodle_url('/course/view.php', array('id' => $courseid)))->out(false)
];

echo $OUTPUT->render_from_template('block_learning_style/view_individual_results', $template_data);

echo $OUTPUT->footer();
