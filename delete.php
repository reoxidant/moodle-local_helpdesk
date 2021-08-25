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

require('../../config.php');
require_once($CFG -> dirroot . '/local/helpdesk/lib.php');
require_once($CFG -> dirroot . '/local/helpdesk/locallib.php');

// Get and check parameters

$categoryids = required_param('categories', PARAM_SEQUENCE);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$screen = helpdesk_resolve_screen();
$view = helpdesk_resolve_view();

require_login();
$context = context_system ::instance();
require_capability('local/helpdesk:manage', $context);

$pluginname = get_string('pluginname', 'local_helpdesk');
$url = new moodle_url('/local/helpdesk/delete.php');

$PAGE -> set_context($context);

$PAGE -> navbar -> add($pluginname);
$PAGE -> set_title($pluginname);
$PAGE -> set_heading($pluginname);

$PAGE -> set_url($url);
$PAGE -> set_pagelayout('standard');

// Make sure all categories are OK

$categoryidarray = explode(',', $categoryids);
$categorynames = [];

foreach ($categoryidarray as $categoryid) {
    $category = $DB -> get_record('helpdesk_categories', ['id' => $categoryid]);

    if (!$category) {
        print_error('invalidcategoryid');
    }
    $categorynames[] = format_string($category -> name);
}

$returnurl = new moodle_url('/local/helpdesk/view.php', compact('view', 'screen'));

if (count($categoryidarray) == 0) {
    print_error('errorselectsome', 'local/helpdesk', $returnurl);
}

if ($confirm && data_submitted()) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error', $returnurl);
    }

    foreach ($categoryidarray as $categoryid) {
        helpdesk_delete_category($categoryid);
    }

    redirect($returnurl);
} else {
    $PAGE -> set_title(get_string('deleteselectedcategory', 'local_helpdesk'));
    $PAGE -> set_heading($pluginname. ': ' .get_string('deleteselectedcategory', 'local_helpdesk'));
    echo $OUTPUT -> header();
    $options = ['categories' => $categoryids, 'sesskey' => sesskey(), 'confirm' => 1];
    if (count($categorynames) == 1) {
        $message = get_string('deletecategoryconfirm', 'local_helpdesk', $categorynames[0]);
    } else {
        $message = get_string('deletecategoryconfirm', 'local_helpdesk') . '<ul>';
        foreach ($categorynames as $categoryname) {
            $message .= '<li>' . $categoryname . '</li>';
        }
        $message .= '</ul>';
    }
    $formcontinue = new single_button(new moodle_url('/local/helpdesk/delete.php', $options), get_string('yes'), 'post');
    $formcancel = new single_button($returnurl, get_string('no'), 'get');
    echo $OUTPUT -> confirm($message, $formcontinue, $formcancel);
    echo $OUTPUT -> footer();
}