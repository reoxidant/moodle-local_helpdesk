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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

$OUTPUT -> box_start('generalbox', 'bugreport');
?>
<div style="text-align: center;">

    <!-- Print Bug Form -->
    <form name="byidform" action="/local/helpdesk/view.php" method="get" class="mform">
        <input type="hidden" name="action" value="searchforissues"/>
        <input type="hidden" name="screen" value="browse"/>
        <input type="hidden" name="view" value="view"/>
        <fieldset>
            <legend class="ftoggler"><?= get_string('searchbyid', 'local_helpdesk') ?></legend>
            <table style="padding: 5px; max-width: 500px; display: inline;">
                <tr>
                    <td style="text-align: right; width: 150px; padding: 5px">
                        <b><?php print_string('issuenumber', 'local_helpdesk') ?>:</b></td>
                    <td style="text-align: left; width:200px">
                        <label>
                            <input type="text" name="issuenumber" value="" size="5"/>
                        </label>
                    </td>
                    <td style="width:200px">&nbsp;</td>
                    <td style="width:200px">
                        <input type="submit" name="search" class="btn btn-secondary"
                               value="<?php print_string('search', 'local_helpdesk') ?>"/>
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
    <?php
    $OUTPUT -> box_end();
    $OUTPUT -> box_start('generalbox', 'bugreport');
    ?>
    <form name="searchform" action="/local/helpdesk/view.php" method="get" class="mform">
        <input type="hidden" name="action" value="searchforissues"/>
        <input type="hidden" name="screen" value="browse"/>
        <input type="hidden" name="view" value="view"/>

        <fieldset>
            <legend class="ftoggler"><?= get_string('searchcriteria', 'local_helpdesk') ?></legend>
            <div style="height: 10px"></div>
            <table style="padding: 5px; max-width: 800px; display: inline;">
                <tr>
                    <td style="text-align: right; vertical-align: top; width: 130px; padding: 5px">
                        <b><?php print_string('assignedto', 'local_helpdesk') ?>:</b><br/></td>
                    <td style="width: 180px; vertical-align: top; text-align: left">
                        <?php

                        $assignees = helpdesk_getassignees();

                        $assigneesmenu = [];
                        if ($assignees) {
                            foreach ($assignees as $assignee) {
                                $assigneesmenu[$assignee -> id] = fullname($assignee);
                            }
                            echo html_writer ::select($assigneesmenu, 'assignedto', '', array('' => get_string('any', 'local_helpdesk')));
                        } else {
                            print_string('noresolvers', 'local_helpdesk');
                        }
                        ?>
                    </td>
                    <td style="text-align: right; width: 50px;"></td>
                    <td style="width: 75px; vertical-align: top; text-align: left"></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td style="vertical-align: top; text-align: right; padding: 5px">
                        <b><?php print_string('summary', 'local_helpdesk') ?>:</b><br/></td>
                    <td colspan="3" style="vertical-align: top; text-align: left">
                        <label>
                            <input type="text" name="summary" size="30" value="" maxlength="100"/>
                        </label>
                    </td>
                </tr>

                <tr style="vertical-align: top">
                    <td style="text-align: center" colspan="4">
                        <input type="submit" name="search" class="btn btn-secondary"
                               value="<?php print_string('search', 'local_helpdesk') ?>"/>
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>

<?php
$OUTPUT -> box_end();
?>

