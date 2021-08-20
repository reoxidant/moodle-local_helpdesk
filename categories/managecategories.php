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
 * $managecategories file description here.
 *
 * @package    $managecategories
 * @copyright  2021 SysBind Ltd. <service@sysbind.co.il>
 * @auther     vshapovalov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Check for multiple / no group errors

$disabled = 'disabled="disabled"';

// Some buttons are enabled if single category selected.

$showaddmembersform_disabled = $singlecategory ? '' : $disabled;
$showeditcategorysettingsform_disabled = $singlecategory ? '' : $disabled;
$deletecategory_disabled = count($categoryids) > 0 ? '' : $disabled;

?>
    <form id="categoryeditform" action="/local/helpdesk/view.php" method="post">
        <div>
            <table style="padding: 6px" class="generaltable generalbox categorymanagementtable boxaligncenter">
                <tr>
                    <td>
                        <p>
                            <label for="categories">
                                <span id="categorieslabel">
                                    <?= get_string('categories', 'local_helpdesk') ?>:
                                </span>
                                <span id="thecategorizing">&nbsp;</span>
                            </label>
                        </p>
                        <p>
                            <select name="categories[]" multiple="multiple" id="categories" size="15"
                                    class="select"
                                    onchange="helpdesk_categories.membersCategory.refreshMembers()">

                                <?php
                                $categories = helpdesk_get_all_categories();
                                $selectedname = '&nbsp;';

                                if ($categories) {
                                    // Print out the HTML
                                    foreach ($categories as $category) {
                                        $select = '';
                                        $usercount = $DB->count_records('helpdesk_categories_members', ['categoryid' => $category->id]);
                                        $categoryname = format_string($category -> name) . ' (' . $usercount . ')';
                                        if (in_array($category -> id, $categoryids)) {
                                            $select = ' selected="selected"';
                                            if ($singlecategory) {
                                                // Only keep selected name if there is one group selected
                                                $selectedname = $categoryname;
                                            }
                                        }

                                        echo '<option value="' . $category -> id . '" ' . $select . ' 
                                        title="' . $categoryname . '" >' . $categoryname . '</option>\n';
                                    }

                                } else {
                                    // Print an empty option to avoid the XHTML error of having an empty select element
                                    echo '<option>&nbsp;</option>';
                                }
                                ?>

                            </select>
                        </p>
                        <p>
                            <input type="submit" <?= $showeditcategorysettingsform_disabled ?>
                                   name="action_showcategorysettingsform"
                                   id="showeditcategorysettingsform" class="btn btn-secondary"
                                   value="<?= get_string('editcategorysettings', 'local_helpdesk') ?>"
                            >
                        </p>
                        <p>
                            <input type="submit" <?= $deletecategory_disabled ?>
                                   name="action_deletecategory" id="deletecategory" class="btn btn-secondary"
                                   value="<?= get_string('deleteselectedcategory', 'local_helpdesk') ?>"
                            >
                        </p>
                        <p>
                            <input type="submit" name="action_showcreateorphancategoryform"
                                   id="showcreateorphancategoryform" class="btn btn-secondary"
                                   value="<?= get_string('createcategory', 'local_helpdesk') ?>"
                            >
                        </p>
                    </td>
                    <td>
                        <p>
                            <label for="members">
                            <span id="memberslabel">
                                <?= get_string('membersofselectedcategory', 'local_helpdesk') ?>:
                            </span>
                                <span id="thecategory"><?= $selectedname ?></span>
                            </label>
                        </p>
                        <p>
                            <select name="user" id="members" size="15" class="select"
                                    onclick="window.status=this.options[this.selectedIndex].title;"
                                    onmouseout="window.status='';">

                                <?php
                                $member_names = [];

                                $atleastonemember = false;

                                if ($singlecategory) {
                                    $categorymembers = helpdesk_get_members_category($categoryids[0]);
                                    foreach ($categorymembers as $member) {
                                        echo '<option value="' . $member -> id . '">' . fullname($member, true) . '</option>';
                                        $atleastonemember = true;
                                    }
                                }

                                if (!$atleastonemember) {
                                    echo '<option>&nbsp;</option>';
                                }

                                ?>
                            </select>
                        </p>
                        <p>
                            <input type="submit" <?= $showaddmembersform_disabled ?> name="action_showaddmembersform"
                                   id="showaddmembersform"
                                   class="btn btn-secondary"
                                   value="<?= get_string('adduserstocategory', 'local_helpdesk') ?>">
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </form>
<?php

$PAGE -> requires -> js_init_call('helpdesk_categories.init', [$CFG->wwwroot]);