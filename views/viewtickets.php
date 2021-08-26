<?php
/**
 * Description actions
 * @copyright 2021 vshapovalov
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package PhpStorm
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from view.php in mod/tracker
}

global $CFG, $DB, $STATUSKEYS, $FULLSTATUSKEYS;

require_once($CFG -> libdir . '/tablelib.php');

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
            i.reportedby,
            i.assignedto,
            i.status,
            i.priority,
            u.firstname firstname,
            u.lastname lastname
        FROM            
            {helpdesk_issue} i
        LEFT JOIN
            {user} u
        ON
            i.reportedby = u.id
        WHERE
            i.reportedby = u.id
            ' . $resolvedclause . '        
        GROUP BY
            i.id,
            i.summary,
            i.datereported,
            i.reportedby,
            i.assignedto,
            i.status,
            i.priority,
            u.firstname,
            u.lastname
    ';

$sqlcount = '
        SELECT
            COUNT(*)
        FROM
            {helpdesk_issue} i,
            {user} u
        WHERE
            i.reportedby = u.id
            ' . $resolvedclause . '
    ';

$numrecords = $DB -> count_records_sql($sqlcount)
?>

    <form name="manageform" action="/local/helpdesk/view.php" method="post">
        <input type="hidden" name="action" value="updatelist"/>
        <input type="hidden" name="view" value="view"/>
        <input type="hidden" name="screen" value="browse"/>
<?php

//Define table object.

$priority = get_string('priority', 'local_helpdesk');
$issuenumber = get_string('issuenumber', 'local_helpdesk');
$summary = get_string('summary', 'local_helpdesk');
$datereported = get_string('datereported', 'local_helpdesk');
$reportedby = get_string('reportedby', 'local_helpdesk');
$assignedto = get_string('assignedto', 'local_helpdesk');
$status = get_string('status', 'local_helpdesk');
$action = '';

if ($resolved) {
    $tablecolumns = array('id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status', 'action');
    $tableheaders = array(
        "<b>$issuenumber</b>",
        "<b>$summary</b>",
        "<b>$datereported</b>",
        "<b>$reportedby</b>",
        "<b>$assignedto</b>",
        "<b>$status</b>",
        "<b>$action</b>"
    );
} else {
    $tablecolumns = array('priority', 'id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status', 'action');
    $tableheaders = array(
        "<b>$priority</b>",
        "<b>$issuenumber</b>",
        "<b>$summary</b>",
        "<b>$datereported</b>",
        "<b>$reportedby</b>",
        "<b>$assignedto</b>",
        "<b>$status</b>",
        "<b>$action</b>"
    );
}

$table = new flexible_table('local-helpdesk-issuelist');
$table -> define_columns($tablecolumns);
$table -> define_headers($tableheaders);

$table -> define_baseurl(new moodle_url('/local/helpdesk/view.php', compact('view', 'screen')));

$table -> sortable(true, 'priority');
$table -> collapsible(true);
$table -> initialbars(true);

$table -> set_attribute('cellspacing', '0');
$table -> set_attribute('id', 'issues');
$table -> set_attribute('class', 'issuelist');
$table -> set_attribute('width', '100%');

$table -> column_class('priority', 'list_priority');
$table -> column_class('id', 'list_issue_number');
$table -> column_class('summary', 'list_summary');
$table -> column_class('datereported', 'timelabel');
$table -> column_class('reportedby', 'list_reportedby');
$table -> column_class('assignedto', 'list_assignedto');
$table -> column_class('status', 'list_status');
$table -> column_class('action', 'list_action');

$table -> setup();

// Get extra query parameters from flexible_table behaviour.
$where = $table -> get_sql_where();
$sort = $table -> get_sql_sort();
$table -> pagesize($limit, $numrecords);

if ($sort != '') {
    $sql .= " ORDER BY $sort";
} else {
    $sql .= ' ORDER BY priority ASC';
}

$issues = $DB -> get_records_sql($sql, null, $table -> get_page_start(), $table -> get_page_size());
$maxpriority = $DB -> get_field_select('helpdesk_issue', 'MAX(priority)', '');

if (!empty($issues)) {
    foreach ($issues as $issue) {

        $issuenumber = "<a href=\"view.php?view=view&amp;issueid={$issue->id}\">{$issue->id}</a>";

        $summary = "<a href=\"view.php?view=view&amp;screen=viewanissue&amp;issueid={$issue->id}\">" . format_string($issue -> summary) . '</a>';

        $datereported = date('Y/m/d H:i', $issue -> datereported);

        $user = $DB -> get_record('user', array('id' => $issue -> reportedby));

        $reportedby = fullname($user);

        $assignedto = '';

        $user = $DB -> get_record('user', array('id' => $issue -> assignedto));

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
//                    ) . "<input type=\"hidden\" name=\"changed{$issue->id}\" value=\"0\" />";
//            }

//            $assignedto = fullname($user);
        }else{
            $status = $FULLSTATUSKEYS[0 + $issue -> status];
//            $assignedto = fullname($user);
        }

        $assignedto = fullname($user);

        $status =
            '<div class=status_' . $STATUSCODES[$issue -> status] . ' 
                  style="width: 100%; height:100%; text-align: center">' . $status .
            '</div>';

        $hassolution = $issue -> status === RESOLVED && !empty($issue -> resolution);

        $solution = ($hassolution) ?
            '<img src="' . $OUTPUT -> image_url('solution', 'local_helpdesk') . '" height="15" 
                  alt="' . get_string('hassolution', 'local_helpdesk') . '" />' : '';

        $actions = '';

        if (has_capability('local/helpdesk:manage', $context) ||
            has_capability('local/helpdesk:resolve', $context)) {
            $actions =
                "<a href=\"view.php?view=view&amp;issueid={$issue->id}&screen=editanissue\" 
                    title=\"" . get_string('update') . '" >
                    <img src ="' . $OUTPUT -> image_url('t/edit', 'core') . "\" alt='edit' style='border:0'/>
                </a>";
        }

        if (has_capability('local/helpdesk:manage', $context)) {
            $actions .=
                "<a href=\"view.php?issueid={$issue->id}&action=delete\" title=\"" . get_string('delete') . '" >
                    <img src ="' . $OUTPUT -> image_url('t/delete', 'core') . "\" alt='delete' style='border:0'/>
                </a>";
        }

        if (strncmp($sort, 'priority', 8) === 0 && has_capability('local/helpdesk:managepriority', $context)) {
            if ($issue -> priority < $maxpriority) {
                $actions .= '<a href="view.php?issueid=' . $issue -> id . '&action=raisetotop"
                                title=" ' . get_string('raisetotop', 'local_helpdesk') . ' ">
                                <img src="' . $OUTPUT -> image_url('totop', 'local_helpdesk') . '" alt="raisetotop" style="border:0"/>
                             </a>';
                $actions .= '<a href="view.php?issueid=' . $issue -> id . '&action=raisepriority"
                                title=" ' . get_string('raisepriority', 'local_helpdesk') . ' ">
                                <img src="' . $OUTPUT -> image_url('up', 'local_helpdesk') . '" alt="raisepriority" style="border:0"/>
                             </a>';
            } else {
                $actions .= '<img src="' . $OUTPUT -> image_url('up_shadow', 'local_helpdesk') . '" style="border:0"/>';
                $actions .= '<img src="' . $OUTPUT -> image_url('totop_shadow', 'local_helpdesk') . '" style="border:0"/>';
            }

            if ($issue -> priority > 1) {
                $actions .= '<a href="view.php?issueid=' . $issue -> id . '&action=lowerpriority" 
                                title="' . get_string('lowerpriority', 'local_helpdesk') . '"/>
                                <img src="' . $OUTPUT -> image_url('down', 'local_helpdesk') . '" alt="lowerpriority" style="border: 0">
                            </a>';
                $actions .= '<a href="view.php?issueid=' . $issue -> id . '&action=lowertobottom" 
                                title="' . get_string('lowertobottom', 'local_helpdesk') . '">
                                <img src="' . $OUTPUT -> image_url('tobottom', 'local_helpdesk') . '" alt="lowertobottom" style="border: 0">
                            </a>';
            } else {
                $actions .= '<img src="' . $OUTPUT -> image_url('down_shadow', 'local_helpdesk') . '" style="border: 0"/>';
                $actions .= '<img src="' . $OUTPUT -> image_url('tobottom_shadow', 'local_helpdesk') . '" style="border: 0"/>';
            }
        }

        if ($resolved) {
            $dataset = [$issuenumber, $summary . '' . $solution, $datereported, $reportedby, $assignedto, $status, $actions];
        } else {
            $dataset = [$maxpriority - $issue -> priority + 1, $issuenumber, $summary . ' ' . $solution, $datereported, $reportedby, $assignedto, $status, $actions];
        }
        $table -> add_data($dataset);
    }
    $table -> finish_html();
    echo '<br />';

    echo '<div style = "text-align: center;">';
    echo '<p><input type = "submit" name = "go_btn" value = "' . get_string('savechanges') . '" /></p> ';
    echo '</div > ';
} else {
    echo '<br />';
    echo '<br />';
    if ($resolved) {
        echo $OUTPUT -> notification(get_string('noissuesresolved', 'local_helpdesk'), 'box generalbox', 'notice');
    } else {
        echo $OUTPUT -> notification(get_string('noissuesreported', 'local_helpdesk'), 'box generalbox', 'notice');
    }
}

echo ' </form > ';