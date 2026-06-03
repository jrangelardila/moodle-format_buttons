<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Dedicated settings page for all format_buttons course options.
 *
 * @package     format_buttons
 * @copyright   2023 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_login($course);

$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);

$format  = core_courseformat\base::instance($course->id);
$course  = $format->get_course();

$pageurl = new moodle_url('/course/format/buttons/editgroups.php', ['id' => $id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('format_settings', 'format_buttons'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$PAGE->navbar->add($course->shortname, course_get_url($course));
$PAGE->navbar->add(get_string('format_settings', 'format_buttons'), $pageurl);

$maxgroups   = (int) get_config('format_buttons', 'max_groups');
$maxsections = $format->get_last_section_number();

$customdata = [
    'course'       => $course,
    'max_groups'   => $maxgroups,
    'max_sections' => $maxsections,
];

$mform = new \format_buttons\form\editgroups_form(null, $customdata);

/**
 * Ensure a colour value is a valid 6-digit hex string for <input type="color">.
 *
 * Returns the stored value if it is already a valid #rrggbb string.
 * Falls back to the site-admin default, then to $harddefault.
 * Expands 3-digit shorthand (#rgb → #rrggbb) along the way.
 *
 * @param string $stored       Value currently saved in the course format options.
 * @param string $configkey    Key in the format_buttons plugin config (site default).
 * @param string $harddefault  Fallback when both $stored and site config are absent.
 * @return string              Always a valid #rrggbb value.
 */
function format_buttons_normalize_color(string $stored, string $configkey, string $harddefault): string {
    $expand = function (string $c): string {
        // Expand #rgb → #rrggbb.
        if (preg_match('/^#([0-9A-Fa-f])([0-9A-Fa-f])([0-9A-Fa-f])$/', $c, $m)) {
            return '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
        }
        return $c;
    };

    foreach ([$stored, (string) get_config('format_buttons', $configkey), $harddefault] as $candidate) {
        $candidate = trim((string) $candidate);
        $candidate = $expand($candidate);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $candidate)) {
            return strtolower($candidate);
        }
    }

    // Absolute last resort — should never be reached if $harddefault is valid.
    return $harddefault;
}

// Populate with current course format option values.
// Color fields must always receive a valid #rrggbb value for <input type="color">.
$formdata = [
    'id'                     => $id,
    'colorfont'              => format_buttons_normalize_color(
                                    $course->colorfont              ?? '',
                                    'fontcolor',
                                    '#ffffff'
                                ),
    'bgcolor'                => format_buttons_normalize_color(
                                    $course->bgcolor                ?? '',
                                    'bgcolor',
                                    '#0f6cbf'
                                ),
    'fontcolor_selected'     => format_buttons_normalize_color(
                                    $course->fontcolor_selected     ?? '',
                                    'fontcolor_selected',
                                    '#ffffff'
                                ),
    'bgcolor_selected'       => format_buttons_normalize_color(
                                    $course->bgcolor_selected       ?? '',
                                    'bgcolor_selected',
                                    '#0f6cbf'
                                ),
    'selectform'             => $course->selectform             ?? 'rounded',
    'selectoption'           => $course->selectoption           ?? 'number',
    'title_section_view'     => $course->title_section_view     ?? '0',
    'section_zero_ubication' => $course->section_zero_ubication ?? '0',
    'group_numbering_reset'  => $course->group_numbering_reset  ?? '1',
];
for ($i = 1; $i <= $maxgroups; $i++) {
    $formdata['group_sections' . $i]  = $course->{'group_sections' . $i}  ?? 0;
    $formdata['group_title' . $i]     = $course->{'group_title' . $i}     ?? '';
    $formdata['group_colorfont' . $i] = format_buttons_normalize_color(
                                            $course->{'group_colorfont' . $i} ?? '',
                                            'fontcolor',
                                            '#ffffff'
                                        );
}
$mform->set_data($formdata);

if ($mform->is_cancelled()) {
    redirect(course_get_url($course));
} else if ($data = $mform->get_data()) {
    $options = [
        'colorfont'              => clean_param($data->colorfont              ?? '', PARAM_TEXT),
        'bgcolor'                => clean_param($data->bgcolor                ?? '', PARAM_TEXT),
        'fontcolor_selected'     => clean_param($data->fontcolor_selected     ?? '', PARAM_TEXT),
        'bgcolor_selected'       => clean_param($data->bgcolor_selected       ?? '', PARAM_TEXT),
        'selectform'             => clean_param($data->selectform             ?? 'rounded', PARAM_TEXT),
        'selectoption'           => clean_param($data->selectoption           ?? 'number', PARAM_TEXT),
        'title_section_view'     => (int) ($data->title_section_view     ?? 0),
        'section_zero_ubication' => (int) ($data->section_zero_ubication ?? 0),
        'group_numbering_reset'  => (int) ($data->group_numbering_reset  ?? 1),
    ];
    for ($i = 1; $i <= $maxgroups; $i++) {
        $options['group_sections' . $i]  = (int)  ($data->{'group_sections' . $i}  ?? 0);
        $options['group_title' . $i]     = clean_param($data->{'group_title' . $i}     ?? '', PARAM_TEXT);
        $options['group_colorfont' . $i] = clean_param($data->{'group_colorfont' . $i} ?? '', PARAM_TEXT);
    }
    $format->update_course_format_options($options);
    redirect(
        course_get_url($course),
        get_string('settings_saved', 'format_buttons'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('format_settings', 'format_buttons'));
$mform->display();
echo $OUTPUT->footer();
