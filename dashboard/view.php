<?php
require_once(__DIR__ . '/../../../config.php');
defined('MOODLE_INTERNAL') || die();

global $DB, $OUTPUT, $PAGE, $CFG;

$courseid = required_param('id', PARAM_INT);
$PAGE->set_url('/blocks/learning_style/dashboard/view.php', array('id' => $courseid));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($courseid);

// Check permissions
$canview = has_capability('block/learning_style:viewreports', $context)
    || has_capability('moodle/course:viewhiddensections', $context)
    || is_siteadmin();

if (!$canview) {
    $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($redirecturl);
}
$PAGE->set_title(get_string('pluginname', 'block_learning_style'));
$PAGE->set_heading($course->fullname);

// Add CSS
$PAGE->requires->css('/blocks/learning_style/dashboard/css/style.css');

// Add JS
$PAGE->requires->js('/blocks/learning_style/dashboard/js/main.js');

echo $OUTPUT->header();

$embedded_file = $CFG->dirroot . '/blocks/learning_style/dashboard/embedded.php';
if (file_exists($embedded_file)) {
    include($embedded_file);
}

echo $OUTPUT->footer();
?>
