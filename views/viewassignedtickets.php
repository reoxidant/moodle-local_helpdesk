<?php
/**
 * Description actions
 * @copyright 2021 vshapovalov
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package PhpStorm
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG, $DB, $USER;

require_once($CFG -> libdir . '/tablelib.php');

$FULLSTATUSKEYS = helpdesk_get_status_keys();
$STATUSKEYS = helpdesk_get_status_keys();

$limit = 20;
$page = optional_param('page', 1, PARAM_INT);

if ($page <= 0) {
    $page = 1;
}

if ($resolved) {
    $resolvedclause = ' AND
       (status = ' . RESOLVED . ')
    ';
} else {
    $resolvedclause = ' AND
        status <> ' . RESOLVED . '
    ';
}

$sql = '
        SELECT
            i.id,
            i.summary,
            i.datereported,
            i.assignedto,
            i.status,
            i.priority
        FROM
            {helpdesk_issue} i
        WHERE
            i.reportedby = ' . $USER -> id . '
            ' . $resolvedclause . '
        GROUP BY
            i.id,
            i.summary,
            i.datereported,
            i.assignedto,
            i.status,
            i.priority
    ';

$sqlcount = '
        SELECT
            COUNT(*)
        FROM
            {helpdesk_issue} i
        WHERE
            i.reportedby = ' . $USER -> id . '
            ' . $resolvedclause . '
    ';

$numrecords = $DB -> count_records_sql($sqlcount);
?>

    <form name="manageform" action="/local/helpdesk/view.php" method="post">
        <input type="hidden" name="action" value="updatelist"/>
        <input type="hidden" name="view" value="view"/>
<?php

//define table object

$priority = get_string('priority', 'local_helpdesk');
$issuenumber = get_string('issuenumber', 'local_helpdesk');
$summary = get_string('summary', 'local_helpdesk');
$datereported = get_string('datereported', 'local_helpdesk');
$assignedto = get_string('assignedto', 'local_helpdesk');
$status = get_string('status', 'local_helpdesk');
$action = '';

if (!$resolved && has_capability('local/helpdesk:viewpriority', $context)) {
    $tablecolumns = array('priority', 'id', 'summary', 'datereported', 'assignedto', 'status', 'action');
    $tableheaders = array(
        "<b>$priority</b>",
        '',
        "<b>$summary</b>",
        "<b>$datereported</b>",
        "<b>$assignedto</b>",
        "<b>$status</b>",
        "<b>$action</b>"
    );
} else {
    $tablecolumns = array('id', 'summary', 'datereported', 'assignedto', 'status', 'action');
    $tableheaders = array(
        '',
        "<b>$summary</b>",
        "<b>$datereported</b>",
        "<b>$assignedto</b>",
        "<b>$status</b>",
        "<b>$action</b>"
    );
}

$table = new flexible_table('local-helpdesk-issuelist');
$table -> define_columns($tablecolumns);
$table -> define_headers($tableheaders);

$table -> define_baseurl(new moodle_url('/local/helpdesk/view.php?view=' . $view . '&screen=' . $screen));

$table -> sortable(true, 'datereported', SORT_DESC);
$table -> collapsible(true);
$table -> initialbars(true);

$table -> set_attribute('cellspacing', '0');
$table -> set_attribute('id', 'issues');
$table -> set_attribute('class', 'listissue');
$table -> set_attribute('width', '100%');

$table -> column_class('priority', 'list_priority');
$table -> column_class('id', 'list_issuenumber');
$table -> column_class('summary', 'list_summary');
$table -> column_class('datereported', 'timelabel');
$table -> column_class('assignedto', 'list_assignedto');
$table -> column_class('status', 'list_status');
$table -> column_class('action', 'list_action');

$table -> setup();

$where = $table -> get_sql_where();
$sort = $table -> get_sql_sort();
$table -> pagesize($limit, $numrecords);

if ($sort != '') {
    $sql .= " ORDER BY $sort";
}

$issues = $DB -> get_records_sql($sql, null, $table -> get_page_start(), $table -> get_page_size());
$maxpriority = $DB -> get_field_select('helpdesk_issue', 'MAX(priority)', '');

if (!empty($issues)) {
    foreach ($issues as $issue) {

        $issuenumber = "<a href=\"view.php?view=view&amp;issueid={$issue->id}\">{$issue->id}</a>";

        $summary = "<a href=\"view.php?view=view&amp;screen=viewanissue&amp;issueid={$issue->id}\">" . format_string($issue -> summary) . '</a>';

        $datereported = date('Y/m/d H:i', $issue -> datereported);
        $user = $DB -> get_record('user', ['id' => $issue -> assignedto]);
        if (has_capability('local/helpdesk:manage', $context)) {
            $status = $FULLSTATUSKEYS[0 + $issue -> status] . '<br/>' .
                html_writer ::select($STATUSKEYS,
                    "status{$issue->id}", 0, ['' => 'choose'],
                    ['onchange' => "document.forms['manageform'].schanged{$issue->id}.value = 1;"]
                ) . "<input type=\"hidden\" name=\"schanged{$issue->id}\" value=\"0\" />";
//            $managers = helpdesk_getmanagers($context);
//
//            if (!empty($managers)) {
//                $managersmenu = [];
//                foreach ($managers as $manager) {
//                    $managersmenu[$manager -> id] = fullname($manager);
//                }
//                $assignedto =
//                    html_writer ::select($managersmenu,
//                        "assignedto{$issue->id}", $issue -> assignedto,
//                        ['' => get_string('unassigned', 'local_helpdesk')],
//                        ['onchange' => "document.forms['manageform'].changed{$issue->id}.value = 1;"]
//                    );
//            }

//            $assignedto = fullname($user);
        } else {
            $status = $FULLSTATUSKEYS[0 + $issue -> status];
//            $assignedto = fullname($user);
        }

        $assignedto = fullname($user);

        $status =
            '<div class="status_' . $STATUSCODES[$issue -> status] . '" 
                  style="width: 100%; height: 100%; text-align:center">'
            . $status .
            '</div>';

        $hassolution = $issue -> status === RESOLVED && !empty($issue -> resolution);

        $solution = ($hassolution) ?
            '<img src="' . $OUTPUT -> image_url('solution', 'helpdesk') . '" height="15" 
                  alt="' . get_string('hassolution', 'local_helpdesk') . '" />' : '';

        $actions = '';

        if (has_capability('local/helpdesk:manage', $context) ||
            has_capability('local/helpdesk:resolve', $context)) {
            $actions =
                "<a href=\"view.php?view=resolved&amp;issueid={$issue->id}&screen=editanissue\" 
                    title=\"" . get_string('update') . '" >
                           <img src="' . $OUTPUT -> image_url('t/edit', 'core') . "\" alt='edit' style='border:0'/>
                </a>";
        }

        if (has_capability('local/helpdesk:manage', $context)) {
            $actions .=
                "&nbsp;<a href=\"view.php?view=resolved&amp;issueid={$issue->id}&action=delete\" title=\"" . get_string('delete') . '" >
                    <img src="' . $OUTPUT -> image_url('t/delete', 'core') . "\" alt='delete' style='border:0'/>
                </a>";
        }

        if (!$resolved && has_capability('local/helpdesk:viewpriority', $context)) {
            $ticketpriority = ($issue -> status < RESOLVED) ? $maxpriority - $issue -> priority + 1 : '';
            $dataset = [$ticketpriority, $issuenumber, $summary . ' ' . $solution, $datereported, $assignedto, $status, $actions];
        } else {
            $dataset = [$issuenumber, $summary . ' ' . $solution, $datereported, $assignedto, $status, $actions];
        }
        $table -> add_data($dataset);
    }
    $table -> finish_html();

    echo '<div style="text-align: center;">';
    echo '<p><input type="submit" name="go_btn" value="' . get_string('savechanges') . '" /> </p>';
    echo '</div>';
} else {
    echo '<br/>';
    echo '<br/>';
    echo $OUTPUT -> notification(get_string('notickets', 'local_helpdesk'), 'box generalbox', 'notice');
}

echo '</form>';