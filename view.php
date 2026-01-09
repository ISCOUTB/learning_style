<?php
/**
 * Learning Style Test View
 *
 * @package    block_learning_style
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('cid', PARAM_INT);
$page = optional_param('page', 1, PARAM_INT);
$error  = optional_param('error', 0, PARAM_INT);
$scroll_to_finish = optional_param('scroll_to_finish', 0, PARAM_INT);

if ($courseid == SITEID && !$courseid) {
    redirect($CFG->wwwroot);
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($course);
$context = context_course::instance($courseid);
$PAGE->set_context($context);

require_login($course);
require_capability('block/learning_style:take_test', $context);

// Check if the block is added to the course
if (!$DB->record_exists('block_instances', array('blockname' => 'learning_style', 'parentcontextid' => $context->id))) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}

// Redirect teachers/admins to admin page
if (has_capability('block/learning_style:viewreports', $context)) {
    $manage_url = new moodle_url('/blocks/learning_style/admin_view.php', array('cid' => $courseid));
    redirect($manage_url, get_string('teachers_redirect_message', 'block_learning_style'));
}

// Check for existing response
$existing_response = $DB->get_record('learning_style', array('user' => $USER->id));

// If test is completed, redirect to results
if ($existing_response && $existing_response->is_completed) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)), 
             get_string('redirect_accept_exist', 'block_learning_style'), 
             null, \core\output\notification::NOTIFY_INFO);
}
  
$PAGE->set_url('/blocks/learning_style/view.php', array('cid'=>$courseid, 'page'=>$page));

$title = get_string('pluginname', 'block_learning_style');

$PAGE->set_pagelayout('incourse');
$PAGE->set_title($title." : ".$course->fullname);
$PAGE->set_heading($title." : ".$course->fullname);

// Load plugin CSS via Moodle (avoid echoing <link> after header).
$PAGE->requires->css(new moodle_url('/blocks/learning_style/styles.css'));

// Pagination settings
$questions_per_page = 11;
$total_questions = 44;
$total_pages = ceil($total_questions / $questions_per_page);

// Save logic helper
function get_answers_from_post() {
    $responses = array();
    // We don't check all_answered here, just return what is present
    for ($i = 1; $i <= 44; $i++) {
        $response_raw = optional_param('q' . $i, null, PARAM_RAW);
        if ($response_raw !== null && $response_raw !== '') {
            $responses["q{$i}"] = (int)$response_raw;
        }
    }
    return $responses;
}

$action = optional_param('action', '', PARAM_ALPHA); // 'autosave', 'previous', 'next', 'finish'
$ispost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

if ($ispost && $action !== '') {
    // Same security boundary as save.php.
    require_sesskey();

    // If test already completed, block finishing again (cross-course).
    if ($existing_response && $existing_response->is_completed && $action === 'finish') {
        $redirect_url = new moodle_url('/course/view.php', array('id' => $courseid));
        redirect($redirect_url, get_string('redirect_accept_exist', 'block_learning_style'), null, \core\output\notification::NOTIFY_INFO);
    }

    $responses = get_answers_from_post();

    // For autosave, allow partial data
    if ($action === 'autosave') {
        header('Content-Type: application/json; charset=utf-8');

        // Save partial progress
        $data = new stdClass();
        $data->user = $USER->id;
        $data->is_completed = 0;
        $data->updated_at = time();

        if ($existing_response) {
            $data->id = $existing_response->id;
            $data->created_at = $existing_response->created_at;
            // Copy all existing answers from DB first
            for ($i = 1; $i <= 44; $i++) {
                $field = "q{$i}";
                if (isset($existing_response->$field) && $existing_response->$field !== null) {
                    $data->$field = $existing_response->$field;
                }
            }
        } else {
            $data->created_at = time();
        }

        // Update with answered questions from current POST
        foreach ($responses as $field => $value) {
            $data->$field = $value;
        }

        try {
            if ($existing_response) {
                $DB->update_record('learning_style', $data);
            } else {
                $DB->insert_record('learning_style', $data);
            }
            echo json_encode(['success' => true, 'answered' => count($responses)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // For navigation (previous/next), save progress and redirect
    if ($action === 'previous' || $action === 'next') {
        $data = new stdClass();
        $data->user = $USER->id;
        $data->is_completed = 0;
        $data->updated_at = time();

        if ($existing_response) {
            $data->id = $existing_response->id;
            $data->created_at = $existing_response->created_at;
            // Copy all existing answers
            for ($i = 1; $i <= 44; $i++) {
                $field = "q{$i}";
                if (isset($existing_response->$field) && $existing_response->$field !== null) {
                    $data->$field = $existing_response->$field;
                }
            }
        } else {
            $data->created_at = time();
        }

        // Update with new answers from current page
        foreach ($responses as $field => $value) {
            $data->$field = $value;
        }

        try {
            if ($existing_response) {
                $DB->update_record('learning_style', $data);
            } else {
                $DB->insert_record('learning_style', $data);
            }

            // Calculate new page
            $new_page = ($action === 'previous') ? $page - 1 : $page + 1;
            // Boundary checks
            if ($new_page < 1) $new_page = 1;
            if ($new_page > $total_pages) $new_page = $total_pages;

            $redirect_url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $courseid, 'page' => $new_page));
            redirect($redirect_url, get_string('progress_saved', 'block_learning_style'), null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (Exception $e) {
            $redirect_url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $courseid, 'page' => $page, 'error' => 1));
            redirect($redirect_url, 'Error: ' . $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
        }
        exit;
    }

    // For finish, validate all questions are answered and calculate results
    if ($action === 'finish') {
        // Double check: validate from DB + current submission
        $existing = $DB->get_record('learning_style', array('user' => $USER->id));
        $learning_style_a = array();
        
        // Populate full answer set
        $final_responses = [];

        // Merge all answers (DB + current submission)
        for ($i = 1; $i <= 44; $i++) {
            $field = "q{$i}";
            $val = null;

            // Use current submission if available
            if (isset($responses[$field])) {
                $val = $responses[$field];
            } 
            // Otherwise, try from DB
            elseif ($existing && isset($existing->$field) && $existing->$field !== null) {
                $val = $existing->$field;
            }

            if ($val !== null) {
                $final_responses[$field] = $val;
                $learning_style_a[$i] = $val;
            }
        }

        // Find first unanswered question
        $first_unanswered = null;
        for ($i = 1; $i <= 44; $i++) {
            if (!isset($learning_style_a[$i])) {
                $first_unanswered = $i;
                break;
            }
        }

        // If any question is unanswered, redirect to that page
        if ($first_unanswered !== null) {
            $redirect_page = ceil($first_unanswered / $questions_per_page);
            $redirect_url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $courseid, 'page' => $redirect_page));
            redirect($redirect_url, get_string('all_questions_required', 'block_learning_style'), null, \core\output\notification::NOTIFY_ERROR);
        }

        // Calculate results logic
        $act_ref_eval = [1,5,9,13,17,21,25,29,33,37,41];
        $act_ref = ["a" => 0, "b" => 0, "result" => ""];

        $sen_int_eval = [2,6,10,14,18,22,26,30,34,38,42];
        $sen_int = ["a" => 0, "b" => 0, "result" => ""];

        $vis_vrb_eval = [3,7,11,15,19,23,27,31,35,39,43];
        $vis_vrb = ["a" => 0, "b" => 0, "result" => ""];

        $seq_glo_eval = [4,8,12,16,20,24,28,32,36,40,44];
        $seq_glo = ["a" => 0, "b" => 0, "result" => ""];

        foreach($act_ref_eval as $item){
            if ($learning_style_a[$item] == 0) $act_ref["a"]++; else $act_ref["b"]++;
        }
        $act_ref["result"] = ($act_ref["a"] > $act_ref["b"]) ? ($act_ref["a"] - $act_ref["b"]) . "a" : ($act_ref["b"] - $act_ref["a"]) . "b";

        foreach($sen_int_eval as $item){
            if ($learning_style_a[$item] == 0) $sen_int["a"]++; else $sen_int["b"]++;
        }
        $sen_int["result"] = ($sen_int["a"] > $sen_int["b"]) ? ($sen_int["a"] - $sen_int["b"]) . "a" : ($sen_int["b"] - $sen_int["a"]) . "b";

        foreach($vis_vrb_eval as $item){
            if ($learning_style_a[$item] == 0) $vis_vrb["a"]++; else $vis_vrb["b"]++;
        }
        $vis_vrb["result"] = ($vis_vrb["a"] > $vis_vrb["b"]) ? ($vis_vrb["a"] - $vis_vrb["b"]) . "a" : ($vis_vrb["b"] - $vis_vrb["a"]) . "b";

        foreach($seq_glo_eval as $item){
            if ($learning_style_a[$item] == 0) $seq_glo["a"]++; else $seq_glo["b"]++;
        }
        $seq_glo["result"] = ($seq_glo["a"] > $seq_glo["b"]) ? ($seq_glo["a"] - $seq_glo["b"]) . "a" : ($seq_glo["b"] - $seq_glo["a"]) . "b";

        // Prepare data object
        $data = new stdClass();
        $data->user = $USER->id;
        $data->is_completed = 1;
        $data->updated_at = time();

        foreach ($final_responses as $field => $value) {
            $data->$field = $value;
        }

        $data->act_ref = $act_ref["result"];
        $data->sen_int = $sen_int["result"];
        $data->vis_vrb = $vis_vrb["result"];
        $data->seq_glo = $seq_glo["result"];

        $data->ap_active = $act_ref["a"];
        $data->ap_reflexivo = $act_ref["b"];
        $data->ap_sensorial = $sen_int["a"];
        $data->ap_intuitivo = $sen_int["b"];
        $data->ap_visual = $vis_vrb["a"];
        $data->ap_verbal = $vis_vrb["b"];
        $data->ap_secuencial = $seq_glo["a"];
        $data->ap_global = $seq_glo["b"];

        $redirect_url = new moodle_url('/course/view.php', array('id' => $courseid));

        try {
            if ($existing_response) {
                $data->id = $existing_response->id;
                $data->created_at = $existing_response->created_at;
                $DB->update_record('learning_style', $data);
                redirect($redirect_url, get_string('redirect_accept_success', 'block_learning_style'));
            } else {
                $data->created_at = time();
                $DB->insert_record('learning_style', $data);
                redirect($redirect_url, get_string('redirect_accept_success', 'block_learning_style'));
            }
        } catch (Exception $e) {
            redirect($redirect_url, get_string('redirect_accept_exist', 'block_learning_style'), null, \core\output\notification::NOTIFY_ERROR);
        }
        exit;
    }
}

// SECURITY: Validate that user cannot skip pages without completing previous ones
if ($existing_response && $page > 1) {
    $max_allowed_page = 1;
    for ($p = 1; $p < $page; $p++) {
        $page_start = ($p - 1) * $questions_per_page + 1;
        $page_end = min($p * $questions_per_page, $total_questions);
        $page_complete = true;
        
        for ($i = $page_start; $i <= $page_end; $i++) {
            $field = "q{$i}";
            if (!isset($existing_response->$field) || $existing_response->$field === null) {
                $page_complete = false;
                break;
            }
        }
        
        if ($page_complete) {
            $max_allowed_page = $p + 1;
        } else {
            break;
        }
    }
    
    if ($page > $max_allowed_page) {
        redirect(new moodle_url('/blocks/learning_style/view.php',
                 array('cid' => $courseid, 'page' => $max_allowed_page)));
    }
}

// If coming from "continue test" link, calculate which page to show
if ($existing_response && !isset($_GET['page'])) {
    $first_unanswered = null;
    for ($i = 1; $i <= $total_questions; $i++) {
        $field = "q{$i}";
        if (!isset($existing_response->$field) || $existing_response->$field === null) {
            $first_unanswered = $i;
            break;
        }
    }
    if ($first_unanswered !== null) {
        $page = ceil($first_unanswered / $questions_per_page);
    }
}

$start_question = ($page - 1) * $questions_per_page + 1;
$end_question = min($page * $questions_per_page, $total_questions);

// Calculate how many questions are answered
$answered_count = 0;
if ($existing_response) {
    for ($i = 1; $i <= $total_questions; $i++) {
        $field = "q{$i}";
        if (isset($existing_response->$field) && $existing_response->$field !== null) {
            $answered_count++;
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');

$iconurl = new moodle_url('/blocks/learning_style/pix/icon.svg');

// Prepare data for mustache template
$template_data = [
    'icon_url' => $iconurl->out(),
    'action_url' => $PAGE->url->out(false),
    'courseid' => $courseid,
    'page' => $page,
    'sesskey' => sesskey(),
    'has_previous' => $page > 1,
    'has_next' => $page < $total_pages,
    'show_finish' => $page == $total_pages,
    'questions' => []
];

for ($i = $start_question; $i <= $end_question; $i++) {
    $field = "q{$i}";
    $saved_value = ($existing_response && isset($existing_response->$field)) ? $existing_response->$field : null;
    
    $template_data['questions'][] = [
        'number' => $i,
        'question_text' => get_string("learning_style:q".$i, 'block_learning_style'),
        'option_a' => get_string('learning_style:q'.$i.'_a', 'block_learning_style'),
        'option_b' => get_string('learning_style:q'.$i.'_b', 'block_learning_style'),
        'is_unanswered' => ($saved_value === null),
        'is_a' => ($saved_value === '0' || $saved_value === 0),
        'is_b' => ($saved_value === '1' || $saved_value === 1)
    ];
}

echo $OUTPUT->render_from_template('block_learning_style/view', $template_data);

// Init JS Handler
$js_params = [
    'formUrl' => $PAGE->url->out(false),
    'shouldAutoScroll' => ($existing_response && $answered_count > 0 && $answered_count < 44 && !$scroll_to_finish),
    'scrollToFinish' => (bool)$scroll_to_finish
];

$PAGE->requires->js_call_amd('block_learning_style/view_handler', 'init', [$js_params]);

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
