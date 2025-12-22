<?php
// metrics.php - returns JSON metrics for the learning_style dashboard
// Permissioned endpoint: requires login, sesskey and view capability.
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../blocks/learning_style/lib.php';

// courseid is required
$courseid = required_param('courseid', PARAM_INT);

// require login in the context of the course
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);

// CSRF protection (even for read-only JSON)
require_sesskey();

$context = context_course::instance($courseid);

$canview = has_capability('block/learning_style:viewreports', $context)
    || has_capability('moodle/course:viewhiddensections', $context)
    || is_siteadmin();

if (!$canview) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'nopermissions']);
    exit;
}

$data = get_metrics($courseid);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
exit;
