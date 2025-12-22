<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
if (!$courseid) {
    $courseid = required_param('cid', PARAM_INT);
}

if ($courseid == SITEID) {
    redirect($CFG->wwwroot);
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($course);
$context = context_course::instance($courseid);
$PAGE->set_context($context);

require_login($course);

// Friendly redirect for students/unauthorized users (instead of showing a nopermissions error page).
if (!has_capability('block/learning_style:viewreports', $context) && !is_siteadmin()) {
    $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($redirecturl);
}

$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);

// Procesar acciones
if ($action === 'delete' && $userid && confirm_sesskey()) {
    $confirm = optional_param('confirm', 0, PARAM_INT);
    if ($confirm) {
        // PRIVACY: Teachers can only delete results for users enrolled in THIS course.
        $targetuser = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        if (!is_siteadmin() && (
            !is_enrolled($context, $targetuser, 'block/learning_style:take_test', true)
            || has_capability('block/learning_style:viewreports', $context, $userid)
            || is_siteadmin($userid)
        )) {
            $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
            redirect($redirecturl);
        }
        // Eliminar registro global del test del usuario
        $DB->delete_records('learning_style', array('user' => $userid));
        redirect(new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid)), 
                 get_string('learning_style_deleted', 'block_learning_style'));
    }
}

$PAGE->set_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid));
$title = get_string('admin_title', 'block_learning_style');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title . " : " . $course->fullname);
$PAGE->set_heading($title . " : " . $course->fullname);

// CSS del plugin: usar el sistema de Moodle (evita romper el theme).
$PAGE->requires->css(new moodle_url('/blocks/learning_style/styles.css'));

echo $OUTPUT->header();
echo "<div class='block_learning_style_container'>";

// Header con icono SVG en línea con el título
$iconurl = new moodle_url('/blocks/learning_style/pix/learning_style_icon.svg');
echo "<h1 class='mb-4 text-center'>";
echo "<img src='" . $iconurl . "' alt='Learning Style Icon' style='width: 50px; height: 50px; vertical-align: middle; margin-right: 15px;' />";
echo get_string('admin_title', 'block_learning_style');
echo "</h1>";

