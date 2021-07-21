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
 * $assignmanagers file description here.
 *
 * @package    $assignmanagers
 * @copyright  2021 SysBind Ltd. <service@sysbind.co.il>
 * @auther     vshapovalov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

$action = helpdesk_categories_param_action();

?>
    <form id="categoryeditform" action="index.php" method="post">
        <div>
            <table style="padding: 6px" class="generaltable generalbox categorymanagementtable boxaligncenter">
                <tr>
                    <td>
                        <p>
                            <label for="categories">
                                <span id="categorieslabel">
                                    <?= get_string('categories', 'local_helpdesk') ?>
                                </span>
                                <span id="thecategorizing">&nbsp;</span>
                            </label>
                        </p>
                        <select name="categories[]" multiple="multiple" id="categories" size="15"
                                class="form-control input-block-level"
                                onchange="M.core_group.membersCombo.refreshMembers()">

                            <?php

                            $categories = [];
                            $selectedname = "&nbsp;";

                            if ($categories) {
                                //    foreach ($categories as $category) {
                                //        echo '<option value="" title="">' . $category . '</option>';
                                //    }
                                echo '<option>empty option</option>\n';
                            } else {
                                echo '<option>&nbsp;</option>';
                            }

                            ?>

                        </select>
                        <p>
                            <input type="submit" name="action_updatemembers" id="updatemembers"
                                   value="<?= get_string('showmembersforcategory', 'local_helpdesk')?>
                            ">
                        </p>
                        <p>
                            <input type="submit" name="action_showcategorysettingsform" id="showeditcategorysettingsform"
                                   value="<?=get_string('editcategorysettings', 'local_helpdesk')?>"
                            >
                        </p>
                        <p>
                            <input type="submit" name="action_deletecategory" id="deletecategory"
                                   value="<?=get_string('deleteselectedcategory', 'local_helpdesk')?>"
                            >
                        </p>
                        <p>
                            <input type="submit" name="action_showcreateorphancategoryform" id="showcreateorphancategoryform"
                                   value="<?=get_string('createcategory', 'local_helpdesk')?>"
                            >
                        </p>
                        <p>
                            <input type="submit" name="action_showautocreatecategoriesform" id="showautocreatecategoriesform"
                                   value="<?=get_string('autocreatecategories','local_helpdesk')?>"
                            >
                        </p>
                        <p>
                            <input type="submit" name="action_showimportcategories" id="action_showimportcategories"
                                   value="<?=get_string('importcategories', 'local_helpdesk')?>"
                            >
                        </p>
                    </td>
                </tr>
                <p>
                    <label for="members">
                        <span id="memberslabel" value="<?=get_string('membersofselectedcategory', 'local_helpdesk')?>"</span>
                        <span id="thecategory"><?=$selectedname?></span>
                    </label>
                </p>
                <select name="user" id="members" size="15" class="select" onclick="window.status=this.options[this.selectedIndex].title;" onmouseout="window.status=''"></select>
            </table>
        </div>
    </form>


