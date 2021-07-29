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
 * $addcategory file description here.
 *
 * @package    $addcategory
 * @copyright  2021 SysBind Ltd. <service@sysbind.co.il>
 * @auther     vshapovalov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG -> dirroot . '/local/helpdesk/locallib.php');
require_once($CFG -> dirroot . '/local/helpdesk/forms/addcategory_form.php');

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
$PAGE -> set_title($pluginname);
$PAGE -> set_heading($pluginname);

$form = new addcategory_form($url);

if ($form -> is_cancelled()) {
    redirect(new moodle_url('/local/helpdesk/view.php', compact('view', 'screen')));
}

$data = $form -> get_data();

if ($data) {
    $category = new StdClass;
    $category -> name = $data -> name;
    $category -> description = $data -> description;
    if ($data -> categoryid) {
        $category -> id = $data -> categoryid;
        $DB -> update_record('helpdesk_categories', $category);
    } else {
        $category -> id = $DB -> insert_record('helpdesk_categories', $category);
    }

    redirect(new moodle_url('/local/helpdesk/view.php'), compact('view', 'screen'));
}

echo $OUTPUT-> header();

$view = 'categories';
$screen = 'addcategory';
include_once($CFG -> dirroot . '/local/helpdesk/menus.php');

$form -> display();

echo $OUTPUT -> footer();