<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
use core\output\inplace_editable;

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     format_buttons
 * @copyright   2023 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_buttons extends core_courseformat\base
{
    /**
     * Construct
     *
     * @throws \core\exception\coding_exception
     */
    protected function __construct($format, $courseid)
    {
        parent::__construct($format, $courseid);
        $this->set_sectionnum(null);

    }

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections()
    {
        return true;
    }


    /**
     * Return identation
     *
     * @return bool
     */
    public function uses_indentation(): bool
    {
        return true;
    }

    /**
     * Return index
     *
     * @return true
     */
    public function uses_course_index()
    {
        return true;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax()
    {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Return use components
     *
     * @return true
     */
    public function supports_components()
    {
        return true;
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section)
    {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news()
    {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * This method is required for inplace section name editor.
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     * @throws \core\exception\moodle_exception
     */
    public function get_section_name($section)
    {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string(
                $section->name,
                true,
                ['context' => context_course::instance($this->courseid)]
            );
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Opciones personalizadas del curso
     *
     * @param $foreditform
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function course_format_options($foreditform = false)
    {
        $courseformatoptionsedit['colorfont'] = array(
            'label' => get_string('colorfont', 'format_buttons'),
            'help' => 'colorfont',
            'help_component' => 'format_buttons',
            'element_type' => 'text',
            'default' => get_config('format_buttons', 'fontcolor')
        );

        $courseformatoptionsedit['bgcolor'] = array(
            'label' => get_string('bgcolor', 'format_buttons'),
            'help' => 'bgcolor',
            'help_component' => 'format_buttons',
            'element_type' => 'text',
            'default' => get_config('format_buttons', 'bgcolor')
        );

        $courseformatoptionsedit['bgcolor_selected'] = array(
            'label' => get_string('bgcolor_selected', 'format_buttons'),
            'help' => 'bgcolor',
            'help_component' => 'format_buttons',
            'element_type' => 'text',
            'default' => get_config('format_buttons', 'bgcolor_selected')
        );

        $courseformatoptionsedit['fontcolor_selected'] = array(
            'label' => get_string('fontcolor_selected', 'format_buttons'),
            'help' => 'colorfont',
            'help_component' => 'format_buttons',
            'element_type' => 'text',
            'default' => get_config('format_buttons', 'fontcolor_selected')
        );

        $opt = get_config('format_buttons', 'selectoption');
        $courseformatoptionsedit['selectoption'] = array(
            'label' => get_string('selectoption', 'format_buttons'),
            'help' => 'selectoption',
            'help_component' => 'format_buttons',
            'element_type' => 'select',
            'default' => $opt,
            'element_attributes' => array(
                array(
                    'number' => get_string('option1', 'format_buttons'),
                    'leter_lowercase' => get_string('option2', 'format_buttons'),
                    'leter_uppercase' => get_string('option3', 'format_buttons'),
                    'roman_numbers' => get_string('option4', 'format_buttons')
                )
            )
        );

        $courseformatoptionsedit['selectform'] = array(
            'label' => get_string('selectform', 'format_buttons'),
            'help' => 'selectform',
            'help_component' => 'format_buttons',
            'element_type' => 'select',
            'default' => 'rounded',
            'element_attributes' => array(
                array(
                    'square' => get_string('square', 'format_buttons'),
                    'rounded' => get_string('rounded', 'format_buttons'),
                )
            )
        );

        $courseformatoptionsedit['title_section_view'] = array(
            'label' => get_string('title_section_view', 'format_buttons'),
            'help' => 'title_section_view',
            'help_component' => 'format_buttons',
            'element_type' => 'select',
            'default' => '1',
            'element_attributes' => array(
                array(
                    '0' => get_string('no'),
                    '1' => get_string('yes'),
                )
            )
        );

        $courseformatoptionsedit['section_zero_ubication'] = array(
            'label' => get_string('section_zero_ubication', 'format_buttons'),
            'help' => 'section_zero_ubication',
            'help_component' => 'format_buttons',
            'element_type' => 'select',
            'default' => '0',
            'element_attributes' => array(
                array(
                    '0' => get_string('no'),
                    '1' => get_string('yes'),
                )
            )
        );

        $max_groups = get_config('format_buttons', 'max_groups');
        if ($max_groups != 0) {
            $max_sections = count($this->get_sections());
            $numbers = range(0, $max_sections);

            for ($i = 0; $i < $max_groups; $i++) {
                $courseformatoptionsedit['group_sections' . ($i + 1)] = array(
                    'label' => get_string('sections_gruping', 'format_buttons', $i + 1),
                    'help' => 'sections_gruping',
                    'help_component' => 'format_buttons',
                    'element_type' => 'select',
                    'default' => '0',
                    'element_attributes' => array(
                        $numbers
                    )
                );

                $courseformatoptionsedit['group_title' . ($i + 1)] = array(
                    'label' => get_string('title_gruping', 'format_buttons', $i + 1),
                    'help' => 'title_gruping',
                    'help_component' => 'format_buttons',
                    'element_type' => 'text',
                );

                $courseformatoptionsedit['group_colorfont' . ($i + 1)] = array(
                    'label' => get_string('color_gruping', 'format_buttons', $i + 1),
                    'help' => 'colorfont',
                    'help_component' => 'format_buttons',
                    'element_type' => 'text',
                );
            }
        }

        return $courseformatoptionsedit;
    }

    /**
     * Delete section
     *
     * @param $sectionornum
     * @param $forcedeleteifnotempty
     * @return bool
     */
    public function delete_section($sectionornum, $forcedeleteifnotempty = false)
    {
        return parent::delete_section($sectionornum, $forcedeleteifnotempty); // TODO: Change the autogenerated stub
    }

    /**
     * Return default blocks
     *
     * @return array[]
     */
    public function get_default_blocks()
    {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Return sthealth activities
     *
     * @param $cm
     * @param $section
     * @return true
     */
    public function allow_stealth_module_visibility($cm, $section)
    {
        return true;
    }

    /**
     * Function taken from format_topics
     *
     * @param $mform
     * @param $forsection
     * @return array
     * @throws dml_exception
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Custom sections are always created with the default number of sections.
            $courseconfig = get_config('moodlecourse');
            $element = $mform->addElement('hidden', 'numsections');
            $mform->setType('numsections', PARAM_INT);
            $mform->setDefault('numsections', $courseconfig->numsections);
            array_unshift($elements, $element);
        }

        return $elements;
    }

}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * This method is required for inplace section name editor.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 * @throws dml_exception
 */
function format_buttons_inplace_editable($itemtype, $itemid, $newvalue)
{
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'buttons'],
            MUST_EXIST
        );
        $format = core_courseformat\base::instance($section->course);
        return $format->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}
