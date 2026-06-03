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
 * Moodleform for editing all format_buttons course options from the dedicated settings page.
 *
 * @package     format_buttons
 * @copyright   2023 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_buttons\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/element/colourpicker.php');

\MoodleQuickForm::registerElementType(
    'format_buttons_colourpicker',
    __DIR__ . '/element/colourpicker.php',
    'format_buttons_colourpicker'
);

/**
 * Form covering all course-level format options for format_buttons.
 */
class editgroups_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition(): void {
        $mform = $this->_form;
        $maxgroups   = (int) $this->_customdata['max_groups'];
        $maxsections = (int) $this->_customdata['max_sections'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'header_appearance',
            get_string('section_appearance', 'format_buttons'));
        $mform->setExpanded('header_appearance');

        $mform->addElement('format_buttons_colourpicker', 'colorfont',
            get_string('colorfont', 'format_buttons'));
        $mform->setType('colorfont', PARAM_TEXT);
        $mform->addHelpButton('colorfont', 'colorfont', 'format_buttons');

        $mform->addElement('format_buttons_colourpicker', 'bgcolor',
            get_string('bgcolor', 'format_buttons'));
        $mform->setType('bgcolor', PARAM_TEXT);
        $mform->addHelpButton('bgcolor', 'bgcolor', 'format_buttons');

        $mform->addElement('format_buttons_colourpicker', 'fontcolor_selected',
            get_string('fontcolor_selected', 'format_buttons'));
        $mform->setType('fontcolor_selected', PARAM_TEXT);
        $mform->addHelpButton('fontcolor_selected', 'colorfont', 'format_buttons');

        $mform->addElement('format_buttons_colourpicker', 'bgcolor_selected',
            get_string('bgcolor_selected', 'format_buttons'));
        $mform->setType('bgcolor_selected', PARAM_TEXT);
        $mform->addHelpButton('bgcolor_selected', 'bgcolor', 'format_buttons');

        $mform->addElement('select', 'selectform',
            get_string('selectform', 'format_buttons'), [
                'square'  => get_string('square', 'format_buttons'),
                'rounded' => get_string('rounded', 'format_buttons'),
            ]);
        $mform->setType('selectform', PARAM_TEXT);
        $mform->addHelpButton('selectform', 'selectform', 'format_buttons');

        $mform->addElement('select', 'selectoption',
            get_string('selectoption', 'format_buttons'), [
                'number'          => get_string('option1', 'format_buttons'),
                'leter_lowercase' => get_string('option2', 'format_buttons'),
                'leter_uppercase' => get_string('option3', 'format_buttons'),
                'roman_numbers'   => get_string('option4', 'format_buttons'),
            ]);
        $mform->setType('selectoption', PARAM_TEXT);
        $mform->addHelpButton('selectoption', 'selectoption', 'format_buttons');

        $mform->addElement('header', 'header_display',
            get_string('display_options', 'format_buttons'));
        $mform->setExpanded('header_display');

        $mform->addElement('select', 'title_section_view',
            get_string('title_section_view', 'format_buttons'), [
                '0' => get_string('no'),
                '1' => get_string('yes'),
            ]);
        $mform->setType('title_section_view', PARAM_INT);
        $mform->addHelpButton('title_section_view', 'title_section_view', 'format_buttons');

        $mform->addElement('select', 'section_zero_ubication',
            get_string('section_zero_ubication', 'format_buttons'), [
                '0' => get_string('no'),
                '1' => get_string('yes'),
            ]);
        $mform->setType('section_zero_ubication', PARAM_INT);
        $mform->addHelpButton('section_zero_ubication', 'section_zero_ubication', 'format_buttons');

        $mform->addElement('select', 'group_numbering_reset',
            get_string('group_numbering_reset', 'format_buttons'), [
                '1' => get_string('numbering_reset', 'format_buttons'),
                '0' => get_string('numbering_continuous', 'format_buttons'),
            ]);
        $mform->setType('group_numbering_reset', PARAM_INT);
        $mform->addHelpButton('group_numbering_reset', 'group_numbering_reset', 'format_buttons');

        if ($maxgroups > 0) {
            $numberoptions = [];
            for ($n = 0; $n <= $maxsections; $n++) {
                $numberoptions[$n] = $n;
            }

            for ($i = 1; $i <= $maxgroups; $i++) {
                $mform->addElement('header', 'group_header_' . $i,
                    get_string('title_gruping', 'format_buttons', $i));
                $mform->setExpanded('group_header_' . $i);

                $mform->addElement('select', 'group_sections' . $i,
                    get_string('sections_gruping', 'format_buttons', $i), $numberoptions);
                $mform->setType('group_sections' . $i, PARAM_INT);
                $mform->addHelpButton('group_sections' . $i, 'sections_gruping', 'format_buttons');

                $mform->addElement('text', 'group_title' . $i,
                    get_string('title_gruping', 'format_buttons', $i),
                    ['size' => 40, 'maxlength' => 255]);
                $mform->setType('group_title' . $i, PARAM_TEXT);
                $mform->addHelpButton('group_title' . $i, 'title_gruping', 'format_buttons');

                $mform->addElement('format_buttons_colourpicker', 'group_colorfont' . $i,
                    get_string('color_gruping', 'format_buttons', $i));
                $mform->setType('group_colorfont' . $i, PARAM_TEXT);
                $mform->addHelpButton('group_colorfont' . $i, 'colorfont', 'format_buttons');
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validate colour fields — must be a valid 6-digit hex when set.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors     = parent::validation($data, $files);
        $hexpattern = '/^#[0-9A-Fa-f]{6}$/';
        $maxgroups  = (int) $this->_customdata['max_groups'];

        $colorfields = ['colorfont', 'bgcolor', 'fontcolor_selected', 'bgcolor_selected'];
        for ($i = 1; $i <= $maxgroups; $i++) {
            $colorfields[] = 'group_colorfont' . $i;
        }

        foreach ($colorfields as $field) {
            $color = trim($data[$field] ?? '');
            if ($color !== '' && !preg_match($hexpattern, $color)) {
                $errors[$field] = get_string('invalid_color', 'format_buttons');
            }
        }

        return $errors;
    }
}
