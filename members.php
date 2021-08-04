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
 * $members file description here.
 *
 * @package    $members
 * @copyright  2021 SysBind Ltd. <service@sysbind.co.il>
 * @auther     vshapovalov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$categoryid = required_param('category', PARAM_INT);
$cancel = optional_param('cancel', false, PARAM_BOOL);

$screen = helpdesk_resolve_screen();
$view = helpdesk_resolve_view();

$context = context_system ::instance();

require_login();
require_capability('local/helpdesk:manage', $context);

$pluginname = get_string('pluginname', 'local_helpdesk');

$url = new moodle_url('/local/helpdesk/addcategory.php');


$PAGE -> set_url($url);
$PAGE -> set_context($context);
$PAGE -> set_pagelayout('standard');

$PAGE -> requires -> js('/local/helpdesk/js/helpdeskview.js');
$PAGE -> navbar -> add($pluginname);

// Print header

$PAGE -> set_title($pluginname);
$PAGE -> set_heading($pluginname);

echo $OUTPUT->header();

$category = $DB -> get_record('helpdesk_category', ['id' => $categoryid], '*', MUST_EXIST);
$category = $category -> id;
$categoryname = format_string($category -> name);

if ($cancel) {
    redirect(new moodle_url('/local/helpdesk/view.php', compact('view', 'screen', 'category')));
}

?>

<div id="addmembersform">
    <form id="assignform" method="post" action="<?= $CFG->wwwroot ?>/local/helpdesk/members.php?category=<?= $categoryid ?>">
        <div>
            <input type="hidden" name="sesskey" value="<?= p(sesskey()) ?>">
            <table class="generaltable generalbox categorymanagementtable boxaligncenter">
                <tr>
                    <td id="existingcell">
                        <p>
                            <label for="removeselect"><?php print_string('categorymembers', 'local_helpdesk')?></label>
                        </p>

                    </td>
                </tr>
            </table>
        </div>
    </form>
</div>


