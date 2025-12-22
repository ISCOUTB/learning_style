<?php
// Dashboard fragment intended to be embedded inside a block.

defined('MOODLE_INTERNAL') || die();

global $COURSE, $OUTPUT;
global $CFG;

// Ensure we have a valid integer course id. Avoid non-standard helpers like is_number().
if (!isset($courseid) || !is_numeric($courseid) || (int)$courseid <= 0) {
    // Fall back to the current course when no valid value provided.
    $courseid = (int)$COURSE->id;
} else {
    // Force integer cast to avoid type juggling.
    $courseid = (int)$courseid;
}

?>
<div id="learning-style-dashboard" data-courseid="<?php echo (int)$courseid; ?>">
    <div class="block_term" data-tooltip="<?php echo get_string('dashboard_surveyed_tooltip', 'block_learning_style'); ?>">
        <div class="flex">
            <div class="icon_term">
                <img src="<?php echo new moodle_url('/blocks/learning_style/dashboard/assets/ent.png'); ?>" alt="">
            </div>
            <div class="value" id="total_enc">...</div>
        </div>
        <span id="label-surveyed"><?php echo get_string('dashboard_surveyed', 'block_learning_style'); ?></span>
    </div>

    <div class="block_term" data-tooltip="<?php echo get_string('dashboard_dominant_tooltip', 'block_learning_style'); ?>" id="dominant-style-block" style="display: none;">
        <div class="flex">
            <div class="icon_term">
                <img src="<?php echo new moodle_url('/blocks/learning_style/dashboard/assets/grupo.png'); ?>" alt="">
            </div>
            <div class="value" id="est_dom">...</div>
        </div>
        <span id="label-dominant"><?php echo get_string('dashboard_dominant', 'block_learning_style'); ?></span>
    </div>

    <div class="block_term" data-tooltip="<?php echo get_string('dashboard_least_dominant_tooltip', 'block_learning_style'); ?>" id="least-dominant-style-block" style="display: none;">
        <div class="flex">
            <div class="icon_term">
                <img src="<?php echo new moodle_url('/blocks/learning_style/dashboard/assets/solo.png'); ?>" alt="">
            </div>
            <div class="value" id="est_men_dom">...</div>
        </div>
        <span id="label-least-dominant"><?php echo get_string('dashboard_least_dominant', 'block_learning_style'); ?></span>
    </div>

    <div id="no-data-message" style="display: none;"></div>

    <div id="charts-section">
        <div class="chart-selector-wrapper">
            <label for="chart-type-selector" class="chart-selector-label" id="chart-selector-label"><?php echo get_string('dashboard_select_chart', 'block_learning_style'); ?></label>

            <select id="chart-type-selector" class="custom-dashboard-select">
                <option value="radar"><?php echo get_string('dashboard_chart_radar', 'block_learning_style'); ?></option>
                <option value="pie"><?php echo get_string('dashboard_chart_pie', 'block_learning_style'); ?></option>
                <option value="bar"><?php echo get_string('dashboard_chart_bar', 'block_learning_style'); ?></option>
            </select>
        </div>

        <div class="c_graf">
            <canvas id="grafic" class="graf_term" style="width: 100%; height: auto;"></canvas>
        </div>

        <div class="c_graf" style="display: none">
            <canvas id="distr_pie" class="graf_term" style="width: 100%; height: auto;"></canvas>
        </div>
        <div class="c_graf" style="display: none">
            <canvas id="distr_bar" class="graf_term" height="300px"></canvas>
        </div>

        <div class="block_term">
            <div class="expandible">
                <div class="flex" id="expandir_actor">
                    <div id="label-dominance-order">
                        <?php echo get_string('dashboard_dominance_order', 'block_learning_style'); ?>
                    </div>
                    <button class="button_expandir" type="button">
                        <img src="<?php echo new moodle_url('/blocks/learning_style/dashboard/assets/exp.png'); ?>" alt="Expandir/Contraer" id="icon_exp">
                    </button>
                </div>
                <div id="learning_style_exp" class="learning_style_exp_close"></div>
            </div>
        </div>
    </div>
</div>
