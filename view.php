<?php
require_once(dirname(__FILE__) . '/lib.php');

if( !isloggedin() ){
            return;
}

$courseid = required_param('cid', PARAM_INT);
$error  = optional_param('error', 0, PARAM_INT);

if ($courseid == SITEID && !$courseid) {
    redirect($CFG->wwwroot);
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($course);
$context = $PAGE->context;

require_login($course);

// Check for existing response
$existing_response = $DB->get_record('learning_style', array('user' => $USER->id));

// If test is completed, redirect to results or show completion message
if ($existing_response && $existing_response->is_completed) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)), 
             get_string('redirect_accept_exist', 'block_learning_style'), 
             null, \core\output\notification::NOTIFY_INFO);
}
  
$PAGE->set_url('/blocks/learning_style/view.php', array('cid'=>$courseid));

$title = get_string('pluginname', 'block_learning_style');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($title." : ".$course->fullname);
$PAGE->set_heading($title." : ".$course->fullname);

echo $OUTPUT->header();

// Botón de descarga para profesores (usando capacidad estándar de Moodle)
if (has_capability('moodle/course:viewhiddensections', $context)) {
    $download_url = new moodle_url('/blocks/learning_style/download_results.php', array('courseid' => $courseid, 'sesskey' => sesskey()));
    echo '<div style="text-align: right; margin-bottom: 20px;">';
    echo '<a href="' . $download_url . '" class="btn btn-primary">';
    echo get_string('download_results', 'block_learning_style');
    echo '</a>';
    echo '</div>';
}

echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>";
echo "<div class='container'>";
echo "<h1 class='title_learning_style'>" . get_string('test_page_title', 'block_learning_style') . "</h1>";
echo "
<p>
" . get_string('test_intro_p1', 'block_learning_style') . "<br>
<br>
" . get_string('test_intro_p2', 'block_learning_style') . "<br>
" . get_string('test_benefit_1', 'block_learning_style') . "<br>
" . get_string('test_benefit_2', 'block_learning_style') . "<br>
" . get_string('test_benefit_3', 'block_learning_style') . "<br>
<br>
" . get_string('test_intro_p3', 'block_learning_style') . "<br>
<br>
" . get_string('test_lets_begin', 'block_learning_style') . "
</p>
<div style='background-color: #e3f2fd; border-left: 4px solid #2196F3; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;'>
    <strong>" . get_string('test_note', 'block_learning_style') . "</strong> " . get_string('test_all_required', 'block_learning_style') . " (<span style='color: #d32f2f;'>*</span>)
</div>
";
$action_form = new moodle_url('/blocks/learning_style/save.php');
?>
<style>
    /* Estilo para campos obligatorios no completados solo después de intentar enviar */
    form.attempted select:invalid {
        border: 2px solid #d32f2f !important;
        background-color: #ffebee !important;
    }
    
    /* Mensaje visual al hacer focus en campo inválido */
    form.attempted select:invalid:focus {
        outline: 2px solid #d32f2f;
        box-shadow: 0 0 8px rgba(211, 47, 47, 0.3);
    }
    
    /* Diseño limpio de preguntas */
    .learning_style_item {
        margin-bottom: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #2196F3;
    }
    
    .learning_style_item > div {
        font-size: 1.05rem;
        line-height: 1.6;
        margin-bottom: 12px;
        color: #333;
        font-weight: 500;
    }
    
    .learning_style_item select {
        width: 100%;
    }
</style>
<link rel="stylesheet" href="<?php echo $CFG->wwwroot?>/blocks/learning_style/styles.css">

