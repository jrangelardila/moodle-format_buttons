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

/**
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     format_buttons
 * @category    upgrade
 * @copyright   2023 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_buttons\output\courseformat;

defined('MOODLE_INTERNAL') || die();

use cache;
use context_course;
use core\exception\moodle_exception;
use core_courseformat\base as course_format;
use core_courseformat\output\local\content as content_base;
use moodle_url;
use stdClass;
use TypeError;


class content extends content_base
{
    /**
     * All sections
     * @var
     */
    private $array_sections;
    /**
     * @var string
     */
    private $form_btn;
    /**
     * @var int
     */
    private $selected_section;

    /**
     * @throws moodle_exception
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(course_format $format)
    {
        global $DB;

        parent::__construct($format);

        $this->sectionclass = 'format_buttons\\output\\courseformat\\content\\section';
        $this->sectionselectorclass = 'format_buttons\\output\\courseformat\\content\\sectionselector';
        $this->sectionnavigationclass = 'format_buttons\\output\\courseformat\\content\\sectionnavigation';

        //rounded btns
        $this->form_btn = match ($format->get_course()->selectform) {
            'rounded' => "50%",
            default => "0%",
        };


        global $DB;

        $verified_exist = false;
        $section = $this->get_last_section_access();
        if ($section) {
            $verified_exist = $DB->get_record('course_sections', ['section' => $section,
                'course' => $this->format->get_course()->id, 'component' => null]);
        }
        if ($this->format->get_sectionid() != null) {
            $this->selected_section = $this->format->get_sectionnum();
        } else {
            if ($verified_exist) {
                $this->selected_section = $section;
            }
        }
        if ($this->selected_section == null) {
            if ($this->format->get_course()->section_zero_ubication) {
                $this->selected_section = 0;
            } else {
                $this->selected_section = 1;
            }
        }
        $this->set_array_sections();

    }

    /**
     * Get template
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string
    {
        return 'format_buttons/local/content';
    }

    /**
     * Export data for template
     *
     * @param \renderer_base $output
     * @return object
     * @throws \moodle_exception
     */
    public function export_for_template(\renderer_base $output)
    {
        $format = $this->format;
        $format->set_sectionnum(null);

        $this->save_last_section_access($this->selected_section);

        $data = (object)[
            'title' => $format->page_title(), // This method should be in the course_format class.
            'initialsection' => $this->get_initialsection($output),
            'sections' => [$this->get_export_section($output)],
            'format' => $format->get_format(),
            'sectionreturn' => null,
            'all_sections' => $this->array_sections,
            'bgcolor' => $this->format->get_course()->bgcolor,
            'colorfont' => $this->format->get_course()->colorfont,
            'bgcolor_selected' => $this->format->get_course()->bgcolor_selected,
            'fontcolor_selected' => $this->format->get_course()->fontcolor_selected,
            'form_btn' => $this->form_btn,
            'singlesection' => null,
        ];

        if ($this->hasaddsection) {
            $addsection = new $this->addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
        }

        if ($format->show_editor()) {
            $bulkedittools = new $this->bulkedittoolsclass($format);
            $data->bulkedittools = $bulkedittools->export_for_template($output);
        }
        //If there is a img to init sections, return data
        $file_setting = get_config('format_buttons', 'image_sections');
        if ($file_setting != "") {
            $data->image_init_sectios = $this->get_content_file('format_buttons_file', get_config('format_buttons', 'image_sections'));
        }
        //navigation
        $sectionnavigation = new $this->sectionnavigationclass($format, $this->selected_section);
        $data->sectionnavigation = $sectionnavigation->export_for_template($output);
        $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
        $data->sectionselector = $sectionselector->export_for_template($output);

        return $data;
    }

    /**
     * Use in reemplace to export_sections
     *
     * @param $output
     * @return null
     * @throws \core\exception\coding_exception
     * @throws \moodle_exception
     */
    public function get_export_section($output)
    {
        $course = $this->format->get_course();
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info($this->selected_section);
        $sectionclass = $this->format->get_output_classname('content\\section');
        $sectionoutput = new $sectionclass($this->format, $sectioninfo);
        return $sectionoutput->export_for_template($output);
    }

    /**
     * Return section 0
     *
     * @param $output
     * @return mixed
     * @throws \core\exception\coding_exception
     * @throws \moodle_exception
     */
    public function get_initialsection($output)
    {
        $course = $this->format->get_course();
        if ($course->section_zero_ubication) {
            return null;
        }
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info(0);
        $sectionclass = $this->format->get_output_classname('content\\section');
        $sectionoutput = new $sectionclass($this->format, $sectioninfo);
        return $sectionoutput->export_for_template($output);
    }

    /**
     * Set array sections
     *
     * @throws moodle_exception
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function set_array_sections()
    {
        global $DB;
        $format = $this->format;
        $course = $format->get_course();

        $array_sections = array();

        $all_sections = $DB->get_records('course_sections', array('course' => $course->id, 'component' => null), "section");
        foreach ($all_sections as $section) {
            $info = new \stdClass();
            $info->component = $section->component;
            if ($section->section != 0 && !$course->section_zero_ubication) {
                $info->body = true;
            }
            if ($course->section_zero_ubication) {
                $info->body = true;
            }
            //Ad class selected
            if ($section->section == $this->selected_section) {
                $info->selected = true;
            }

            $url = new moodle_url("/course/section.php", array(
                'id' => $section->id
            ));
            $url->set_anchor("section-$section->section");
            $info->url = $url->out();
            $info->namesection = $section->section;
            //Filter capacibility, and fixed the disabled sections for the teacher
            $isteacher = is_siteadmin() || has_capability('moodle/course:update', context_course::instance($course->id));
            if ($section->visible == 0) {
                $info->cssclass = "font-italic";
                $info->disabled = !$isteacher ? "disabled" : "bg-secondary font-italic";
            }

            $array_sections[] = $info;
        }

        switch ($course->selectoption) {
            case 'leter_lowercase':
                $array_sections = $this->leter_lowercase($array_sections);
                break;
            case 'leter_uppercase':
                $array_sections = $this->leter_uppercase($array_sections);
                break;
            case 'roman_numbers':
                $array_sections = $this->roman_numbers($array_sections);
                break;
            default:
                //default
                break;
        }

        $array_sections = $this->agruping_sections($array_sections, $course);

        $this->array_sections = $array_sections;
    }

    /**
     * lowercase leter
     *
     * @param array $array_sections
     * @return array
     */
    private function leter_lowercase(array $array_sections)
    {
        $count = 0;
        $format = $this->format;
        $course = $format->get_course();
        foreach ($array_sections as $array_section) {

            if (empty($array_section->namesection) && !$course->section_zero_ubication) continue;
            $array_section->namesection = $this->convert_lowercase_letter($count, 'a');
            $count++;
        }

        return $array_sections;
    }

    /**
     * Return sections in uppercase
     *
     * @param array $array_sections
     * @return array
     */
    private function leter_uppercase(array $array_sections)
    {
        $count = 0;
        $format = $this->format;
        $course = $format->get_course();
        foreach ($array_sections as $array_section) {
            if (empty($array_section->namesection) && !$course->section_zero_ubication) continue;
            $array_section->namesection = $this->convert_uppercase_letter($count);
            $count++;
        }

        return $array_sections;
    }

    /**
     * Return sections in roman numbers
     *
     * @param array $array_sections
     * @return array
     */
    private function roman_numbers(array $array_sections)
    {
        $options = $this->get_numbers_in_roman();
        $count = 1;
        $format = $this->format;
        $course = $format->get_course();
        foreach ($array_sections as $array_section) {

            if (empty($array_section->namesection) && !$course->section_zero_ubication) continue;

            $array_section->namesection = $options[$count];
            $count++;
        }

        return $array_sections;
    }

    /**
     * Groping sections
     *
     * @param $array_sections
     * @param $course
     * @return mixed
     * @throws \dml_exception
     */
    public function agruping_sections($array_sections, $course)
    {
        $max_groups = get_config('format_buttons', 'max_groups');

        $atribute_sections = [];
        if ($max_groups != 0) {
            for ($i = 0; $i < $max_groups; $i++) {
                $group_section = "group_sections" . ($i + 1);
                if ($course->{$group_section} != 0) {
                    $obj = new stdClass();
                    $obj->count = $course->{$group_section};

                    $title = "group_title" . ($i + 1);
                    $obj->title = $course->{$title};

                    $color = "group_colorfont" . ($i + 1);
                    $obj->color = $course->{$color};

                    $atribute_sections[$i + 1] = $obj;
                }
            }
        }

        $total_count_group = 0;
        foreach ($atribute_sections as $atribute_section) {
            $num_sections = $atribute_section->count;
            $count = 0;
            $zero = 0;
            $count_first_btn_section = 0;
            if ($num_sections != 0) {
                foreach ($array_sections as $section) {
                    $zero++;
                    if ($zero == 1) continue;
                    if ($count == $num_sections + $total_count_group) break;
                    $count++;
                    if ($total_count_group >= $count) continue;
                    $count_first_btn_section++;
                    $section->bgcolor = $atribute_section->color != "" ? $atribute_section->color : $section->bgcolor;
                    $section->namesection = $num_sections == 1 ? "..." : $this->get_namesection_for_btn($count - $total_count_group, $course);
                    if ($atribute_section->title != "" && $count_first_btn_section == 1) {
                        $section->text_section = $atribute_section->title;
                    }
                }
                $total_count_group += $num_sections;
            }
        }
        return $array_sections;
    }

    /**
     * Convert leter in lowercase
     *
     * @param $num
     * @param $baseChar
     * @return string
     */
    private function convert_lowercase_letter($num, $baseChar)
    {
        $letters = '';

        do {
            $letters = chr(($num % 26) + ord($baseChar)) . $letters;
            $num = intval($num / 26) - 1;
        } while ($num >= 0);

        return $letters;
    }

    /**
     * Leter uppercase
     *
     * @param $num
     * @return string
     */
    private function convert_uppercase_letter($num)
    {
        $letters = '';

        do {
            $letters = chr($num % 26 + 65) . $letters;
            $num = intval($num / 26) - 1;
        } while ($num >= 0);

        return $letters;
    }

    /**
     * Get numbers in roman
     *
     * @return string[]
     */
    private function get_numbers_in_roman()
    {
        $romannumbers = array(
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X',
            20 => 'XX', 30 => 'XXX', 40 => 'XL', 50 => 'L', 60 => 'LX', 70 => 'LXX', 80 => 'LXXX', 90 => 'XC', 100 => 'C'
        );

        for ($i = 11; $i <= 19; $i++) {
            $romannumbers[$i] = 'X' . $romannumbers[$i % 10];
        }

        for ($i = 21; $i <= 99; $i++) {
            if ($i % 10 === 0) {
                $romannumbers[$i] = $romannumbers[$i - $i % 10];
            } else {
                $romannumbers[$i] = $romannumbers[$i - $i % 10] . $romannumbers[$i % 10];
            }
        }
        return $romannumbers;
    }

    /**
     * Return namesection for btn
     *
     * @param $count
     * @param $course
     * @return mixed|string
     */
    public function get_namesection_for_btn($count, $course)
    {
        switch ($course->selectoption) {
            case "number";
                //If number its default
                break;
            case 'leter_lowercase':
                $count--;
                do {
                    $letters = chr(($count % 26) + ord('a')) . $letters;
                    $count = intval($count / 26) - 1;
                } while ($count >= 0);
                $count = $letters;
                break;
            case 'leter_uppercase':
                $count--;
                do {
                    $letters = chr(($count % 26) + ord('A')) . $letters;
                    $count = intval($count / 26) - 1;
                } while ($count >= 0);


                $count = $letters;
                break;
            case 'roman_numbers':
                $count = $this->get_numbers_in_roman()[$count];
                break;
            default:
                //If not option, Its default
                break;
        }
        return $count;
    }

    /**
     * Return the image file
     *
     * @param $filearea
     * @param $file_name
     * @return string
     * @throws \dml_exception
     */
    public function get_content_file($filearea, $file_name)
    {
        global $DB;

        $file_name = substr($file_name, 1);

        $file_verified = $DB->get_record('files', array(
            'contextid' => 1,
            'component' => 'format_buttons',
            'filearea' => $filearea,
            'filepath' => '/',
            'filename' => $file_name
        ));

        $fs = get_file_storage();

        $fileinfo = $file_verified;

        $file = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea,
            $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);


        if ($file) {
            $image_content = $file->get_content();

            $image_base64 = base64_encode($image_content);
            $mime_type = $file->get_mimetype();
            $image_src = 'data:' . $mime_type . ';base64,' . $image_base64;

            return $image_src;
        } else {
            return "";
        }
    }

    /**
     * Save the requested sections
     *
     * @param $section
     * @return void
     */
    public function save_last_section_access($section)
    {
        global $USER;
        $cache = cache::make('format_buttons', 'user_last_section');
        $cache->set($USER->id . '_' . $this->format->get_course()->id, $section);
    }

    /**
     * Return the requested sections
     *
     * @return array|bool|float|int|mixed|\stdClass|string
     * @throws \coding_exception
     */
    public function get_last_section_access()
    {
        global $USER;

        $cache = cache::make('format_buttons', 'user_last_section');
        return $cache->get($USER->id . '_' . $this->format->get_course()->id);
    }


}