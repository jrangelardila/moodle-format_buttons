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
 * Native <input type="color"> element for format_buttons forms.
 *
 * @package     format_buttons
 * @copyright   2023 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/text.php');

/**
 * Renders a native browser colour picker paired with a hex text display.
 *
 * The colour swatch (<input type="color">) is the real form field.
 * A companion read-only text span shows the current hex value and
 * the two are kept in sync via inline JS.  No mustache template
 * exists for type "color", so the renderer calls toHtml() directly.
 */
class format_buttons_colourpicker extends MoodleQuickForm_text {

    /**
     * @param string|null $elementname
     * @param string|null $elementlabel
     * @param array|string $attributes
     */
    public function __construct($elementname = null, $elementlabel = null, $attributes = null) {
        parent::__construct($elementname, $elementlabel, $attributes);
        $this->setType('color');
    }

    /**
     * Returning 'color' ensures there is no matching mustache template,
     * so the renderer falls back to toHtml().
     */
    public function getType(): string {
        return 'color';
    }

    /**
     * Render a colour swatch + hex label pair inside a small flex container.
     *
     * The <input type="color"> carries the element name and is submitted with
     * the form.  The companion <span> is updated on every swatch change so the
     * current hex value is always visible without any extra UI.
     */
    public function toHtml(): string {
        $id    = htmlspecialchars($this->_attributes['id']   ?? ('id_' . $this->getName()), ENT_QUOTES);
        $name  = htmlspecialchars($this->getName(), ENT_QUOTES);
        $value = htmlspecialchars((string) ($this->getValue() ?? '#ffffff'), ENT_QUOTES);
        $spanid = $id . '_hex';

        return '
<div class="d-inline-flex align-items-center gap-2">
    <input type="color"
           id="' . $id . '"
           name="' . $name . '"
           value="' . $value . '"
           style="width:44px; height:36px; padding:2px; cursor:pointer;
                  border:1px solid #ced4da; border-radius:.25rem; flex-shrink:0;"
           oninput="var s=document.getElementById(\'' . $spanid . '\');
                    if(s) s.textContent=this.value;">
    <span id="' . $spanid . '"
          style="font-family:monospace; font-size:.9rem; color:#495057;
                 background:#f8f9fa; border:1px solid #ced4da;
                 border-radius:.25rem; padding:4px 8px; min-width:80px;
                 text-align:center; user-select:all;">' . $value . '</span>
</div>';
    }
}
