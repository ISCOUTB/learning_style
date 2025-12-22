<?php
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

// Save logic
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

    // Collect all 44 responses (only current page will be present; others stay null).
    $responses = array();
    $learning_style_a = array();
    $all_answered = true;

    for ($i = 1; $i <= 44; $i++) {
        // Use PARAM_RAW to distinguish between '0' and '' (Unanswered)
        $response_raw = optional_param('q' . $i, null, PARAM_RAW);

        if ($response_raw !== null && $response_raw !== '') {
            $response = (int)$response_raw;
            $learning_style_a[$i] = $response;
            $responses["q{$i}"] = $response;
        } else {
            $learning_style_a[$i] = null;
            $all_answered = false;
        }
    }

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

        // Add/Update only answered questions from current page
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
            $redirect_url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $courseid, 'page' => $new_page));
            redirect($redirect_url, get_string('progress_saved', 'block_learning_style'), null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (Exception $e) {
            $redirect_url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $courseid, 'page' => $page, 'error' => 1));
            redirect($redirect_url, 'Error: ' . $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
        }
        exit;
    }

    // For finish, validate all questions are answered and calculate results
    // SECURITY: Always validate ALL 44 questions are answered before finishing
    if ($action === 'finish') {
        // Double check: validate from DB + current submission
        $existing = $DB->get_record('learning_style', array('user' => $USER->id));

        // Merge all answers (DB + current submission)
        for ($i = 1; $i <= 44; $i++) {
            $field = "q{$i}";

            // Use current submission if available
            if (!isset($responses[$field]) || $responses[$field] === null) {
                // Otherwise, try from DB
                if ($existing && isset($existing->$field) && $existing->$field !== null) {
                    $responses[$field] = $existing->$field;
                    $learning_style_a[$i] = $existing->$field;
                }
            }
        }

        // Find first unanswered question
        $first_unanswered = null;
        for ($i = 1; $i <= 44; $i++) {
            $field = "q{$i}";
            if (!isset($responses[$field]) || $responses[$field] === null) {
                $first_unanswered = $i;
                break;
            }
        }

        // If any question is unanswered, redirect to that page
        if ($first_unanswered !== null) {
            $redirect_page = ceil($first_unanswered / 11);
            $redirect_url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $courseid, 'page' => $redirect_page));
            redirect($redirect_url, get_string('all_questions_required', 'block_learning_style'), null, \core\output\notification::NOTIFY_ERROR);
        }

        // Calculate results - CORRECTED TO SAVE ACTUAL COUNTS, NOT DIFFERENCES
        $act_ref_eval = [1,5,9,13,17,21,25,29,33,37,41];
        $act_ref = ["a" => 0, "b" => 0, "result" => ""];

        $sen_int_eval = [2,6,10,14,18,22,26,30,34,38,42];
        $sen_int = ["a" => 0, "b" => 0, "result" => ""];

        $vis_vrb_eval = [3,7,11,15,19,23,27,31,35,39,43];
        $vis_vrb = ["a" => 0, "b" => 0, "result" => ""];

        $seq_glo_eval = [4,8,12,16,20,24,28,32,36,40,44];
        $seq_glo = ["a" => 0, "b" => 0, "result" => ""];

        /**
        * Evaluando Activo/Reflexivo (Active/Reflexive)
        * Questions: 1,5,9,13,17,21,25,29,33,37,41 (11 questions total)
        */
        foreach($act_ref_eval as $item){
            if ($learning_style_a[$item] == 0){
                $act_ref["a"]++;  // Active
            }else{
                $act_ref["b"]++;  // Reflexive
            }
        }

        // Validation: sum must be 11
        if (($act_ref["a"] + $act_ref["b"]) != 11) {
            error_log("Learning Style ERROR: Active/Reflexive dimension sum is not 11. Got: " . ($act_ref["a"] + $act_ref["b"]));
        }

        if ($act_ref["a"] > $act_ref["b"]) {
            $act_ref["result"] = ($act_ref["a"] - $act_ref["b"]) . "a";
        } else {
            $act_ref["result"] = ($act_ref["b"] - $act_ref["a"]) . "b";
        }

        /**
        * Evaluando Sensorial/Intuitivo (Sensorial/Intuitive)
        * Questions: 2,6,10,14,18,22,26,30,34,38,42 (11 questions total)
        */
        foreach($sen_int_eval as $item){
            if ($learning_style_a[$item] == 0){
                $sen_int["a"]++;  // Sensorial
            }else{
                $sen_int["b"]++;  // Intuitive
            }
        }

        // Validation: sum must be 11
        if (($sen_int["a"] + $sen_int["b"]) != 11) {
            error_log("Learning Style ERROR: Sensorial/Intuitive dimension sum is not 11. Got: " . ($sen_int["a"] + $sen_int["b"]));
        }

        if ($sen_int["a"] > $sen_int["b"]) {
            $sen_int["result"] = ($sen_int["a"] - $sen_int["b"]) . "a";
        } else {
            $sen_int["result"] = ($sen_int["b"] - $sen_int["a"]) . "b";
        }

        /**
        * Evaluando Visual/Verbal (Visual/Verbal)
        * Questions: 3,7,11,15,19,23,27,31,35,39,43 (11 questions total)
        */
        foreach($vis_vrb_eval as $item){
            if ($learning_style_a[$item] == 0){
                $vis_vrb["a"]++;  // Visual
            }else{
                $vis_vrb["b"]++;  // Verbal
            }
        }

        // Validation: sum must be 11
        if (($vis_vrb["a"] + $vis_vrb["b"]) != 11) {
            error_log("Learning Style ERROR: Visual/Verbal dimension sum is not 11. Got: " . ($vis_vrb["a"] + $vis_vrb["b"]));
        }

        if ($vis_vrb["a"] > $vis_vrb["b"]) {
            $vis_vrb["result"] = ($vis_vrb["a"] - $vis_vrb["b"]) . "a";
        } else {
            $vis_vrb["result"] = ($vis_vrb["b"] - $vis_vrb["a"]) . "b";
        }

        /**
        * Evaluando Secuencial/Global (Sequential/Global)
        * Questions: 4,8,12,16,20,24,28,32,36,40,44 (11 questions total)
        */
        foreach($seq_glo_eval as $item){
            if ($learning_style_a[$item] == 0){
                $seq_glo["a"]++;  // Sequential
            }else{
                $seq_glo["b"]++;  // Global
            }
        }

        // Validation: sum must be 11
        if (($seq_glo["a"] + $seq_glo["b"]) != 11) {
            error_log("Learning Style ERROR: Sequential/Global dimension sum is not 11. Got: " . ($seq_glo["a"] + $seq_glo["b"]));
        }

        if ($seq_glo["a"] > $seq_glo["b"]) {
            $seq_glo["result"] = ($seq_glo["a"] - $seq_glo["b"]) . "a";
        } else {
            $seq_glo["result"] = ($seq_glo["b"] - $seq_glo["a"]) . "b";
        }

        // Prepare data object
        $data = new stdClass();
        $data->user = $USER->id;
        $data->is_completed = 1;
        $data->updated_at = time();

        // Add individual question responses
        foreach ($responses as $field => $value) {
            $data->$field = $value;
        }

        // Add calculated results - CORRECTED: Store actual counts, not differences
        $data->act_ref = $act_ref["result"];
        $data->sen_int = $sen_int["result"];
        $data->vis_vrb = $vis_vrb["result"];
        $data->seq_glo = $seq_glo["result"];

        // Store ACTUAL COUNTS for each dimension (not differences)
        $data->ap_active = $act_ref["a"];      // Count of Active answers (0-11)
        $data->ap_reflexivo = $act_ref["b"];   // Count of Reflexive answers (0-11)
        $data->ap_sensorial = $sen_int["a"];   // Count of Sensorial answers (0-11)
        $data->ap_intuitivo = $sen_int["b"];   // Count of Intuitive answers (0-11)
        $data->ap_visual = $vis_vrb["a"];      // Count of Visual answers (0-11)
        $data->ap_verbal = $vis_vrb["b"];      // Count of Verbal answers (0-11)
        $data->ap_secuencial = $seq_glo["a"];  // Count of Sequential answers (0-11)
        $data->ap_global = $seq_glo["b"];      // Count of Global answers (0-11)

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
        exit;
    }
}

