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

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once($CFG -> dirroot . '/local/helpdesk/lib.php');
require_once($CFG -> dirroot . '/local/helpdesk/locallib.php');

$PAGE -> set_context(context_system ::instance());
$PAGE -> set_url('/helpdesk/searchmembers.php');

echo $OUTPUT -> header();

// Check access.
require_login();
require_sesskey();

// Get the search parameter.
$search = required_param('search', PARAM_RAW);

// Do the search and output the results.

$context = context_system ::instance();

$members = helpdesk_getresolvers($context);

$ids = [];
foreach ($members as $member) {
    $ids += $member->id;
}
$results = $DB->get_record_sql('SELECT * FROM {user} 
                                    WHERE id IN (' .implode(',', $ids).") 
                                    AND CONCAT(firstname, ' ', lastname, ' ', email) LIKE '%$search%')");

echo json_encode(['results' => $results]);