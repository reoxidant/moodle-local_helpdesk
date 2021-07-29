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
 * $addcomment file description here.
 *
 * @package    $addcomment
 * @copyright  2021 SysBind Ltd. <service@sysbind.co.il>
 * @auther     vshapovalov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG -> dirroot . '/local/helpdesk/lib.php');
require_once($CFG -> dirroot . '/local/helpdesk/locallib.php');
require_once($CFG -> dirroot . '/local/helpdesk/forms/addcomment_form.php');

$issueid = required_param('issueid', PARAM_INT);

$context = context_system ::instance();

require_login();
require_capability('local/helpdesk:comment', $context);

$pluginname = get_string('pluginname', 'local_helpdesk');

$issue = $DB -> get_record('helpdesk_issue', ['id' => $issueid]);

if (!$issue) {
    print_error('errorbadissueid', 'local_helpdesk');
}

// Setting page.

$url = new moodle_url('/local/helpdesk/addcomment.php', ['issueid' => $issueid]);
$PAGE -> set_url($url);
$PAGE -> set_context($context);
$PAGE -> set_pagelayout('standard');
$PAGE -> navbar -> add($pluginname);
$PAGE -> set_title($pluginname);
$PAGE -> set_heading($pluginname);

$form = new addcomment_form($url);

if ($form -> is_cancelled()) {
    redirect(new moodle_url('/local/helpdesk/view.php', ['view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issueid]));
} else {
    $data = $form -> get_data();

    if ($data) {
        $comment = new StdClass();
        $comment -> comment = $data -> comment_editor['text'];
        $comment -> commentformat = $data -> comment_editor['format'];
        $comment -> userid = $USER -> id;
        $comment -> issueid = $issueid;
        $comment -> datecreated = time();
        $comment -> id = $DB -> insert_record('helpdesk_issuecomment', $comment);
        if (!$comment -> id) {
            print_error('errorcannotwritecomment', 'local_helpdesk');
        }

        // Stores files.
        $data = file_postupdate_standard_editor(
            $data,
            'comment',
            $form -> editoroptions,
            $context,
            'local_helpdesk',
            'issuecomment',
            $comment -> id
        );

        // Update back re-encoded field text content.
        $DB -> set_field('helpdesk_issuecomment', 'comment', $data -> comment, ['id' => $comment -> id]);
        redirect(new moodle_url('/local/helpdesk/view.php', ['view' => 'view', 'screen' => 'viewanissue', 'issueid' => $issueid]));
    }
}

echo $OUTPUT -> header();

echo $OUTPUT -> heading($issue -> summary);

$description = file_rewrite_pluginfile_urls($issue -> description, 'pluginfile.php', $context -> id, 'local_helpdesk', 'issuedescription', $issue -> id);

echo $OUTPUT -> box(format_text($description, $issue -> descriptionformat), 'helpdesk-issue-description');

echo $OUTPUT -> heading(get_string('addacomment', 'local_helpdesk'));

$form -> display();

echo $OUTPUT -> footer();