// Confirmación de eliminación
if ($action === 'delete' && $userid) {
    $user = $DB->get_record('user', array('id' => $userid), 'firstname, lastname');
    if ($user) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>" . get_string('confirm_delete_learning_style', 'block_learning_style') . "</h4>";
        echo "<p>" . s(fullname($user)) . "</p>";
        echo "<div class='mt-3'>";
        echo "<a href='" . new moodle_url('/blocks/learning_style/admin_view.php', 
            array('courseid' => $courseid, 'action' => 'delete', 'userid' => $userid, 'confirm' => 1, 'sesskey' => sesskey())) . 
            "' class='btn btn-danger text-white'>" . get_string('delete') . "</a> ";
        echo "<a href='" . new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid)) . 
                "' class='btn btn-secondary text-white'>" . get_string('cancel') . "</a>";
        echo "</div>";
        echo "</div>";
    }
} else {
    // Description banner (match other admin dashboards)
    echo "<div class='alert alert-info mb-4'>";
    echo format_text(get_string('admin_dashboard_description', 'block_learning_style'), FORMAT_HTML);
    echo "</div>";

    // Obtener estadísticas
    // Get enrolled students in this course (capability-based)
    $enrolled_users = get_enrolled_users($context, 'block/learning_style:take_test', 0, 'u.id');
    $enrolled_ids = array_keys($enrolled_users);

    // Defensive: ensure only students show in admin tables (exclude report-capable users).
    $student_ids = array();
    foreach ($enrolled_ids as $candidateid) {
        $candidateid = (int)$candidateid;
        if (is_siteadmin($candidateid)) {
            continue;
        }
        if (has_capability('block/learning_style:viewreports', $context, $candidateid)) {
            continue;
        }
        $student_ids[] = $candidateid;
    }
    $enrolled_ids = $student_ids;
    
    // Total students in course
    $total_students = count($enrolled_ids);
    
    // Obtener participantes con información del usuario PRIMERO (antes de calcular estadísticas)
    $userfields = \core_user\fields::for_name()->with_userpic()->get_sql('u', false, '', '', false)->selects;
    $participants = array();
    if (!empty($enrolled_ids)) {
        list($insql, $params) = $DB->get_in_or_equal($enrolled_ids, SQL_PARAMS_NAMED);
        $sql = "SELECT ls.*, {$userfields}
                FROM {learning_style} ls
                JOIN {user} u ON ls.user = u.id
                WHERE ls.user $insql
                ORDER BY ls.created_at DESC";
        
        $participants = $DB->get_records_sql($sql, $params);
    }
    
    // Count participants who are enrolled in this course
    $completed_tests = 0;
    $in_progress_tests = 0;
    if (!empty($enrolled_ids)) {
        list($insql, $params) = $DB->get_in_or_equal($enrolled_ids, SQL_PARAMS_NAMED);
        
        // Count completed tests
        $params_completed = $params;
        $params_completed['completed'] = 1;
        $completed_tests = $DB->count_records_select('learning_style', "user $insql AND is_completed = :completed", $params_completed);
        
        // Count in-progress tests
        $params_progress = $params;
        $params_progress['completed'] = 0;
        $in_progress_tests = $DB->count_records_select('learning_style', "user $insql AND is_completed = :completed", $params_progress);
    }
    
    echo "<div class='row mb-4'>";

    // Total students card
    echo "<div class='col-md-3 mb-4'>";
    echo "<div class='card border-info h-100' style='border-color: #1567f9 !important;'>";
    echo "<div class='card-body text-center'>";
    echo "<i class='fa fa-users' style='font-size: 2em; margin-bottom: 10px; color: #1567f9;'></i>";
    echo "<h5 class='card-title'>" . get_string('total_students', 'block_learning_style') . "</h5>";
    echo "<h2 class='text-info' style='color: #1567f9 !important;'>" . $total_students . "</h2>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Completed tests card
    echo "<div class='col-md-3 mb-4'>";
    echo "<div class='card border-success h-100' style='border-color: #28a745 !important;'>";
    echo "<div class='card-body text-center'>";
    echo "<i class='fa fa-check-circle text-success' style='font-size: 2em; margin-bottom: 10px;'></i>";
    echo "<h5 class='card-title'>" . get_string('completed_tests', 'block_learning_style') . "</h5>";
    echo "<h2 class='text-success'>" . $completed_tests . "</h2>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // In progress tests card
    echo "<div class='col-md-3 mb-4'>";
    echo "<div class='card border-warning h-100' style='border-color: #ffc107 !important;'>";
    echo "<div class='card-body text-center'>";
    echo "<i class='fa fa-clock-o' style='font-size: 2em; margin-bottom: 10px; color: #ffc107;'></i>";
    echo "<h5 class='card-title'>" . get_string('in_progress_tests', 'block_learning_style') . "</h5>";
    echo "<h2 class='text-info' style='color: #ffc107 !important;'>" . $in_progress_tests . "</h2>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Completion rate card
    $completion_rate = $total_students > 0 ? round(($completed_tests / $total_students) * 100, 1) : 0;
    echo "<div class='col-md-3 mb-4'>";
    echo "<div class='card border-primary h-100' style='border-color: #1567f9 !important;'>";   
    echo "<div class='card-body text-center'>"; 
    echo '<i class="fa fa-percent" style="font-size: 2em; margin-bottom: 10px; color: #1567f9;"></i>';
    echo "<h5 class='card-title'>" . get_string('completion_rate', 'block_learning_style') . "</h5>";
    echo "<h2 class='text-info' style='color: #1567f9 !important;'>" . $completion_rate . "%</h2>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    echo "</div>";

    // General statistics section (only meaningful when there are completed tests)
    if ($completed_tests > 0) {
        echo "<div class='row mt-4'>";
        echo "<div class='col-12'>";
        echo "<div class='card'>";
        echo "<div class='card-header'>";
        echo "<h5 class='mb-0'><i class='fa fa-chart-bar'></i> " . get_string('general_statistics', 'block_learning_style') . "</h5>";
        echo "</div>";
        echo "<div class='card-body'>";
        echo "<div class='row'>";
        
        // Calculate learning style distribution
        $style_distribution = array(
            'Active' => 0,
            'Reflexive' => 0,
            'Sensorial' => 0,
            'Intuitive' => 0,
            'Visual' => 0,
            'Verbal' => 0,
            'Sequential' => 0,
            'Global' => 0
        );
        
        if (!empty($participants)) {
            foreach ($participants as $p) {
                if ($p->is_completed == 1) {
                    // Determinate dominant style on each dimension
                    // Processing Dimension
                    if ($p->ap_active > $p->ap_reflexivo) {
                        $style_distribution['Active']++;
                    } else {
                        $style_distribution['Reflexive']++;
                    }
                    
                    // Perceiving Dimension
                    if ($p->ap_sensorial > $p->ap_intuitivo) {
                        $style_distribution['Sensorial']++;
                    } else {
                        $style_distribution['Intuitive']++;
                    }

                    // Receiving Dimension
                    if ($p->ap_visual > $p->ap_verbal) {
                        $style_distribution['Visual']++;
                    } else {
                        $style_distribution['Verbal']++;
                    }

                    // Understanding Dimension
                    if ($p->ap_secuencial > $p->ap_global) {
                        $style_distribution['Sequential']++;
                    } else {
                        $style_distribution['Global']++;
                    }
                }
            }
        }
        
        // Display most common learning styles (localized)
        arsort($style_distribution);
        $top_styles = array_slice($style_distribution, 0, 4, true);

        // Map internal keys to language strings to support multiple languages
        $style_labels = array(
            'Active' => get_string('active', 'block_learning_style'),
            'Reflexive' => get_string('reflexive', 'block_learning_style'),
            'Sensorial' => get_string('sensorial', 'block_learning_style'),
            'Intuitive' => get_string('intuitive', 'block_learning_style'),
            'Visual' => get_string('visual', 'block_learning_style'),
            'Verbal' => get_string('verbal', 'block_learning_style'),
            'Sequential' => get_string('sequential', 'block_learning_style'),
            'Global' => get_string('global', 'block_learning_style')
        );

        echo "<div class='col-md-6'>";
        echo "<h6 class='text-center text-md-left mt-3 mb-3 mt-md-0 mb-md-2'>" . get_string('most_common_types', 'block_learning_style') . "</h6>";
        if (!empty($top_styles) && $completed_tests > 0) {
            echo "<ul class='list-group'>";
            foreach ($top_styles as $style => $count) {
                if ($count > 0) {
                    $percentage = round(($count / $completed_tests) * 100, 1);
                    $label = isset($style_labels[$style]) ? $style_labels[$style] : $style;
                    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                    echo "<strong>" . s($label) . "</strong>";
                    echo "<span class='badge bg-secondary rounded-pill'>" . $count . " (" . $percentage . "%)</span>";
                    echo "</li>";
                }
            }
            echo "</ul>";
        } else {
            echo "<p class='text-muted'>" . get_string('no_data_available', 'block_learning_style') . "</p>";
        }
        echo "</div>";
        
        // Average dimension scores - Mostrar las 8 dimensiones por separado
        echo "<div class='col-md-6'>";
        echo "<h6 class='text-center text-md-left mt-3 mb-3 mt-md-0 mb-md-2'>" . get_string('average_dimensions', 'block_learning_style') . "</h6>";
        if ($completed_tests > 0) {
            $avg_active = 0;
            $avg_reflexivo = 0;
            $avg_sensorial = 0;
            $avg_intuitivo = 0;
            $avg_visual = 0;
            $avg_verbal = 0;
            $avg_secuencial = 0;
            $avg_global = 0;
            
            foreach ($participants as $p) {
                if ($p->is_completed == 1) {
                    $avg_active += $p->ap_active;
                    $avg_reflexivo += $p->ap_reflexivo;
                    $avg_sensorial += $p->ap_sensorial;
                    $avg_intuitivo += $p->ap_intuitivo;
                    $avg_visual += $p->ap_visual;
                    $avg_verbal += $p->ap_verbal;
                    $avg_secuencial += $p->ap_secuencial;
                    $avg_global += $p->ap_global;
                }
            }
            
            $avg_active = round($avg_active / $completed_tests, 1);
            $avg_reflexivo = round($avg_reflexivo / $completed_tests, 1);
            $avg_sensorial = round($avg_sensorial / $completed_tests, 1);
            $avg_intuitivo = round($avg_intuitivo / $completed_tests, 1);
            $avg_visual = round($avg_visual / $completed_tests, 1);
            $avg_verbal = round($avg_verbal / $completed_tests, 1);
            $avg_secuencial = round($avg_secuencial / $completed_tests, 1);
            $avg_global = round($avg_global / $completed_tests, 1);
            
            echo "<div class='row'>";
            
            // Primera subcolumna - 4 dimensiones
            echo "<div class='col-md-6'>";
            echo "<ul class='list-group'>";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<strong>" . get_string('active', 'block_learning_style') . ":</strong>";
            echo $avg_active;
            echo "</li>";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<strong>" . get_string('reflexive', 'block_learning_style') . ":</strong>";
            echo $avg_reflexivo;
            echo "</li>";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<strong>" . get_string('sensorial', 'block_learning_style') . ":</strong>";
            echo $avg_sensorial;
            echo "</li>";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<strong>" . get_string('intuitive', 'block_learning_style') . ":</strong>";
            echo $avg_intuitivo;
            echo "</li>";
            echo "</ul>";
            echo "</div>";
            
            // Segunda subcolumna - 4 dimensiones
            echo "<div class='col-md-6 mt-3 mt-md-0'>";
            echo "<ul class='list-group'>";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<strong>" . get_string('visual', 'block_learning_style') . ":</strong>";
            echo $avg_visual;
            echo "</li>";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<strong>" . get_string('verbal', 'block_learning_style') . ":</strong>";
            echo $avg_verbal;
            echo "</li>";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<strong>" . get_string('sequential', 'block_learning_style') . ":</strong>";
            echo $avg_secuencial;
            echo "</li>";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<strong>" . get_string('global', 'block_learning_style') . ":</strong>";
            echo $avg_global;
            echo "</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "</div>"; // Cierra row de subcolumnas
        } else {
            echo "<p class='text-muted'>" . get_string('no_data_available', 'block_learning_style') . "</p>";
        }
        echo "</div>";
        
        echo "</div>"; // Cierra row de estadísticas
        echo "</div>"; // Cierra card-body
        echo "</div>"; // Cierra card
        echo "</div>"; // Cierra col-12
        echo "</div>"; // Cierra row mt-4
    }

    // Sección de Participants List
    if (empty($participants)) {
        echo "<div class='alert alert-info mt-4'>";
        echo "<i class='fa fa-info-circle'></i> ";
        echo "<h5>" . get_string('no_participants', 'block_learning_style') . "</h5>";
        echo "<p>" . get_string('no_participants_message', 'block_learning_style') . "</p>";
        echo "</div>";
    } else {
        echo "<div class='card mt-5'>";
        echo "<div class='card-header'>";
        echo "<h5 class='mb-0'>" . get_string('participants_list', 'block_learning_style') . "</h5>";
        echo "</div>";
        echo "<div class='card-body'>";
        
        // Filtros y búsqueda
        echo "<div class='row mb-3'>";
        echo "<div class='col-md-8'>";
        echo "<input type='text' id='searchInput' class='form-control' placeholder='" . s(get_string('search_participant_placeholder', 'block_learning_style')) . "'>";
        echo "</div>";
        echo "<div class='col-12 col-md-4 text-end d-flex justify-content-center justify-content-md-start mt-3 mt-md-0'>";
        echo "<button class='btn btn-success' onclick='exportData(\"csv\")'><i class='fa fa-download mr-2'></i>" . s(get_string('export_csv', 'block_learning_style')) . "</button>";
        echo "</div>";
        echo "</div>";

        // Tabla de participantes
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-hover' id='participantsTable'>";
        echo "<thead class='table-dark'>";
        echo "<tr>";
        echo "<th>" . get_string('participant', 'block_learning_style') . "</th>";
        echo "<th>" . get_string('email') . "</th>";
        echo "<th>" . get_string('status', 'block_learning_style') . "</th>";
        echo "<th>" . get_string('learning_profile', 'block_learning_style') . "</th>";
        echo "<th>" . get_string('completion_date', 'block_learning_style') . "</th>";
        echo "<th>" . get_string('actions', 'block_learning_style') . "</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        foreach ($participants as $participant) {
            echo "<tr class='participant-row'>";
            echo "<td>";
            echo "<div class='d-flex align-items-center'>";
            $userpicture = new user_picture($participant);
            $userpicture->size = 35;
            echo $OUTPUT->render($userpicture);
            echo "<span class='ms-2'><strong>" . s(fullname($participant)) . "</strong></span>";
            echo "</div>";
            echo "</td>";
            echo "<td>" . $participant->email . "</td>";
            
            // Estado y Progreso
            echo "<td>";
            if ($participant->is_completed == 1) {
                echo "<span class='badge bg-success text-white'>" . get_string('status_completed', 'block_learning_style') . "</span>";
            } else {
                // Calcular progreso - contar respuestas no nulas (q1 a q44)
                $answered = 0;
                for ($i = 1; $i <= 44; $i++) {
                    $field = 'q' . $i;
                    if (isset($participant->$field) && $participant->$field !== null && $participant->$field !== '') {
                        $answered++;
                    }
                }
                echo "<span class='badge bg-warning text-dark'>" . get_string('status_in_progress', 'block_learning_style') . "</span>";
                echo "<br><small class='text-muted'>" . get_string('progress_questions', 'block_learning_style', ['answered' => $answered, 'total' => 44]) . "</small>";
            }
            echo "</td>";
            
            // Learning Profile (solo si está completado)
            echo "<td>";
            if ($participant->is_completed == 1) {
                $profile = array();
                $profile[] = ($participant->ap_active > $participant->ap_reflexivo) ? get_string('active', 'block_learning_style') : get_string('reflexive', 'block_learning_style');
                $profile[] = ($participant->ap_sensorial > $participant->ap_intuitivo) ? get_string('sensorial', 'block_learning_style') : get_string('intuitive', 'block_learning_style');
                $profile[] = ($participant->ap_visual > $participant->ap_verbal) ? get_string('visual', 'block_learning_style') : get_string('verbal', 'block_learning_style');
                $profile[] = ($participant->ap_secuencial > $participant->ap_global) ? get_string('sequential', 'block_learning_style') : get_string('global', 'block_learning_style');
                echo "<strong>" . implode(', ', $profile) . "</strong>";
            } else {
                echo "<span class='text-muted'>-</span>";
            }
            echo "</td>";
            
            $date_to_show = $participant->updated_at > 0 ? $participant->updated_at : $participant->created_at;
            echo "<td>" . date('d/m/Y H:i', $date_to_show) . "</td>";
            echo "<td>";
                echo "<a href='" . new moodle_url('/blocks/learning_style/view_individual.php', 
                    array('userid' => $participant->user, 'courseid' => $courseid)) . 
                    "' class='btn btn-sm btn-info mr-2 mt-1 mb-1' title='" . get_string('view_details', 'block_learning_style') . "'>";
                echo "<i class='fa fa-eye'></i> " . get_string('view_details', 'block_learning_style');
            echo "</a>";
            echo "<a href='" . new moodle_url('/blocks/learning_style/admin_view.php', 
                    array('courseid' => $courseid, 'action' => 'delete', 'userid' => $participant->user, 'sesskey' => sesskey())) . 
                    "' class='btn btn-sm btn-danger' title='" . get_string('delete') . "'>";
                echo "<i class='fa fa-trash'></i> " . get_string('delete');
            echo "</a>";
            echo "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
}

// Botón para regresar al curso
echo "<div class='mt-4 text-center'>";
echo "<a href='" . new moodle_url('/course/view.php', array('id' => $courseid)) . "' class='btn btn-secondary'>";
echo "<i class='fa fa-arrow-left'></i> " . get_string('back_to_course', 'block_learning_style');
echo "</a>";
echo "</div>";

echo "</div>";

// JavaScript para funcionalidad
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    
    function filterTable() {
        const filter = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('#participantsTable .participant-row');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matchesSearch = text.includes(filter);
            row.style.display = matchesSearch ? '' : 'none';
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }
    
    // Función para exportar datos
    window.exportData = function(format) {
        if (format === 'csv') {
            window.location.href = '" . $CFG->wwwroot . "/blocks/learning_style/download_results.php?courseid=" . $courseid . "&sesskey=" . sesskey() . "&format=csv';
        }
    };
});
</script>";

echo $OUTPUT->footer();
