<?php

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

    public function get_content()
    {
        global $CFG, $DB, $USER, $COURSE;

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

        // Dashboard viewers (teacher/admin) must never be treated as students.
        $can_view_dashboard = is_siteadmin()
            || has_capability('block/learning_style:viewreports', $context)
            || has_capability('moodle/course:viewhiddensections', $context);

        // Determine if the user has a student role in this course
        $is_student = !$can_view_dashboard && has_capability('block/learning_style:take_test', $context);

        //Check if user is student
        if ($is_student) {
            //check if user already have the learning style (in any course)
            $entry = $DB->get_record('learning_style', array('user' => $USER->id));

            if (!$entry) {
                // Mostrar invitación al test sin redirigir
                $this->content->text .= $this->get_test_invitation();
            } else if ($entry && isset($entry->is_completed) && $entry->is_completed == 0) {
                // Test in progress - show continue option
                $answered = 0;
                for ($i = 1; $i <= 44; $i++) {
                    $field = "q{$i}";
                    if (isset($entry->$field) && $entry->$field !== null) {
                        $answered++;
                    }
                }
                $this->content->text .= $this->get_continue_test_card($answered);
            } else {
                $final_style = [];

                $izq_title = get_string('active_recommendations', 'block_learning_style');
                $der_title = get_string('reflexive_recommendations', 'block_learning_style');

                // Si Activo es mayor, el resultado es negativo y la barra se va a la izquierda.
                $diff_active = $entry->ap_reflexivo - $entry->ap_active; 
                $final_style[abs($diff_active) . "ar"] = $this->my_slider($diff_active, get_string("active", 'block_learning_style'), get_string("reflexive", 'block_learning_style'),$izq_title,$der_title);

                $izq_title = get_string('sensorial_recommendations', 'block_learning_style');
                $der_title = get_string('intuitive_recommendations', 'block_learning_style');
                
                // Intuitivo (der) - Sensorial (izq)
                $diff_sensorial = $entry->ap_intuitivo - $entry->ap_sensorial;
                $final_style[abs($diff_sensorial) . "si"] = $this->my_slider($diff_sensorial, get_string("sensorial", 'block_learning_style'), get_string("intuitive", 'block_learning_style'),$izq_title,$der_title);

                $izq_title = get_string('visual_recommendations', 'block_learning_style');
                $der_title = get_string('verbal_recommendations', 'block_learning_style');
                
                // Verbal (der) - Visual (izq)
                $diff_visual = $entry->ap_verbal - $entry->ap_visual;
                $final_style[abs($diff_visual) . "vv"] = $this->my_slider($diff_visual, get_string("visual", 'block_learning_style'), get_string("verbal", 'block_learning_style'),$izq_title,$der_title);

                $izq_title = get_string('sequential_recommendations', 'block_learning_style');
                $der_title = get_string('global_recommendations', 'block_learning_style');
                
                // Global (der) - Secuencial (izq)
                $diff_secuencial = $entry->ap_global - $entry->ap_secuencial;
                $final_style[abs($diff_secuencial) . "sg"] = $this->my_slider($diff_secuencial, get_string("sequential", 'block_learning_style'), get_string("global", 'block_learning_style'),$izq_title,$der_title);
                krsort($final_style);

                // Header con icono de éxito
                $this->content->text .= '<div class="learning-results-block" style="padding: 15px; background: white; border-radius: 8px; border: 1px solid #dee2e6;">';
                $this->content->text .= '<div class="learning-header text-center mb-3">';
                $this->content->text .= '<div style="position: relative; display: inline-block; line-height: 0;">';
                $this->content->text .= $this->get_learning_style_icon('4em', 'display: block;', false);
                $this->content->text .= '<i class="fa fa-check text-primary" style="position: absolute; top: -6px; right: -9px; font-size: 1.4em; background: white; border-radius: 50%; line-height: 1; text-shadow: 0 1px 2px rgba(0,0,0,0.1);"></i>';
                $this->content->text .= '</div>';
                $this->content->text .= '<h6 class="mt-2 mb-1">' . get_string('test_completed', 'block_learning_style') . '</h6>';
                $this->content->text .= '<small class="text-muted">' . get_string('your_learning_style', 'block_learning_style') . '</small>';
                $this->content->text .= '</div>';
                
                // Test description
                $this->content->text .= '<div class="learning-description mb-3" style="background: #f8f9fa; padding: 10px 12px; border-radius: 5px; border-left: 3px solid #0d6efd;">';
                $this->content->text .= '<small class="text-muted" style="line-height: 1.5;">';
                $this->content->text .= '<i class="fa fa-info-circle" style="color: #0d6efd;"></i> ';
                $this->content->text .= get_string('test_description', 'block_learning_style');
                $this->content->text .= '</small>';
                $this->content->text .= '</div>';
                
                $this->content->text .= "<p>" . get_string('felder_soloman_intro', 'block_learning_style') . "</p>";
                $this->content->text .= html_writer::start_tag('ul', ['class' => 'lsorder']);
                foreach ($final_style as $key => $val) {
                    $this->content->text .= html_writer::tag('li', $val);
                }
                $this->content->text .= html_writer::end_tag('ul');

                // Inicializar popovers de forma segura
                // Initialize popovers using an AMD module that assumes Bootstrap is provided by Moodle/theme.
                $this->page->requires->js_call_amd('block_learning_style/popoverinit', 'init');

                // Mensaje final
                $this->content->text .= html_writer::tag('p', get_string('click_bold_for_recommendations', 'block_learning_style'), ['class' => 'alpyintro', 'style' => 'margin: 0;']);
                $json_style = [
                    "act" => intval($entry->ap_active),
                    "ref" => intval($entry->ap_reflexivo),
                    "sen" => intval($entry->ap_sensorial),
                    "int" => intval($entry->ap_intuitivo),
                    "vis" => intval($entry->ap_visual),
                    "vrb" => intval($entry->ap_verbal),
                    "seq" => intval($entry->ap_secuencial),
                    "glo" => intval($entry->ap_global)
                ];
                $json_encode = json_encode($json_style);
                // Canvas del gráfico radar (sin width ni height, lo controla CSS)
                $this->content->text .= "<canvas id='radarstyle' style='width: 100%;'></canvas>";

                $chartjs_code = "
                    var data = $json_encode;

                    require(['core/chartjs'], function(Chart) {
                        var canvas = document.getElementById('radarstyle');
                        if (!canvas) { return; }
                        var ctx = canvas.getContext('2d');
                        new Chart(ctx, {
                            type: 'radar',
                            data: {
                                labels: [
                                    '" . get_string('chart_visual', 'block_learning_style') . "',
                                    '" . get_string('chart_sensorial', 'block_learning_style') . "',
                                    '" . get_string('chart_active', 'block_learning_style') . "',
                                    '" . get_string('chart_global', 'block_learning_style') . "',
                                    '" . get_string('chart_verbal', 'block_learning_style') . "',
                                    '" . get_string('chart_intuitive', 'block_learning_style') . "',
                                    '" . get_string('chart_reflexive', 'block_learning_style') . "',
                                    '" . get_string('chart_sequential', 'block_learning_style') . "'
                                ],
                                datasets: [{
                                    label: '" . get_string('learning_style_label', 'block_learning_style') . "',
                                    data: [
                                        data.vis,
                                        data.sen,
                                        data.act,
                                        data.glo,
                                        data.vrb,
                                        data.int,
                                        data.ref,
                                        data.seq
                                    ],
                                    backgroundColor: 'rgba(21, 103, 249, 0.2)',
                                    borderColor: 'rgba(21, 103, 249, 1)',
                                    borderWidth: 2,
                                    pointBackgroundColor: 'rgba(21, 103, 249, 1)'
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    r: {
                                        min: 0,
                                        max: 11,
                                        angleLines: { 
                                            display: true,
                                            color: 'rgba(0, 0, 0, 0.1)'
                                        },
                                        grid: {
                                            // AQUÍ QUITAMOS LAS LÍNEAS PARES
                                            color: function(context) {
                                                if (context.tick.value % 2 === 1) {
                                                    return 'transparent';
                                                }
                                                return 'rgba(0, 0, 0, 0.1)';
                                            },
                                            lineWidth: 1
                                        },
                                        ticks: {
                                            stepSize: 1,
                                            display: true,
                                            backdropColor: 'transparent',
                                            // TAMBIÉN QUITAMOS LOS NÚMEROS PARES DE LAS ETIQUETAS
                                            callback: function(value) {
                                                return value % 2 !== 0 ? value : '';
                                            },
                                            font: {
                                                size: 12
                                            }
                                        },
                                        pointLabels: {
                                            font: {size: 14}
                                        }
                                    }
                                },
                                plugins: {
                                    legend: { display: false }
                                }
                            }
                        });
                    });
                ";
                $this->page->requires->js_init_code($chartjs_code);
                $this->content->text .= '</div>'; // Cerrar learning-results-block
            }
        } else {
            if ($can_view_dashboard) {
                // Embed the dashboard UI inside the block.
                // IMPORTANT: The embedded fragment must not call require_login() or output header/footer.

                // Header with learning style icon for teacher/admin view.
                $this->content->text .= '<div class="learning-header text-center mb-3" style="text-align: center !important;">';
                $this->content->text .= $this->get_learning_style_icon('4em', '', true);
                $this->content->text .= '<h6 class="mt-2 mb-1 font-weight-bold">' . get_string('management_title', 'block_learning_style') . '</h6>';
                $this->content->text .= '<small class="text-muted">' . get_string('course_overview', 'block_learning_style') . '</small>';
                $this->content->text .= '</div>';

                // Assets for embedded dashboard.
                $this->page->requires->css('/blocks/learning_style/dashboard/css/style.css');
                $this->page->requires->js('/blocks/learning_style/dashboard/js/main.js');

                // Render dashboard HTML fragment.
                $embedded_file = $CFG->dirroot . '/blocks/learning_style/dashboard/embedded.php';
                if (file_exists($embedded_file)) {
                    ob_start();
                    $courseid = $COURSE->id;
                    include_once($embedded_file);
                    $this->content->text .= ob_get_clean();
                } else {
                    $this->content->text .= '<p>' . get_string('dashboard_not_found', 'block_learning_style') . '</p>';
                }

                // Agregar botones al final (después del dashboard) para profesores/administradores 
                $is_teacher = has_capability('block/learning_style:viewreports', $context) || is_siteadmin();
                if ($is_teacher) {
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

                // URLs
                $download_url = new moodle_url('/blocks/learning_style/download_results.php', array('courseid' => $COURSE->id, 'sesskey' => sesskey()));
                $admin_url = new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $COURSE->id));

                // Construir contenedor de botones
                $buttons_container = html_writer::start_div('text-center', array('style' => 'margin: 10px 0;'));
                $buttons_container .= html_writer::link($admin_url,
                    get_string('view_admin_results', 'block_learning_style'),
                    array('class' => 'btn btn-primary btn-sm', 'style' => 'margin: 2px; background: linear-gradient(135deg, #1567f9 0%, #0054ce 100%); border: none;', 'title' => get_string('view_admin_results', 'block_learning_style')));

                // Mostrar botón de descarga solo si hay tests completados
                if ($has_completed) {
                    $buttons_container .= html_writer::link($download_url,
                        get_string('download_results', 'block_learning_style'),
                        array('class' => 'btn btn-sm', 'style' => 'margin: 2px; background-color: #1e7e34; color: #ffffff; text-decoration: none; border: none;', 'title' => get_string('download_results', 'block_learning_style')));
                }

                $buttons_container .= html_writer::end_div();

                $this->content->text .= $buttons_container;
            }
            }
        }

        return $this->content;
    }

    /**
     * Get the learning style icon HTML (SVG)
     */
    private function get_learning_style_icon($size = '1.8em', $additional_style = '', $centered = false) {
        global $CFG;
        $iconurl = new moodle_url('/blocks/learning_style/pix/learning_style_icon.svg');
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
        global $COURSE, $CFG, $SESSION;
        
        $output = '';
        $output .= '<div class="learning-invitation-block">';
        
        // Header with learning style icon
        $output .= '<div class="learning-header text-center mb-3" style="text-align: center !important;">';
        $output .= '<div style="text-align: center; margin: 0 auto;">';
        $output .= $this->get_learning_style_icon('4em', '', true);
        $output .= '</div>';
        $output .= '<h6 class="mt-2 mb-1">' . get_string('test_title', 'block_learning_style') . '</h6>';
        $output .= '<small class="text-muted">' . get_string('discover_your_style', 'block_learning_style') . '</small>';
        $output .= '</div>';
        
        // Test description card
        $output .= '<div class="learning-description mb-3">';
        $output .= '<div class="card border-info">';
        $output .= '<div class="card-body p-3">';
        $output .= '<h6 class="card-title">';
        $output .= '<i class="fa fa-info-circle text-info"></i> ';
        $output .= get_string('what_is_felder', 'block_learning_style');
        $output .= '</h6>';
        $output .= '<p class="card-text small mb-2">' . get_string('test_description', 'block_learning_style') . '</p>';
        $output .= '<ul class="list-unstyled small mb-0">';
        $output .= '<li><i class="fa fa-check text-success"></i> ' . get_string('feature_44_questions', 'block_learning_style') . '</li>';
        $output .= '<li><i class="fa fa-check text-success"></i> ' . get_string('feature_4_dimensions', 'block_learning_style') . '</li>';
        $output .= '<li><i class="fa fa-check text-success"></i> ' . get_string('feature_instant_results', 'block_learning_style') . '</li>';
        $output .= '</ul>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Action button
        $output .= '<div class="learning-actions text-center">';
        $url = new moodle_url('/blocks/learning_style/view.php', array('cid' => $COURSE->id));
        $output .= '<a href="' . $url . '" class="btn btn-warning btn-block">';
        $output .= '<i class="fa fa-rocket"></i> <span>' . get_string('start_test', 'block_learning_style') . '</span>';
        $output .= '</a>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Display continue test card for students with test in progress
     * Design matches CHASIDE block for consistency
     */
    private function get_continue_test_card($answered_count) {
        global $COURSE; 
        
        $output = '';
        $progress_percentage = ($answered_count / 44) * 100;
        $all_answered = ($answered_count == 44);
        
        $output .= '<div class="learning-invitation-block" style="padding: 15px; background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%); border-radius: 8px; border: 1px solid #dee2e6;">';
        
        // Header with learning style icon
        $output .= '<div class="learning-header text-center mb-3" style="text-align: center !important;">';
        $output .= '<div style="text-align: center; margin: 0 auto;">';
        $output .= $this->get_learning_style_icon('4em', 'filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));', true);
        $output .= '</div>';
        $output .= '<h6 class="mt-2 mb-1">' . get_string('test_title', 'block_learning_style') . '</h6>';
        $output .= '<small class="text-muted">' . get_string('discover_your_style', 'block_learning_style') . '</small>';
        $output .= '</div>';
        
        // Description section
        $output .= '<div class="learning-description mb-3" style="background: white; padding: 10px 12px; border-radius: 5px; border-left: 3px solid #1567f9;">';
        $output .= '<small class="text-muted" style="line-height: 1.5;">';
        $output .= '<i class="fa fa-info-circle text-info"></i> ';
        $output .= get_string('test_description', 'block_learning_style');
        $output .= '</small>';
        $output .= '</div>';
        
        // Special alert when all questions are answered but not submitted
        if ($all_answered) {
            $output .= '<div class="alert alert-info mb-3" style="padding: 12px 15px; margin-bottom: 15px; border-left: 4px solid #ffc107; background-color: #fff3cd; border-radius: 4px;">';
            $output .= '<div style="display: flex; align-items: start;">';
            $output .= '<i class="fa fa-exclamation-triangle" style="color: #856404; margin-right: 10px; margin-top: 2px; font-size: 1.2em;"></i>';
            $output .= '<div>';
            $output .= '<strong style="color: #856404;">' . get_string('all_answered_title', 'block_learning_style') . '</strong><br>';
            $output .= '<small style="color: #856404;">' . get_string('all_answered_message', 'block_learning_style') . '</small>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
        
        // Progress section
        $output .= '<div class="learning-progress mb-3" style="background: white; padding: 12px; border-radius: 5px; border: 1px solid #e9ecef;">';
        $output .= '<div class="d-flex justify-content-between align-items-center mb-2">';
        $output .= '<span class="small font-weight-bold">' . get_string('your_progress', 'block_learning_style') . '</span>';
        $output .= '<span class="small text-muted">' . $answered_count . '/44</span>';
        $output .= '</div>';
        $output .= '<div class="progress mb-2" style="height: 8px;">';
        $output .= '<div class="progress-bar" style="width: ' . $progress_percentage . '%; background: linear-gradient(135deg, #649dffff 0%, #0054ce 100%);"></div>';
        $output .= '</div>';
        $output .= '<small class="text-muted">' . number_format($progress_percentage, 1) . '% ' . get_string('completed', 'block_learning_style') . '</small>';
        $output .= '</div>';
        
        // Call to action button
        $output .= '<div class="learning-actions text-center">';

        // Change button text, icon and URL based on completion status
        if ($all_answered) {
            $button_text = get_string('finish_test', 'block_learning_style');
            $button_icon = 'fa-flag-checkered';
            $button_class = 'btn-success';

            // Jump to the last page (where the real finish button exists) and highlight it.
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
        
        $output .= '<a href="' . $url . '" class="btn ' . $button_class . ' btn-block" style="box-shadow: 0 2px 4px rgba(0,0,0,0.2); font-weight: 500; transition: all 0.3s ease;">';
        $output .= '<i class="fa ' . $button_icon . '"></i> ' . $button_text;
        $output .= '</a>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }
}