<form method="POST" action="<?php echo $action_form ?>" id="learningStyleForm">
    <div class="content-accept <?php echo ($error)?"error":"" ?>">

        <ol class="learning_style_q" style="padding: 0px; list-style: none;">
        <?php for ($i=1;$i<=44;$i++){ ?>
            <li class="learning_style_item">
                <div><?php echo get_string("learning_style:q".$i, 'block_learning_style') ?></div>
                <select name="learning_style:q<?php echo $i; ?>" required class="form-select select-q">
                    <option value="" disabled selected hidden>Selecciona</option>
                    <option value="0"><?php echo get_string('learning_style:q'.$i.'_a', 'block_learning_style') ?></option>
                    <option value="1"><?php echo get_string('learning_style:q'.$i.'_b', 'block_learning_style') ?></option>
                </select>
            </li>
        <?php } ?>
        </ol>
        
        <div class="clearfix"></div>
        <div class="mt-3">
            <input class="btn btn-success" type="submit" id="submitBtn" value="<?php echo get_string('submit_text', 'block_learning_style') ?>" >
        </div>
    
    </div>
    
    <input type="hidden" name="cid" value="<?php echo $courseid ?>">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    <input type="hidden" name="action" value="complete" id="actionField">
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
    
    fetch('<?php echo $CFG->wwwroot; ?>/blocks/learning_style/save.php', {
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

// Marcar formulario cuando se haga clic en enviar
document.getElementById('submitBtn').addEventListener('click', function(e) {
    // Check if all questions are answered
    const selects = document.querySelectorAll('.select-q');
    let allAnswered = true;
    selects.forEach(select => {
        if (select.value === '') allAnswered = false;
    });
    
    if (!allAnswered) {
        e.preventDefault();
        document.getElementById('learningStyleForm').classList.add('attempted');
        
        // Highlight ALL unanswered questions in red and scroll to first one
        const selects = document.querySelectorAll('.select-q');
        let firstUnanswered = null;
        
        selects.forEach(function(select) {
            if (select.value === '') {
                const listItem = select.closest('.learning_style_item');
                
                if (listItem) {
                    listItem.style.border = '3px solid #d32f2f';
                    listItem.style.backgroundColor = '#ffebee';
                    listItem.style.borderRadius = '8px';
                    listItem.style.padding = '15px';
                    listItem.style.marginBottom = '20px';
                    listItem.style.boxShadow = '0 4px 8px rgba(211, 47, 47, 0.3)';
                    listItem.classList.add('question-error-highlight');
                    
                    // Store first unanswered for scrolling
                    if (!firstUnanswered) {
                        firstUnanswered = select;
                    }
                }
            }
        });
        
        // Scroll to first unanswered question
        if (firstUnanswered) {
            firstUnanswered.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
        
        return false;
    }
    
    // Set action to complete
    document.getElementById('actionField').value = 'complete';
    document.getElementById('learningStyleForm').classList.add('attempted');
});

// Mantener la clase attempted si hay error
<?php if($error): ?>
document.getElementById('learningStyleForm').classList.add('attempted');
<?php endif; ?>

// Auto-scroll to first unanswered question when continuing test
<?php if($existing_response): ?>
window.addEventListener('load', function() {
    // Find first unanswered question (select with no value)
    const selects = document.querySelectorAll('.select-q');
    for (let i = 0; i < selects.length; i++) {
        if (selects[i].value === '') {
            // Found first unanswered question - scroll to it and highlight with green border
            const selectElement = selects[i];
            const listItem = selectElement.closest('.learning_style_item');
            
            // Add green highlight
            if (listItem) {
                listItem.style.border = '3px solid #28a745';
                listItem.style.backgroundColor = '#d4edda';
                listItem.style.borderRadius = '8px';
                listItem.style.padding = '15px';
                listItem.style.marginBottom = '20px';
                listItem.style.boxShadow = '0 4px 8px rgba(40, 167, 69, 0.3)';
            }
            
            // Scroll to it
            selectElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            
            // Remove highlight after 5 seconds
            setTimeout(function() {
                if (listItem) {
                    listItem.style.border = '';
                    listItem.style.backgroundColor = '';
                    listItem.style.borderRadius = '';
                    listItem.style.padding = '';
                    listItem.style.marginBottom = '';
                    listItem.style.boxShadow = '';
                }
            }, 5000);
            
            break;
        }
    }
});
<?php endif; ?>
</script>

<?php
echo "</div>";
echo $OUTPUT->footer();
?>
<br>