// SECURITY: Validate that user cannot skip pages without completing previous ones
if ($existing_response && $page > 1) {
    // Check all questions from page 1 to current page - 1
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
    
    // If trying to access a page beyond allowed, redirect to max allowed
    if ($page > $max_allowed_page) {
        // Silent redirect (no notification). Client-side UX handles visual guidance.
        redirect(new moodle_url('/blocks/learning_style/view.php',
                 array('cid' => $courseid, 'page' => $max_allowed_page)));
    }
}

// If coming from "continue test" link, calculate which page to show
if ($existing_response && !isset($_GET['page'])) {
    // Find first unanswered question
    $first_unanswered = null;
    for ($i = 1; $i <= $total_questions; $i++) {
        $field = "q{$i}";
        if (!isset($existing_response->$field) || $existing_response->$field === null) {
            $first_unanswered = $i;
            break;
        }
    }
    
    // Calculate page for first unanswered question
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

// Header con icono SVG
echo "<div class='text-center mb-3'>";
$iconurl = new moodle_url('/blocks/learning_style/pix/learning_style_icon.svg');
echo "<img src='" . $iconurl . "' alt='Learning Style Icon' style='width: 70px; height: 70px; margin-bottom: 10px;' />";
echo "</div>";

echo "<h1 class='title_learning_style'>".get_string('test_page_title', 'block_learning_style')."</h1>";

echo "<div class='content-header'>";
echo "
<div>
".get_string('test_intro_p1', 'block_learning_style')." 
".get_string('test_intro_p2', 'block_learning_style')." 
</div>
<br>
<div style='background-color: #e8f5ff; border-left: 4px solid #0054ce; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;'>
    <strong>".get_string('test_note', 'block_learning_style')."</strong> ".get_string('test_all_required', 'block_learning_style')." (<span style='color: #d32f2f;'>*</span>)
</div>
";
echo "</div>";

$action_form = $PAGE->url;
?>

<form method="POST" action="<?php echo $action_form; ?>" id="learningStyleForm">
    <div class="content-accept">
        <ol class="learning_style_q" style="padding: 0px; list-style: none;">
        <?php 
        // Display current page questions
        for ($i=$start_question; $i<=$end_question; $i++){ 
            $field = "q{$i}";
            $saved_value = ($existing_response && isset($existing_response->$field)) ? $existing_response->$field : null;
        ?>
            <li class="learning_style_item" data-question="<?php echo $i; ?>">
                <div><?php echo get_string("learning_style:q".$i, 'block_learning_style') ?></div>
                <select name="q<?php echo $i; ?>" class="my-custom-select select-q" data-question="<?php echo $i; ?>" style="font-weight: 500 !important;">
                    <option value="" <?php echo ($saved_value === null) ? 'selected' : ''; ?>><?php echo get_string('select_option', 'block_learning_style'); ?></option>
                    <option value="0" <?php echo ($saved_value === '0' || $saved_value === 0) ? 'selected' : ''; ?>>A) <?php echo get_string('learning_style:q'.$i.'_a', 'block_learning_style') ?></option>
                    <option value="1" <?php echo ($saved_value === '1' || $saved_value === 1) ? 'selected' : ''; ?>>B) <?php echo get_string('learning_style:q'.$i.'_b', 'block_learning_style') ?></option>
                </select>
            </li>
        <?php } ?>
        </ol>
        
        <div class="clearfix"></div>
        
        <!-- Navigation buttons -->
        <div class="navigation-buttons" style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
            <div>
                <?php if ($page > 1): ?>
                    <button type="submit" name="action" value="previous" class="btn btn-secondary">
                        <?php echo get_string('btn_previous', 'block_learning_style'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div>
                <?php if ($page < $total_pages): ?>
                    <button type="submit" name="action" value="next" class="btn btn-primary">
                        <?php echo get_string('btn_next', 'block_learning_style'); ?>
                    </button>
                <?php else: ?>
                    <button type="submit" name="action" value="finish" id="submitBtn" class="btn btn-success">
                        <?php echo get_string('btn_finish', 'block_learning_style'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    
    </div>
    
    <input type="hidden" name="cid" value="<?php echo $courseid ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    <div class="clearfix"></div>
    
</form>

<script>
// Auto-save functionality (silent, no visual feedback)
let autoSaveTimer = null;
let isSaving = false;

function autoSaveProgress() {
    if (isSaving) return;
    
    isSaving = true;
    const formData = new FormData(document.getElementById('learningStyleForm'));
    formData.set('action', 'autosave');
    
    fetch('<?php echo $PAGE->url->out(false); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Silent save - no visual feedback
        isSaving = false;
    })
    .catch(error => {
        console.error('Auto-save error:', error);
        isSaving = false;
    });
}

// Listen to changes in selects for auto-save
document.querySelectorAll('.select-q').forEach(select => {
    select.addEventListener('change', function() {
        // Remove red highlight when user answers the question
        const listItem = this.closest('.learning_style_item');
        if (listItem && listItem.classList.contains('question-error-highlight')) {
            listItem.style.border = '';
            listItem.style.backgroundColor = '';
            listItem.style.borderRadius = '';
            listItem.style.padding = '';
            listItem.style.marginBottom = '';
            listItem.style.boxShadow = '';
            listItem.classList.remove('question-error-highlight');
        }
        
        // Clear previous timer
        if (autoSaveTimer) {
            clearTimeout(autoSaveTimer);
        }
        
        // Auto-save after 2 seconds of inactivity
        autoSaveTimer = setTimeout(autoSaveProgress, 2000);
    });
});

// Handle form submission for navigation
document.getElementById('learningStyleForm').addEventListener('submit', function(e) {
    const submitButton = e.submitter;
    const action = submitButton ? submitButton.value : 'next';
    
    // For "previous" button, always allow navigation without validation
    if (action === 'previous') {
        return true;
    }
    
    // Only validate for "next" and "finish" actions (same UX as personality_test)
    if (action !== 'next' && action !== 'finish') {
        return true;
    }

    // Validate current page for next/finish
    const selectsOnPage = document.querySelectorAll('.select-q');
    let allAnswered = true;
    let firstUnanswered = null;

    selectsOnPage.forEach(function(select) {
        if (select.value === '') {
            allAnswered = false;
            const listItem = select.closest('.learning_style_item');

            if (listItem) {
                listItem.style.border = '3px solid #d32f2f';
                listItem.style.backgroundColor = '#ffebee';
                listItem.style.borderRadius = '10px';
                listItem.style.padding = '24px 28px';
                listItem.style.marginBottom = '1.5rem';
                listItem.style.boxShadow = '0 4px 8px rgba(211, 47, 47, 0.3)';
                listItem.classList.add('question-error-highlight');

                if (!firstUnanswered) {
                    firstUnanswered = listItem;
                }
            }
        }
    });

    if (!allAnswered) {
        e.preventDefault();

        // Scroll to first unanswered question (card)
        if (firstUnanswered) {
            firstUnanswered.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        return false;
    }
});

// Auto-scroll to first unanswered question when continuing test
<?php if($existing_response && $answered_count > 0 && $answered_count < 44 && !$scroll_to_finish): ?>
window.addEventListener('load', function() {
    // Wait a bit for the page to fully render
    setTimeout(function() {
        // Find first unanswered question on current page
        const selects = document.querySelectorAll('.select-q');
        for (let i = 0; i < selects.length; i++) {
            if (selects[i].value === '') {
                const selectElement = selects[i];
                const listItem = selectElement.closest('.learning_style_item');
                
                // Add green highlight to the entire card
                if (listItem) {
                    listItem.style.border = '3px solid #28a745';
                    listItem.style.backgroundColor = '#d4edda';
                    listItem.style.borderRadius = '10px';
                    listItem.style.padding = '24px 28px';
                    listItem.style.marginBottom = '1.5rem';
                    listItem.style.boxShadow = '0 4px 8px rgba(40, 167, 69, 0.3)';
                    listItem.style.transition = 'all 0.3s ease';
                    
                    // Scroll to it
                    listItem.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    
                    // Remove highlight after 5 seconds
                    setTimeout(function() {
                        listItem.style.border = '';
                        listItem.style.backgroundColor = '';
                        listItem.style.borderRadius = '';
                        listItem.style.padding = '';
                        listItem.style.marginBottom = '';
                        listItem.style.boxShadow = '';
                    }, 5000);
                }
                
                break;
            }
        }
    }, 300);
});
<?php endif; ?>

// Scroll to finish button when coming from block with all questions answered
<?php if($scroll_to_finish): ?>
window.addEventListener('load', function() {
    setTimeout(function() {
        const finishBtn = document.getElementById('submitBtn');
        if (finishBtn) {
            finishBtn.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            
            // Add green pulsing highlight to the button
            finishBtn.style.boxShadow = '0 0 20px rgba(40, 167, 69, 0.8)';
            finishBtn.style.transition = 'all 0.3s ease';
            
            // Remove highlight after 5 seconds
            setTimeout(function() {
                finishBtn.style.boxShadow = '';
            }, 5000);
        }
    }, 300);
});
<?php endif; ?>
</script>

<?php
echo "</div>";
echo $OUTPUT->footer();
?>
<br>
