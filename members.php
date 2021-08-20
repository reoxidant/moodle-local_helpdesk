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
require_once($CFG -> dirroot . '/local/helpdesk/lib.php');
require_once($CFG -> dirroot . '/local/helpdesk/locallib.php');

$PAGE -> requires -> js(new moodle_url('/local/helpdesk/js/helpdeskview.js'), true);

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
$PAGE -> navbar -> add($pluginname);

// Print header

$PAGE -> set_title($pluginname);
$PAGE -> set_heading($pluginname);

$category = $DB -> get_record('helpdesk_categories', ['id' => $categoryid], '*', MUST_EXIST);
$categoryname = format_string($category -> name);
$category = $category -> id;

if ($cancel) {
    redirect(new moodle_url('/local/helpdesk/view.php', compact('view', 'screen', 'category')));
}

echo $OUTPUT -> header();
echo $OUTPUT -> heading(get_string('adduserstocategory', 'local_helpdesk') . ": $categoryname", 3);

if(optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userid = optional_param_array('addselect', null, PARAM_INT)[0];
    helpdesk_add_member_category($categoryid, $userid);
}

if(optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userid = optional_param_array('removeselect', null, PARAM_INT)[0];
    helpdesk_remove_member_category($categoryid, $userid);
}

?>
<div id="addmembersform">
    <form id="assignform" method="post"
          action="<?= $CFG -> wwwroot ?>/local/helpdesk/members.php?category=<?= $categoryid ?>">
        <div>
            <input type="hidden" name="sesskey" value="<?= p(sesskey()) ?>">
            <table class="generaltable generalbox groupmanagementtable boxaligncenter">
                <tr>
                    <td id="existingcell">
                        <p>
                            <label for="removeselect"><?php print_string('categorymembers', 'local_helpdesk') ?></label>
                        </p>
                        <!-- start display members -->
                        <div class="userselector" id="removeselect_wrapper">
                            <select name="removeselect[]" id="removeselect" multiple="multiple" size="20">
                                <?php $categorymembers = helpdesk_get_members_category($categoryid);
                                if (empty($categorymembers)) { ?>
                                    <optgroup label="Пусто">
                                        <option disabled="disabled">&nbsp;</option>
                                    </optgroup>
                                <?php } else { ?>
                                <optgroup label="Управляющий (<?= count($categorymembers) ?>)">
                                    <?php foreach ($categorymembers as $member) { ?>
                                        <option value="<?= $member->id ?>">
                                            <?= $member->firstname . ' ' . $member->lastname . ' (' . $member->email . ')' ?>
                                        </option>
                                    <?php } ?>
                                </optgroup>
                                <?php } ?>
                            </select>
                            <div>
                                <label for="removeselect_searchtext">Найти</label>
                                <input type="text" name="removeselect_searchtext" id="removeselect_searchtext" size="15"
                                       value="">
                                <input class="btn btn-secondary mx-1" type="button" value="Очистить"
                                       id="removeselect_clearbutton">
                            </div>
                        </div>
                        <!-- end display members -->
                    </td>
                    <td id="buttonscell">
                        <p class="arrow_button">
                            <input
                                    class="btn btn-secondary"
                                    name="add" id="add" type="submit"
                                    value="<?= $OUTPUT -> larrow() . '&nbsp' . get_string('add') ?>"
                                    title="<?php print_string('add'); ?>">
                            <br>
                            <input
                                    class="btn btn-secondary"
                                    name="remove" id="remove" type="submit"
                                    value="<?= get_string('remove') . '&nbsp;' . $OUTPUT -> rarrow() ?>"
                                    title="<?php print_string('remove') ?>">
                        </p>
                    </td>
                    <td id="potentialcell">
                        <p>
                            <label for="addselect"><?php print_string('potentialmembers', 'local_helpdesk') ?></label>
                        </p>
                        <!-- start display potential membership -->
                        <div class="userselector" id="addselect_wrapper">
                            <select name="addselect[]" id="addselect" multiple="multiple" size="20">
                                <?php
                                    $resolvers = helpdesk_getresolvers($context);
                                    $allmembers = array_udiff($resolvers, $categorymembers,
                                        static function ($first_obj, $second_obj) {
                                            return $first_obj->id - $second_obj->id;
                                        }
                                    );
                                ?>
                                <optgroup label="Пользователи (<?= count($allmembers) ?>)">
                                    <?php foreach ($allmembers as $member) { ?>
                                        <option value="<?= $member -> id ?>">
                                            <?= $member -> firstname ?> <?= $member -> lastname ?>
                                            (<?= $member -> email ?>)
                                        </option>
                                    <?php } ?>
                                </optgroup>
                            </select>
                            <div>
                                <label for="addselect_searchtext">Найти</label>
                                <input type="text" name="addselect_searchtext" id="addselect_searchtext" size="15"
                                       value="">
                                <input class="btn btn-secondary mx-1" type="button" value="Очистить"
                                       id="addselect_clearbutton">
                            </div>
                        </div>
                        <!-- end display potential membership -->
                    </td>
                </tr>
                <tr>
                    <td colspan="3" id="backcell">
                        <input class="btn btn-secondary" type="submit" name="cancel"
                               value="<?php print_string('backtocategories', 'local_helpdesk') ?>">
                    </td>
                </tr>
            </table>
        </div>
    </form>
</div>
<?php

//this must be after calling display() on the selectors so their setup JS executes first
$PAGE -> requires -> js_init_call('init_add_remove_members_page');
$PAGE -> requires -> js_init_call('search_members', [$categoryid]);

echo $OUTPUT -> footer();
?>

