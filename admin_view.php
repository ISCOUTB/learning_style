<?php
/**
 * Admin Dashboard for Learning Style Block
 *
 * @package    block_learning_style
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');

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

// Check if the block is added to the course
if (!$DB->record_exists('block_instances', array('blockname' => 'learning_style', 'parentcontextid' => $context->id))) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}

// Friendly redirect for unauthorized users
if (!has_capability('block/learning_style:viewreports', $context)) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}

// Parameters
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$search = optional_param('search', '', PARAM_NOTAGS);

$admin_url = new moodle_url('/blocks/learning_style/admin_view.php', array('courseid' => $courseid));
$PAGE->set_url($admin_url);

// Handle Delete Action
if ($action === 'delete' && $userid && confirm_sesskey()) {
    $confirm = optional_param('confirm', 0, PARAM_INT);
    if ($confirm) {
        // Privacy check
        $targetuser = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        if (!is_enrolled($context, $targetuser, 'block/learning_style:take_test', true)
            || has_capability('block/learning_style:viewreports', $context, $userid)) {
            redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
        }
        
        $DB->delete_records('learning_style', array('user' => $userid));
        redirect($admin_url, get_string('learning_style_deleted', 'block_learning_style'));
    }
}

$title = get_string('admin_title', 'block_learning_style');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title . " : " . $course->fullname);
$PAGE->set_heading($title . " : " . $course->fullname);
$PAGE->requires->css('/blocks/learning_style/styles.css');

// Template Data Construction
$data = [
    'title' => $title,
    'plugin_icon_url' => (new moodle_url('/blocks/learning_style/pix/icon.svg'))->out(),
    'description' => format_text(get_string('admin_dashboard_description', 'block_learning_style'), FORMAT_HTML),
    'courseid' => $courseid,
    'admin_url' => $admin_url->out(false),
    'export_url' => (new moodle_url('/blocks/learning_style/download_results.php', ['courseid' => $courseid, 'sesskey' => sesskey()]))->out(false),
    'course_url' => (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
    'search_term' => $search
];

$user = $DB->get_record('user', array('id' => $userid), 'firstname, lastname');
if ($user) {
    $data['delete_confirmation'] = true;
    $data['confirm_message'] = get_string('confirm_delete_learning_style', 'block_learning_style') . ' ' . fullname($user);
    $data['confirm_url'] = (new moodle_url('/blocks/learning_style/admin_view.php', [
        'courseid' => $courseid,
        'action' => 'delete',
        'userid' => $userid,
        'confirm' => 1,
        'sesskey' => sesskey()
    ]))->out(false);
    $data['cancel_url'] = $admin_url->out(false);
    
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_learning_style/admin_view', $data);
    echo $OUTPUT->footer();
    exit;
}

// 1. Get Enrolled Users Helper
list($esql, $params) = get_enrolled_sql($context, 'block/learning_style:take_test', 0, true);

// 2. Statistics (Count only)
$sql_enrolled = "SELECT COUNT(DISTINCT u.id) FROM {user} u JOIN ($esql) je ON je.id = u.id WHERE u.deleted = 0";
$total_enrolled = $DB->count_records_sql($sql_enrolled, $params);

// SQL for completed/in-progress
$sql_completed_count = "SELECT COUNT(ls.id) FROM {learning_style} ls JOIN ($esql) je ON je.id = ls.user WHERE ls.is_completed = 1";
$total_completed = $DB->count_records_sql($sql_completed_count, $params);

$sql_all_responses = "SELECT COUNT(ls.id) FROM {learning_style} ls JOIN ($esql) je ON je.id = ls.user";
$count_responses = $DB->count_records_sql($sql_all_responses, $params);
$total_in_progress = $count_responses - $total_completed;

$completion_rate = $total_enrolled > 0 ? round(($total_completed / $total_enrolled) * 100, 1) : 0;

$data['total_enrolled'] = $total_enrolled;
$data['total_completed'] = $total_completed;
$data['total_in_progress'] = $total_in_progress;
$data['completion_rate'] = $completion_rate;
$data['has_completed'] = ($total_completed > 0);

// 3. Stats Optimization (SQL instead of Loop)
if ($total_completed > 0) {
    // Single Query for Averages and Distribution Counts
    $sql_stats = "SELECT 
            AVG(ap_active) as avg_active,
            AVG(ap_reflexivo) as avg_reflexivo,
            AVG(ap_sensorial) as avg_sensorial,
            AVG(ap_intuitivo) as avg_intuitivo,
            AVG(ap_visual) as avg_visual,
            AVG(ap_verbal) as avg_verbal,
            AVG(ap_secuencial) as avg_secuencial,
            AVG(ap_global) as avg_global,
            
            SUM(CASE WHEN ap_active > ap_reflexivo THEN 1 ELSE 0 END) as count_active,
            SUM(CASE WHEN ap_reflexivo >= ap_active THEN 1 ELSE 0 END) as count_reflexivo,
            
            SUM(CASE WHEN ap_sensorial > ap_intuitivo THEN 1 ELSE 0 END) as count_sensorial,
            SUM(CASE WHEN ap_intuitivo >= ap_sensorial THEN 1 ELSE 0 END) as count_intuitivo,
            
            SUM(CASE WHEN ap_visual > ap_verbal THEN 1 ELSE 0 END) as count_visual,
            SUM(CASE WHEN ap_verbal >= ap_visual THEN 1 ELSE 0 END) as count_verbal,
            
            SUM(CASE WHEN ap_secuencial > ap_global THEN 1 ELSE 0 END) as count_secuencial,
            SUM(CASE WHEN ap_global >= ap_secuencial THEN 1 ELSE 0 END) as count_global
            
          FROM {learning_style} ls 
          JOIN ($esql) je ON je.id = ls.user 
          WHERE ls.is_completed = 1";

    $stats = $DB->get_record_sql($sql_stats, $params);

    // Process Most Common Types
    $top_styles_list = [
        'Active' => ['count' => $stats->count_active, 'label' => get_string('active', 'block_learning_style'), 'color_hex' => '#e74c3c'],
        'Reflexive' => ['count' => $stats->count_reflexivo, 'label' => get_string('reflexive', 'block_learning_style'), 'color_hex' => '#3498db'],
        'Sensorial' => ['count' => $stats->count_sensorial, 'label' => get_string('sensorial', 'block_learning_style'), 'color_hex' => '#27ae60'],
        'Intuitive' => ['count' => $stats->count_intuitivo, 'label' => get_string('intuitive', 'block_learning_style'), 'color_hex' => '#f39c12'],
        'Visual' => ['count' => $stats->count_visual, 'label' => get_string('visual', 'block_learning_style'), 'color_hex' => '#9b59b6'],
        'Verbal' => ['count' => $stats->count_verbal, 'label' => get_string('verbal', 'block_learning_style'), 'color_hex' => '#e67e22'],
        'Sequential' => ['count' => $stats->count_secuencial, 'label' => get_string('sequential', 'block_learning_style'), 'color_hex' => '#1abc9c'],
        'Global' => ['count' => $stats->count_global, 'label' => get_string('global', 'block_learning_style'), 'color_hex' => '#34495e']
    ];

    uasort($top_styles_list, function($a, $b) {
        return $b['count'] - $a['count'];
    });

    $top_4_styles = array_slice($top_styles_list, 0, 4);
    $formatted_top_styles = [];
    $rank_counter = 1;
    foreach ($top_4_styles as $key => $style) {
        if ($style['count'] > 0) {
            $formatted_top_styles[] = [
                'rank' => $rank_counter++,
                'label' => $style['label'],
                'count' => $style['count'],
                'percentage' => round(($style['count'] / $total_completed) * 100, 1),
                'color_hex' => $style['color_hex']
            ];
        }
    }
    $data['top_styles'] = $formatted_top_styles;

    // Process Dimension Stats (Averages)
    // Structure: Pairs
    $helper_dim = function($avg1, $avg2, $color1, $color2, $l1, $l2) {
        $val1 = round($avg1, 1);
        $val2 = round($avg2, 1);
        $total = $val1 + $val2;
        // avoid div by zero
        $pct1 = $total > 0 ? ($val1 / $total) * 100 : 50;
        $pct2 = $total > 0 ? ($val2 / $total) * 100 : 50;
        
        return [
            'left_label' => $l1,
            'right_label' => $l2,
            'left_avg' => $val1,
            'right_avg' => $val2,
            'left_pct' => $pct1,
            'right_pct' => $pct2,
            'left_color' => $color1,
            'right_color' => $color2
        ];
    };

    $data['dimension_stats'] = [
        $helper_dim($stats->avg_active, $stats->avg_reflexivo, '#e74c3c', '#3498db', get_string('active', 'block_learning_style'), get_string('reflexive', 'block_learning_style')),
        $helper_dim($stats->avg_sensorial, $stats->avg_intuitivo, '#27ae60', '#f39c12', get_string('sensorial', 'block_learning_style'), get_string('intuitive', 'block_learning_style')),
        $helper_dim($stats->avg_visual, $stats->avg_verbal, '#9b59b6', '#e67e22', get_string('visual', 'block_learning_style'), get_string('verbal', 'block_learning_style')),
        $helper_dim($stats->avg_secuencial, $stats->avg_global, '#1abc9c', '#34495e', get_string('sequential', 'block_learning_style'), get_string('global', 'block_learning_style'))
    ];
}

// 4. Participants Table (Paginated & Search)
$userfields = \core_user\fields::for_name()->with_userpic()->get_sql('u', false, '', '', false)->selects;
$where_search = "";
$search_params = [];

if (!empty($search)) {
    $where_search = " AND (" . $DB->sql_like('u.firstname', ':s1', false) . " OR " . $DB->sql_like('u.lastname', ':s2', false) . " OR " . $DB->sql_like('u.email', ':s3', false) . ")";
    $search_params = ['s1' => "%$search%", 's2' => "%$search%", 's3' => "%$search%"];
}

$sql_count_participants = "SELECT COUNT(ls.id) 
                           FROM {learning_style} ls
                           JOIN {user} u ON ls.user = u.id
                           JOIN ($esql) je ON je.id = ls.user
                           WHERE 1=1 $where_search";

$total_participants = $DB->count_records_sql($sql_count_participants, array_merge($params, $search_params));

$sql_list = "SELECT ls.*, {$userfields}
             FROM {learning_style} ls
             JOIN {user} u ON ls.user = u.id
             JOIN ($esql) je ON je.id = ls.user
             WHERE 1=1 $where_search
             ORDER BY ls.created_at DESC";

$participants = $DB->get_records_sql($sql_list, array_merge($params, $search_params), $page * $perpage, $perpage);

// Show table if there are ANY responses in strict sense, OR if a search is active (even if 0 results)
$data['show_table'] = ($count_responses > 0);

$list = [];
if ($participants) {
    foreach ($participants as $p) {
        $userpicture = new user_picture($p);
        $userpicture->size = 35;
        
        $row = [
            'userpicture' => $OUTPUT->render($userpicture),
            'fullname' => fullname($p),
            'email' => $p->email,
            'is_completed' => ($p->is_completed == 1),
            'completion_date' => userdate($p->updated_at, get_string('strftimedatetimeshort')),
            'view_url' => (new moodle_url('/blocks/learning_style/view_individual.php', ['courseid' => $courseid, 'userid' => $p->user]))->out(false),
            'delete_url' => (new moodle_url('/blocks/learning_style/admin_view.php', ['courseid' => $courseid, 'action' => 'delete', 'userid' => $p->user, 'sesskey' => sesskey()]))->out(false)
        ];
        
        if ($p->is_completed == 1) {
             // Calculate brief profile summary using standard strings
             $profile = [];
             $profile[] = ($p->ap_active > $p->ap_reflexivo) ? get_string('active', 'block_learning_style') : get_string('reflexive', 'block_learning_style');
             $profile[] = ($p->ap_sensorial > $p->ap_intuitivo) ? get_string('sensorial', 'block_learning_style') : get_string('intuitive', 'block_learning_style');
             $profile[] = ($p->ap_visual > $p->ap_verbal) ? get_string('visual', 'block_learning_style') : get_string('verbal', 'block_learning_style');
             $profile[] = ($p->ap_secuencial > $p->ap_global) ? get_string('sequential', 'block_learning_style') : get_string('global', 'block_learning_style');
             
             $row['profile_summary'] = implode(', ', $profile);
        } else {
             // Calculate progress for in-progress tests
             $answered = 0;
             for ($i = 1; $i <= 44; $i++) {
                 $field = 'q' . $i;
                 if (isset($p->$field) && $p->$field !== null && $p->$field !== '') {
                     $answered++;
                 }
             }
             $row['answered'] = $answered;
             $row['total_questions'] = 44;
        }
        
        $list[] = $row;
    }
}
$data['list'] = $list;

// Pagination Bar
$baseurl = new moodle_url('/blocks/learning_style/admin_view.php', ['courseid' => $courseid]);
if ($search) {
    $baseurl->param('search', $search);
}
$data['pagination'] = $OUTPUT->render(new paging_bar($total_participants, $page, $perpage, $baseurl, 'page'));

// 5. Render
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_learning_style/admin_view', $data);
echo $OUTPUT->footer();
