<?php
/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     format_buttons
 * @copyright   2023 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
        'format_buttons/color_font',
        get_string('settings', 'format_buttons'), null
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'format_buttons/fontcolor',
        get_string('fontcolor', 'format_buttons'),
        get_string('fontcolor_desc', 'format_buttons'),
        '#FFFFFF' // Default value.
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'format_buttons/bgcolor',
        get_string('bgcolor', 'format_buttons'),
        get_string('bgcolor_desc', 'format_buttons'),
        '#4d2433' // Default value.
    ));

    $options = array(
        'number' => get_string('option1', 'format_buttons'),
        'leter_lowercase' => get_string('option2', 'format_buttons'),
        'leter_uppercase' => get_string('option3', 'format_buttons'),
        'roman_numbers' => get_string('option4', 'format_buttons')
    );

    $settings->add(new admin_setting_configselect(
        'format_buttons/selectoption',
        get_string('numeretion', 'format_buttons'),
        get_string('numeretion_desc', 'format_buttons'),
        'option1', // Default value.
        $options
    ));

    $optionsalignment = array(
        'start' => get_string('selectbuttonalignmentleft', 'format_buttons'),
        'center' => get_string('selectbuttonalignmentcenter', 'format_buttons'),
        'end' => get_string('selectbuttonalignmentright', 'format_buttons')
    );

    $settings->add(new admin_setting_configselect(
        'format_buttons/selectbuttonalignment',
        get_string('selectbuttonalignment', 'format_buttons'),
        get_string('selectbuttonalignment_desc', 'format_buttons'),
        'start', // Default value.
        $optionsalignment
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'format_buttons/fontcolor_selected',
        get_string('fontcolor_selected', 'format_buttons'),
        get_string('fontcolor_selected_desc', 'format_buttons'),
        '#e7e7e7' // Default value.
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'format_buttons/bgcolor_selected',
        get_string('bgcolor_selected', 'format_buttons'),
        get_string('bgcolor_selected_desc', 'format_buttons'),
        '#959494' // Default value.
    ));

    $settings->add(new admin_setting_configstoredfile(
        'format_buttons/image_sections',
        get_string('selectd_file', 'format_buttons'),
        get_string('selectd_file_desc', 'format_buttons'),
        'format_buttons_file',
        itemid: 0,
        options: array('accepted_types' => '.png', 'maxfiles' => 1)
    ));

    $strings = array(
        'zero', 'one', 'two', 'three', 'four', 'five', 'six',
        'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve',
        'thirteen', 'fourteen', 'fifteen', 'sixteen'
    );
    $options = [];
    $counter = 0;
    foreach ($strings as $str) {
        $options[$counter] = get_string($str, 'format_buttons');
        $counter++;
    }
    $settings->add(new admin_setting_configselect(
        'format_buttons/max_groups',
        get_string('groups_course', 'format_buttons'),
        get_string('groups_course_desc', 'format_buttons'),
        4,
        $options
    ));
}
