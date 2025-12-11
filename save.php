<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login();
require_sesskey();

$courseid = required_param('cid', PARAM_INT);
$action = optional_param('action', 'complete', PARAM_ALPHA); // 'autosave' or 'complete'

if ($courseid == SITEID && !$courseid) {
    if ($action === 'autosave') {
        echo json_encode(['success' => false, 'error' => 'Invalid course']);
        exit;
    }
    redirect($CFG->wwwroot);
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Check if user already completed the test (cross-course)
$existing_response = $DB->get_record('learning_style', array('user' => $USER->id));

if ($existing_response && $existing_response->is_completed && $action === 'complete') {
    $redirect_url = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($redirect_url, get_string('redirect_accept_exist', 'block_learning_style'), null, \core\output\notification::NOTIFY_INFO);
}

// Collect all 44 responses
$responses = array();
$learning_style_a = array();
$all_answered = true;

for ($i = 1; $i <= 44; $i++) {
    $response = optional_param("learning_style:q" . $i, null, PARAM_INT);
    $learning_style_a[$i] = $response;
    if ($response !== null) {
        $responses["q{$i}"] = $response;
    }
    
    if ($response === null) {
        $all_answered = false;
    }
}

// For autosave, allow partial data
if ($action === 'autosave') {
    // Save partial progress
    $data = new stdClass();
    $data->user = $USER->id;
    $data->course = $courseid;
    $data->state = 1;
    $data->is_completed = 0;
    $data->timemodified = time();
    
    // Add only answered questions
    foreach ($responses as $field => $value) {
        $data->$field = $value;
    }
    
    try {
        if ($existing_response) {
            $data->id = $existing_response->id;
            $data->created_at = $existing_response->created_at;
            $data->updated_at = time();
            $DB->update_record('learning_style', $data);
        } else {
            $data->created_at = time();
            $data->updated_at = time();
            $DB->insert_record('learning_style', $data);
        }
        echo json_encode(['success' => true, 'answered' => count($responses)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Validate all questions are answered for final submission
if (!$all_answered) {
    $redirect_url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $courseid, 'error' => '1'));
    redirect($redirect_url, get_string('required_message', 'block_learning_style'), null, \core\output\notification::NOTIFY_ERROR);
}

// Calculate results
$act_ref_eval = [1,5,9,13,17,21,25,29,33,37,41];
$act_ref = ["a" => 0, "b" => 0, "result" => ""];
$act = 0;
$ref = 0;

$sen_int_eval = [2,6,10,14,18,22,26,30,34,38,42];
$sen_int = ["a" => 0, "b" => 0, "result" => ""];
$sen = 0;
$int = 0;

$vis_vrb_eval = [3,7,11,15,19,23,27,31,35,39,43];
$vis_vrb = ["a" => 0, "b" => 0, "result" => ""];
$vis = 0;
$vrb = 0;

$seq_glo_eval = [4,8,12,16,20,24,28,32,36,40,44];
$seq_glo = ["a" => 0, "b" => 0, "result" => ""];
$seq = 0;
$glo = 0;

/**
* Evaluando Activo Reflexivo
*/
foreach($act_ref_eval as $item){
    //echo "Evaluando item $item";
    if ($learning_style_a[$item] == 0){
        $act_ref["a"]++;
    }else{
        $act_ref["b"]++;
    }
}

if ($act_ref["a"]>$act_ref["b"]) {
    $act_ref["result"] = ($act_ref["a"]-$act_ref["b"])."a";
    $act = $act_ref["a"]-$act_ref["b"];
}else{
    $act_ref["result"] = ($act_ref["b"]-$act_ref["a"])."b";
    $ref = $act_ref["b"]-$act_ref["a"];
}

/**
* Evaluando Sensitivo Intuitivo
*/
foreach($sen_int_eval as $item){
    //echo "Evaluando item $item";
    if ($learning_style_a[$item] == 0){
        $sen_int["a"]++;
    }else{
        $sen_int["b"]++;
    }
}

if ($sen_int["a"]>$sen_int["b"]) {
    $sen_int["result"] = ($sen_int["a"]-$sen_int["b"])."a";
    $sen = $sen_int["a"]-$sen_int["b"];
}else{
    $sen_int["result"] = ($sen_int["b"]-$sen_int["a"])."b";
    $int = $sen_int["b"]-$sen_int["a"];
}

/**
* Evaluando Visual Verbal
*/
foreach($vis_vrb_eval as $item){
    //echo "Evaluando item $item";
    if ($learning_style_a[$item] == 0){
        $vis_vrb["a"]++;
    }else{
        $vis_vrb["b"]++;
    }
}

if ($vis_vrb["a"]>$vis_vrb["b"]) {
    $vis_vrb["result"] = ($vis_vrb["a"]-$vis_vrb["b"])."a";
    $vis = $vis_vrb["a"]-$vis_vrb["b"];
}else{
    $vis_vrb["result"] = ($vis_vrb["b"]-$vis_vrb["a"])."b";
    $vrb = $vis_vrb["b"]-$vis_vrb["a"];
}

/**
* Evaluando Secuencial Global
*/
foreach($seq_glo_eval as $item){
    //echo "Evaluando item $item";
    if ($learning_style_a[$item] == 0){
        $seq_glo["a"]++;
    }else{
        $seq_glo["b"]++;
    }
}

if ($seq_glo["a"]>$seq_glo["b"]) {
    $seq_glo["result"] = ($seq_glo["a"]-$seq_glo["b"])."a";
    $seq = $seq_glo["a"]-$seq_glo["b"];
}else{
    $seq_glo["result"] = ($seq_glo["b"]-$seq_glo["a"])."b";
    $glo = $seq_glo["b"]-$seq_glo["a"];
}

/*
echo "----- ".$act_ref["result"]." -----";
echo "----- ".$sen_int["result"]." -----";
echo "----- ".$vis_vrb["result"]." -----";
echo "----- ".$seq_glo["result"]." -----";
*/

// Prepare data object
$data = new stdClass();
$data->user = $USER->id;
$data->course = $courseid;
$data->state = 1;
$data->is_completed = 1;
$data->timemodified = time();

// Add individual question responses
foreach ($responses as $field => $value) {
    $data->$field = $value;
}

// Add calculated results
$data->act_ref = $act_ref["result"];
$data->sen_int = $sen_int["result"];
$data->vis_vrb = $vis_vrb["result"];
$data->seq_glo = $seq_glo["result"];
$data->ap_active = $act;
$data->ap_reflexivo = $ref;
$data->ap_sensorial = $sen;
$data->ap_intuitivo = $int;
$data->ap_visual = $vis;
$data->ap_verbal = $vrb;
$data->ap_secuencial = $seq;
$data->ap_global = $glo;

$redirect_url = new moodle_url('/course/view.php', array('id' => $courseid));

try {
    if ($existing_response) {
        // Update existing record
        $data->id = $existing_response->id;
        $data->created_at = $existing_response->created_at;
        $data->updated_at = time();
        $DB->update_record('learning_style', $data);
        redirect($redirect_url, get_string('redirect_accept_success', 'block_learning_style'));
    } else {
        // Insert new record
        $data->created_at = time();
        $data->updated_at = time();
        $DB->insert_record('learning_style', $data);
        redirect($redirect_url, get_string('redirect_accept_success', 'block_learning_style'));
    }
} catch (Exception $e) {
    redirect($redirect_url, get_string('redirect_accept_exist', 'block_learning_style'), null, \core\output\notification::NOTIFY_ERROR);
}
