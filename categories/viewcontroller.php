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

$categoryid = optional_param('category', false, PARAM_INT);
$userid = optional_param('user', false, PARAM_INT);
$action = helpdesk_categories_param_action();

// Support either single category = parameter, or array categories[]

if ($categoryid) {
    $categoryids = [$categoryid];
} else {
    $categoryids = optional_param_array('categories', [], PARAM_INT);
}

$singlecategory = (count($categoryids) == 1);

$returnurl = $CFG -> dirroot . '/local/helpdesk/categories/managecategories.php';

if (!$singlecategory) {
    switch ($action) {
        case 'ajax_getmembersincategory':
        case 'showcategorysettingsform':
        case 'showaddmembersform':
        case 'updatemembers':
            print_error('errorselectone', 'local_helpdesk', $returnurl);
            break;
        default:
            break;
    }
}

switch ($action) {
    case false: // OK, display form.
        break;

    case 'ajax_getmembersincategory':
        $roles = [];
        $categorymembers = helpdesk_get_members_category($categoryids[0]);
        if ($categorymembers) {
            foreach ($categorymembers as $member) {
                console_log($member);
            }
        }
        echo json_encode($categorymembers);
        die;
    case 'deletecategory':
        if (count($categoryids) == 0) {
            print_error('errorselectsome', 'local_helpdesk', $returnurl);
        }
        $categoryidlist = implode(',', $categoryids);
        redirect(new moodle_url('/local/helpdesk/delete.php', ['categories' => $categoryidlist]));
        break;
    case 'showcategorysettingsform':
    case 'showcreateorphancategoryform':
        redirect(new moodle_url('/local/helpdesk/addcategory.php'), ['id' => $categoryids[0]]);
        break;
    case 'showaddmembersform':
        redirect(new moodle_url('/local/helpdesk/members.php', ['category' => $categoryids[0]]));
        break;
    default: // Error.
        print_error('unknowaction', '', $returnurl);
        break;
}
