<?php
/**
 * Learning Style Block
 *
 * @package    block_learning_style
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_learning_style extends block_base
{

    public function init()
    {
        $this->title = get_string('pluginname', 'block_learning_style');
    }

    public function instance_allow_multiple()
    {
        return false;
    }

    public function my_slider($value, $izq_val, $der_val, $izq_title, $der_title) {
        $rawp = (($value + 11) / 22) * 100;
        $p = $rawp;
        $edgeclass = '';
        $markertransform = '';
        if ($p <= 0) {
            $p = 0;
            $edgeclass = ' ls-edge-left';
            $markertransform = ' transform: translateX(0);';
        } else if ($p >= 100) {
            $p = 100;
            $edgeclass = ' ls-edge-right';
            $markertransform = ' transform: translateX(-100%);';
        }

        // Determinar si predomina la derecha o izquierda.
        $predomina_izq = $p < 50;

        // Armar etiquetas con botón si es dominante (escapando atributos/texto).
        $buttonattrs = [
            'data-placement' => 'top',
            'role' => 'button',
            'tabindex' => '0',
            'style' => 'background: none; color: #000; font-weight: 700; border: none;text-transform: none;font-size: 16px;padding: 0;cursor: pointer;text-decoration: none;',
            'data-toggle' => 'popover',
            'data-trigger' => 'focus',
        ];

        if ($predomina_izq) {
            $izq_label = html_writer::tag('a', s($izq_val), $buttonattrs + [
                'title' => s($izq_val),
                'data-content' => s($izq_title),
            ]);
        } else {
            $izq_label = html_writer::tag('span', s($izq_val), ['title' => s($izq_title)]);
        }

        if (!$predomina_izq) {
            $der_label = html_writer::tag('a', s($der_val), $buttonattrs + [
                'title' => s($der_val),
                'data-content' => s($der_title),
            ]);
        } else {
            $der_label = html_writer::tag('span', s($der_val), ['title' => s($der_title)]);
        }

        $slider = html_writer::start_div('slider-container', ['style' => 'text-align:center; margin: 10px 0px;']);
        $slider .= $izq_label . ' ⇄ ' . $der_label . html_writer::empty_tag('br');

        // Barra de progreso con marca.
        $slider .= html_writer::start_tag('div', [
            'class' => 'progress learning-style-progress' . $edgeclass,
            'style' => 'position: relative; height: 20px;'
        ]);
        $slider .= html_writer::tag('div', '', [
            'class' => 'center_mark',
            'style' => 'left: 50%; width: 1px; background: #a8a8a8;',
        ]);
        $slider .= html_writer::tag('div', '', [
            'class' => 'center_mark',
            'style' => 'left: ' . (float)$p . '%;' . $markertransform,
        ]);
        $slider .= html_writer::end_tag('div');

        $slider .= html_writer::end_div();
        return $slider;
    }

    /**
     * Main content generator
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if ($COURSE->id == SITEID) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return $this->content;
        }

        if (!isloggedin()) {
            return $this->content;
        }

        $context = context_course::instance($COURSE->id);
        $this->page->requires->css('/blocks/learning_style/styles.css');

        if ($this->can_view_dashboard($context)) {
            $this->content->text = $this->get_teacher_content($context);
        } else if ($this->is_student($context)) {
            $this->content->text = $this->get_student_content();
        }

        return $this->content;
    }

    /**
     * Check if user can view dashboard
     */
    private function can_view_dashboard($context) {
        return has_capability('block/learning_style:viewreports', $context);
    }

    /**
     * Check if user is a student
     */
    private function is_student($context) {
        return has_capability('block/learning_style:take_test', $context);
    }

    /**
     * Generate content for students
     */
    private function get_student_content() {
        global $DB, $USER;
        
        $entry = $DB->get_record('learning_style', array('user' => $USER->id));

        if (!$entry) {
            return $this->get_test_invitation();
        } 
        
        if (isset($entry->is_completed) && $entry->is_completed == 0) {
            $answered = 0;
            for ($i = 1; $i <= 44; $i++) {
                $field = "q{$i}";
                if (isset($entry->$field) && $entry->$field !== null) {
                    $answered++;
                }
            }
            return $this->get_continue_test_card($answered);
        }

        return $this->render_results($entry);
    }

    /**
     * Render results and chart
     */
    private function render_results($entry) {
        global $COURSE, $OUTPUT;

        $results_cards = [];

        // Definition of dimensions and icons
        // Processing: Active vs Reflexive
        if ($entry->ap_active >= $entry->ap_reflexivo) {
             $results_cards[] = [
                 'dimension' => get_string('processing_dimension', 'block_learning_style'),
                 'style' => get_string('active', 'block_learning_style'),
                 'icon' => 'fa-bolt'
             ];
        } else {
             $results_cards[] = [
                 'dimension' => get_string('processing_dimension', 'block_learning_style'),
                 'style' => get_string('reflexive', 'block_learning_style'),
                 'icon' => 'fa-lightbulb-o'
             ];
        }

        // Perception: Sensorial vs Intuitive
        if ($entry->ap_sensorial >= $entry->ap_intuitivo) {
             $results_cards[] = [
                 'dimension' => get_string('perception_dimension', 'block_learning_style'),
                 'style' => get_string('sensorial', 'block_learning_style'),
                 'icon' => 'fa-wrench'
             ];
        } else {
             $results_cards[] = [
                 'dimension' => get_string('perception_dimension', 'block_learning_style'),
                 'style' => get_string('intuitive', 'block_learning_style'),
                 'icon' => 'fa-puzzle-piece'
             ];
        }

        // Input: Visual vs Verbal
        if ($entry->ap_visual >= $entry->ap_verbal) {
             $results_cards[] = [
                 'dimension' => get_string('input_dimension', 'block_learning_style'),
                 'style' => get_string('visual', 'block_learning_style'),
                 'icon' => 'fa-eye'
             ];
        } else {
             $results_cards[] = [
                 'dimension' => get_string('input_dimension', 'block_learning_style'),
                 'style' => get_string('verbal', 'block_learning_style'),
                 'icon' => 'fa-comments-o'
             ];
        }

        // Understanding: Sequential vs Global
        if ($entry->ap_secuencial >= $entry->ap_global) {
             $results_cards[] = [
                 'dimension' => get_string('understanding_dimension', 'block_learning_style'),
                 'style' => get_string('sequential', 'block_learning_style'),
                 'icon' => 'fa-list-ol'
             ];
        } else {
             $results_cards[] = [
                 'dimension' => get_string('understanding_dimension', 'block_learning_style'),
                 'style' => get_string('global', 'block_learning_style'),
                 'icon' => 'fa-globe'
             ];
        }
        
        $template_data = [
            'icon' => $this->get_learning_style_icon('4em', 'display: block;', false),
            'results_cards' => $results_cards,
            'summary_url' => (new moodle_url('/blocks/learning_style/view_individual.php', ['courseid' => $COURSE->id, 'userid' => $entry->user]))->out(false),
            'instanceid' => $this->instance->id,
            'showdescriptions' => !empty($this->config->showdescriptions)
        ];

        return $OUTPUT->render_from_template('block_learning_style/results', $template_data);
    }

    /**
     * Generate content for teachers/admins
     */
    private function get_teacher_content($context) {
        global $CFG, $COURSE, $DB, $OUTPUT;

        // Header with learning style icon for teacher/admin view.
        $icon = $this->get_learning_style_icon('4em', '', true);

        // Assets for embedded dashboard.
        $this->page->requires->css('/blocks/learning_style/dashboard/css/style.css');
        $this->page->requires->js('/blocks/learning_style/dashboard/js/main.js');

        // Render dashboard HTML fragment.
        $embedded_file = $CFG->dirroot . '/blocks/learning_style/dashboard/embedded.php';
        $dashboard_html = '';

        if (file_exists($embedded_file)) {
            ob_start();
            $courseid = $COURSE->id;
            include_once($embedded_file);
            $dashboard_html = ob_get_clean();
        } else {
            $dashboard_html = '<p>' . get_string('dashboard_not_found', 'block_learning_style') . '</p>';
        }

        $template_data = [
            'icon' => $icon,
            'dashboard_html' => $dashboard_html,
            'show_buttons' => false
        ];

        // Agregar botones al final (después del dashboard) para profesores/administradores 
        $is_teacher = has_capability('block/learning_style:viewreports', $context) || is_siteadmin();
        if ($is_teacher) {
            $template_data['show_buttons'] = true;
            $template_data['admin_url'] = (new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $COURSE->id)))->out();
            
            // Determinar si hay tests completados en este curso (solo estudiantes)
            $student_users = get_enrolled_users($context, 'block/learning_style:take_test', 0, 'u.id');
            $student_ids = array_keys($student_users);

            // Defensive: exclude any teacher/manager-type user even if misconfigured.
            $filtered_student_ids = array();
            foreach ($student_ids as $candidateid) {
                $candidateid = (int)$candidateid;
                if (is_siteadmin($candidateid)) {
                    continue;
                }
                if (has_capability('block/learning_style:viewreports', $context, $candidateid)) {
                    continue;
                }
                $filtered_student_ids[] = $candidateid;
            }
            $student_ids = $filtered_student_ids;

            $has_completed = false;
            if (!empty($student_ids)) {
                list($insql, $params) = $DB->get_in_or_equal($student_ids, SQL_PARAMS_NAMED, 'user');
                $completed_count = $DB->count_records_sql("SELECT COUNT(*) FROM {learning_style} WHERE user $insql AND is_completed = 1", $params);
                $has_completed = ($completed_count > 0);
            }
            
            $template_data['show_download'] = $has_completed;
            if ($has_completed) {
                $template_data['download_url'] = (new moodle_url('/blocks/learning_style/download_results.php', array('courseid' => $COURSE->id, 'sesskey' => sesskey())))->out(false);
            }
        }

        return $OUTPUT->render_from_template('block_learning_style/teacher_dashboard', $template_data);
    }

    /**
     * Get the learning style icon HTML (SVG)
     */
    private function get_learning_style_icon($size = '1.8em', $additional_style = '', $centered = false) {
        global $CFG;
        $iconurl = new moodle_url('/blocks/learning_style/pix/icon.svg');
        // NOTE: styles.css has a legacy rule that floats all images in the block.
        // This icon must never float, otherwise it cannot be centered.
        $style = 'width: ' . $size . '; height: ' . $size . '; vertical-align: middle; float: none !important;';
        if ($centered) {
            $style .= ' display: block; margin: 0 auto;';
        }
        if ($additional_style) {
            $style .= ' ' . $additional_style;
        }
        return '<img class="learning-style-icon" src="' . $iconurl . '" alt="Learning Style Icon" style="' . $style . '" />';
    }

    /**
     * Método para mostrar la invitación al test de estilos de aprendizaje
     */
    private function get_test_invitation() {
        global $COURSE, $OUTPUT;
        
        $template_data = [
            'icon' => $this->get_learning_style_icon('4em', '', true),
            'url' => (new moodle_url('/blocks/learning_style/view.php', array('cid' => $COURSE->id)))->out(),
            'showdescriptions' => !empty($this->config->showdescriptions)
        ];
        
        return $OUTPUT->render_from_template('block_learning_style/test_invitation', $template_data);
    }

    /**
     * Display continue test card for students with test in progress
     */
    private function get_continue_test_card($answered_count) {
        global $COURSE, $OUTPUT; 
        
        $progress_percentage = ($answered_count / 44) * 100;
        $all_answered = ($answered_count == 44);
        
        // Change button text, icon and URL based on completion status
        if ($all_answered) {
            $button_text = get_string('finish_test', 'block_learning_style');
            $button_icon = 'fa-flag-checkered';
            $button_class = 'btn-success';

            $questions_per_page = 11;
            $total_questions = 44;
            $last_page = (int)ceil($total_questions / $questions_per_page);

            $url = new moodle_url('/blocks/learning_style/view.php', array(
                'cid' => $COURSE->id,
                'page' => $last_page,
                'scroll_to_finish' => 1
            ));
        } else {
            $button_text = get_string('continue_test', 'block_learning_style');
            $button_icon = 'fa-play';
            $button_class = 'btn-warning';
            $url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $COURSE->id));
        }

        $template_data = [
            'icon' => $this->get_learning_style_icon('4em', 'filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));', true),
            'answered_count' => $answered_count,
            'progress_percentage' => $progress_percentage,
            'formatted_percentage' => number_format($progress_percentage, 1),
            'all_answered' => $all_answered,
            'url' => $url->out(false),
            'button_text' => $button_text,
            'button_icon' => $button_icon,
            'button_class' => $button_class,
            'showdescriptions' => !empty($this->config->showdescriptions)
        ];
        
        return $OUTPUT->render_from_template('block_learning_style/continue_test', $template_data);
    }
}
