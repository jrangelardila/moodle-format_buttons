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

use cache;
use context_course;
use context_system;
use core_courseformat\output\local\content as content_base;
use course_modinfo;
use Matrix\Exception;
use moodle_url;
use stdClass;
use TypeError;

class content extends content_base
{
    var $currentsection;

    /**
     * Nombre de la plantilla
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string
    {
        return 'format_buttons/local/content';
    }

    /**
     * Retornar la plantilla
     *
     * @param \renderer_base $output
     * @return \stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(\renderer_base $output): \stdClass
    {
        global $DB;
        $format = $this->format;
        $course = $format->get_course();

        if (sizeof($format->get_sections()) == 1) {
            redirect(new moodle_url('/course/changenumsections.php',
                [
                    'courseid' => $course->id,
                    'insertsection' => 0,
                    'sesskey' => sesskey(),
                ]));
        }

        $array_sections = array();

        $all_sections = $DB->get_records('course_sections', array('course' => $course->id), "section");
        $section_prev = self::get_last_section_access($course->id);
        foreach ($all_sections as $section) {
            $info = new \stdClass();
            if ($section->section != 0 && !$course->section_zero_ubication) {
                $info->body = true;
            }
            if ($course->section_zero_ubication) {
                $info->body = true;
            }
            $url = new moodle_url("/course/view.php", array(
                'id' => $course->id,
                'expandsection' => $section->section
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

        $section_select = self::get_param_for_url(
            ['expandsection' => null,
                'section' => null,
            ]);

        if (sizeof($all_sections) - 1 < ($section_select)) {
            $url = new moodle_url("/course/view.php", array('id' => $course->id, 'expandsection' => 0));
            self::save_last_section_access($course->id, null);
            redirect($url);
        }

        if (sizeof($all_sections) - 1 < ($section_prev)) {
            $url = new moodle_url("/course/view.php", array('id' => $course->id, 'expandsection' => 0));
            self::save_last_section_access($course->id, null);
            redirect($url);
        }

        if ($section_select != "") {
            if ($section_select == "0" && !$course->section_zero_ubication) {
                if (isset($array_sections[1])) {
                    $array_sections[1]->selected = true;
                    self::save_last_section_access($course->id, 1);
                }
            } else {
                $array_sections[$section_select]->selected = true;
                self::save_last_section_access($course->id, $section_select);
            }
        } else {
            $section = self::get_last_section_access($course->id);
            if ($section && isset($array_sections[$section])) {
                $array_sections[$section]->selected = true;
            }
        }

        $sections = $this->export_sections($output);
        if (!$course->section_zero_ubication) {
            $sections[0]->section = 1;
        }

        switch ($course->selectoption) {
            case "number";
                //Si es number se deja por defecto
                break;
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
                //Si no hay opción se deja por defecto
                break;
        }

        $array_sections = $this->agruping_sections($array_sections, $course);

        switch ($course->selectform) {
            case 'rounded':
                $form_btn = "50%";
                break;
            default:
                $form_btn = "0%";
                break;
        }

        $course->bgcolor = $course->bgcolor != "" ? $course->bgcolor : get_config('format_buttons', 'bgcolor');
        $course->colorfont = $course->colorfont != "" ? $course->colorfont : get_config('format_buttons', 'fontcolor');
        $course->bgcolor_selected = $course->bgcolor_selected != "" ? $course->bgcolor_selected : get_config('format_buttons', 'bgcolor_selected');
        $course->fontcolor_selected = $course->fontcolor_selected != "" ? $course->fontcolor_selected : get_config('format_buttons', 'fontcolor_selected');;

        $data = (object)[
            'title' => $format->page_title(),
            'sections' => $sections,
            'all_sections' => $array_sections,
            'format' => $format->get_format(),
            'sectionclasses' => '',
            'bgcolor' => $course->bgcolor,
            'colorfont' => $course->colorfont,
            'bgcolor_selected' => $course->bgcolor_selected,
            'fontcolor_selected' => $course->fontcolor_selected,
            'form_btn' => $form_btn,
            'sectionreturn' => "null"
        ];

        if ($format->show_editor()) {
            $bulkedittools = new $this->bulkedittoolsclass($format);
            $data->bulkedittools = $bulkedittools->export_for_template($output);
        }

        $sectionnavigation = new $this->sectionnavigationclass($format, $this->currentsection);

        $data->sectionnavigation = $sectionnavigation->export_for_template($output);

        $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
        try {
            $data->sectionselector = $sectionselector->export_for_template($output);
        } catch (TypeError $e) {
            $this->currentsection = 0;
            $sectionnavigation = new $this->sectionnavigationclass($format, $this->currentsection);

            $data->sectionnavigation = $sectionnavigation->export_for_template($output);

            $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
            $data->sectionselector = $sectionselector->export_for_template($output);
        }

        $url = new moodle_url("/course/changenumsections.php",
            array('courseid' => $course->id, 'insertsection' => 0, 'sesskey' => sesskey()));
        $data->url_add_section = $url;

        $file_setting = get_config('format_buttons', 'image_sections');
        if ($file_setting != "") {

            $url_1 = $this->get_content_file('format_buttons_file', get_config('format_buttons', 'image_sections'));

            $data->image_init_sectios = $url_1;
        }

        return $data;
    }

    /**
     * Agrupar las secciones, de acuerdo a la configuración
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
                    //echo "total: " . $total_count_group . " count: " . $count . "<br>";
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
     * Retornar el namesection, para  una sección, solo funciona al formar los grupos
     * @param $count
     * @param $course
     * @return mixed|string
     */
    public function get_namesection_for_btn($count, $course)
    {
        switch ($course->selectoption) {
            case "number";
                //Si es number se deja por defecto
                break;
            case 'leter_lowercase':
                $count--; //Se debe restar uno
                do {
                    $letters = chr(($count % 26) + ord('a')) . $letters;
                    $count = intval($count / 26) - 1;
                } while ($count >= 0);


                $count = $letters;
                break;
            case 'leter_uppercase':
                $count--; //Se debe restar uno
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
                //Si no hay opción se deja por defecto
                break;
        }
        return $count;
    }

