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
 * ${PLUGINNAME} file description here.
 *
 * @package    ${PLUGINNAME}
 * @copyright  2021 SysBind Ltd. <service@sysbind.co.il>
 * @auther     vshapovalov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG -> libdir . '/formslib.php');

/**
 * Class addcategory_form
 */
class addcategory_form extends moodleform
{
    /**
     * @throws coding_exception
     */
    protected function definition()
    {
        $mform = $this -> _form;

        $mform -> addElement('hidden', 'categoryid');
        $mform -> setType('categoryid', PARAM_INT);

        $mform -> addElement('text', 'name', get_string('name'), 'maxlength="254" size="55"');
        $mform -> setType('name', PARAM_ALPHANUM);
        $mform -> addRule('name', null, 'required', null, 'client');

        $mform -> addElement('textarea', 'description', get_string('description'), ['rows' => 5, 'cols' => 55]);

        $this -> add_action_buttons();
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array
    {
        return parent ::validation($data, $files);
    }
}