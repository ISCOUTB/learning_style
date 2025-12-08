<?php
require_once(dirname(__FILE__) . '/lib.php');

if( !isloggedin() ){
            return;
}

$courseid = optional_param('cid', 0, PARAM_INT);
$error  = optional_param('error', 0, PARAM_INT);

if ($courseid == SITEID && !$courseid) {
    redirect($CFG->wwwroot);
}

/*if (!isset($SESSION->honorcodetext)) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}*/

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($course);
$context = $PAGE->context;
  
$PAGE->set_url('/blocks/learning_style/view.php', array('cid'=>$courseid));

$title = get_string('pluginname', 'block_learning_style');

$PAGE->set_pagelayout('embedded');
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
echo "<h1 class='title_learning_style'>Primer paso, vamos a conocer tu estilo de aprendizaje</h1>";
echo "
<p>
¡Bienvenido al curso!<br>
<br>
Acá tendrás a disposición herramientas adicionales y los conocimientos fundamentales necesarios para que te conviertas en un buen programador, independientemente de la carrera que estudies. Nuestro objetivo principal es proporcionarte una experiencia de aprendizaje personalizada y significativa que te permita alcanzar tus metas profesionales y académicas.<br>
<br>
Antes de empezar, te invitamos a realizar el siguiente test sobre tu estilo de aprendizaje y preferencia de recursos de aprendizaje. Esto nos permitirá conocerte y recomendarte mejor los recursos a los que tendrás acceso en el curso.<br>
<br>
Comencemos!
</p>
<div style='background-color: #e3f2fd; border-left: 4px solid #2196F3; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;'>
    <strong>Nota:</strong> Todas las preguntas son obligatorias (<span style='color: #d32f2f;'>*</span>)
</div>
";
$action_form = new moodle_url('/blocks/learning_style/save.php');
?>
<style>
    body{
        background: url("<?php echo $CFG->wwwroot?>/blocks/learning_style/pix/bg.jpg");
    }
    
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
</style>
<link rel="stylesheet" href="<?php echo $CFG->wwwroot?>/blocks/learning_style/styles.css">

<form method="POST" action="<?php echo $action_form ?>" id="learningStyleForm">
    <div class="content-accept <?php echo ($error)?"error":"" ?>">
        <?php if($error): ?>
            <p class="error"><?php echo get_string('required_message', 'block_learning_style') ?></p>
        <?php endif; ?>

        <ol class="learning_style_q" style="padding: 0px;">
        <?php for ($i=1;$i<=44;$i++){ ?>
        

        <li class="learning_style_item"><div><?php echo $i.". ".get_string("learning_style:q".$i, 'block_learning_style') ?></div>
        <select name="learning_style:q<?php echo $i; ?>" required class="form-select select-q">
            <option value="" disabled selected hidden>Selecciona</option>
            <option value="0"><?php echo get_string('learning_style:q'.$i.'_a', 'block_learning_style') ?></option>
            <option value="1"><?php echo get_string('learning_style:q'.$i.'_b', 'block_learning_style') ?></option>
        </select>
        </li>
        <?php } ?>
        </ol>
        <div class="clearfix"></div>
        <input class="btn btn-success" type="submit" id="submitBtn" value="<?php echo get_string('submit_text', 'block_learning_style') ?>" >
    
    </div>
    
    <input type="hidden" name="cid" value="<?php echo $courseid ?>">
    <div class="clearfix"></div>
    
</form>

<script>
// Marcar formulario cuando se haga clic en enviar
document.getElementById('submitBtn').addEventListener('click', function() {
    document.getElementById('learningStyleForm').classList.add('attempted');
});

// Mantener la clase attempted si hay error
<?php if($error): ?>
document.getElementById('learningStyleForm').classList.add('attempted');
<?php endif; ?>
</script>

<?php
echo "</div>";
echo $OUTPUT->footer();
?>
<br>