    /**
     * Exportar las secciones, de acuerdo a la necesidad
     *
     * @param \renderer_base $output
     * @return array
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function export_sections(\renderer_base $output): array
    {
        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $this->format->get_modinfo();


        $realcoursedisplay = property_exists($course, 'realcoursedisplay') ? $course->realcoursedisplay : false;
        $firstsectionastab = ($realcoursedisplay == COURSE_DISPLAY_MULTIPAGE) ? 1 : 0;

        // Generate section list.
        $sections = [];
        $stealthsections = [];
        $numsections = $format->get_last_section_number();

        //Sección solicitada
        $section_select = self::get_param_for_url(
            array('expandsection' => null,
                'section' => null,
            ));

        if (!$section_select) {
            $last = self::get_last_section_access($course->id);
            if ($last) {
                $this->currentsection = $last;
                $section_select = $last;
            } else {
                $this->currentsection = $course->section_zero_ubication ? 0 : 1;
            }
        } else {
            $this->currentsection = $section_select;
        }

        foreach ($this->get_sections_to_display($modinfo) as $thissection) {
            // The course/view.php check the section existence but the output can be called
            // from other parts so we need to check it.
            if (!$thissection) {
                throw new \moodle_exception('unknowncoursesection', 'error', course_get_url($course), s($course->fullname));
            }

            $section = new $this->sectionclass($format, $thissection);
            $sectionnum = $section->get_section_number();

            if ($course->section_zero_ubication) {
                if ($this->currentsection != $sectionnum) {
                    continue;
                }

            } else {
                if (!$this->currentsection) {
                    if ($sectionnum > 1) {
                        continue;
                    }
                } else {
                    if ($sectionnum != 0 and $sectionnum != $section_select) {
                        continue;
                    }
                }
                if ($sectionnum === 0 && $firstsectionastab) {
                    continue;
                }
            }

            if ($sectionnum > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                if (!empty($modinfo->sections[$sectionnum])) {
                    $stealthsections[] = $section->export_for_template($output);
                }
                continue;
            }

            if (!$format->is_section_visible($thissection)) {
                continue;
            }


            $sections[] = $section->export_for_template($output);
        }

        if (!empty($stealthsections)) {
            $sections = array_merge($sections, $stealthsections);
        }

        return $sections;
    }

    /**
     * Retornar los parametros de la url
     *
     * @param $params_need
     * @return mixed|string
     * @throws \coding_exception
     * @throws \core\exception\coding_exception
     * @throws \core\exception\moodle_exception
     * @throws \dml_exception
     */
    static function get_param_for_url($params_need)
    {
        global $PAGE, $DB;

        $section_select = null;

        foreach ($params_need as $param => $value) {
            $param_value = optional_param($param, null, PARAM_INT);
            if ($param_value != "") {
                $section_select = $param_value;
                break;
            }
        }
        $id = optional_param('id', null, PARAM_INT);
        if ($PAGE->url->out_as_local_url(false) === '/course/section.php?id=' . $id) {
            $section_select = $DB->get_record('course_sections', ['id' => $id], '*', MUST_EXIST)->section;
        }
        return $section_select;
    }

    /**
     * Guardar la ultima sección a que se accedio
     * @param $courseid
     * @param $section
     * @return void
     */
    static function save_last_section_access($courseid, $section)
    {
        global $USER;
        $cache = cache::make('format_buttons', 'user_last_section');
        $cache->set($USER->id . '_' . $courseid, $section);
    }

    /**
     * Retornar la última sección del usuario
     * @param $courseid
     * @return array|bool|float|int|mixed|\stdClass|string
     * @throws \coding_exception
     */
    static function get_last_section_access($courseid)
    {
        global $USER;

        $cache = cache::make('format_buttons', 'user_last_section');
        $section = $cache->get($USER->id . '_' . $courseid);
        if (!$section) {
            $section = 1;
        }
        return $section;
    }

    /**
     * Reescribiendo la función
     *
     * @param course_modinfo $modinfo
     * @return array|\core_courseformat\output\local\section_info[]
     * @throws \moodle_exception
     */
    protected function get_sections_to_display(\course_modinfo $modinfo): array
    {
        if (method_exists($this->format, 'get_sectionnum')) {
            // For Moodle 4.4 and above.
            $singlesection = $this->format->get_sectionnum() ?? 0;
        } else {
            // Backward compatibility for Moodle 4.3 and below.
            $singlesection = $this->format->get_section_number();
        }

        if ($singlesection) {
            return [
                $modinfo->get_section_info(0),
                $modinfo->get_section_info($singlesection),
            ];
        }

        return $modinfo->get_section_info_all();
    }

    /**
     * Retornar las secciones, cuando se seleecione la opción de letras mayusculas
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
     * Retornar letras en minuscula, segun se requiera
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
     * Retornar las secciones, cuando se seleecione la opción de letras mayusculas
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
     * Convertir el parametro a letra segun se requiera
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
     * Array con los números romanos
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
     * Retornar cuando se indique la opción de números romanos
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
     * Retornar la imagen
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
}