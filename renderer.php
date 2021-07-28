<?php
/**
 * Description actions
 * @copyright 2021 vshapovalov
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package PhpStorm
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_helpdesk_renderer
 */
class local_helpdesk_renderer extends plugin_renderer_base
{

    /**
     * @param $view
     * @param $screen
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function tabs($view, $screen): string
    {
        global $OUTPUT, $DB, $USER;

        $str = '';
        $context = context_system ::instance();

        if ($screen === 'tickets') {
            $select = 'status <> ' . RESOLVED . ' AND reportedby = ? ';
            $totalissues = $DB -> count_records_select('helpdesk_issue', $select, [$USER -> id]);

            $select = 'status = ' . RESOLVED . ' AND reportedby = ? ';
            $totalresolvedissues = $DB -> count_records_select('helpdesk_issue', $select, [$USER -> id]);
        } else {
            $select = 'status <> ' . RESOLVED;
            $totalissues = $DB -> count_records_select('helpdesk_issue', $select);

            $select = 'status = ' . RESOLVED;
            $totalresolvedissues = $DB -> count_records_select('helpdesk_issue', $select);
        }

        // Render Tabs with options for user.

        if ($context === null) {
            die('context is null');
        }

        if (has_capability('local/helpdesk:report', $context)) {
            $rows[0][] = new tabobject('reportanissue', 'reportissue.php', get_string('newissue', 'local_helpdesk'));
        }

        $rows[0][] = new tabobject('view', 'view.php?view=view', get_string('view', 'local_helpdesk') .
            ' (' . $totalissues . ' ' . get_string('issues', 'local_helpdesk') . ')'
        );

        $rows[0][] = new tabobject('resolved', 'view.php?view=resolved', get_string('resolvedplural', 'local_helpdesk') .
            ' (' . $totalresolvedissues . ' ' . get_string('issues', 'local_helpdesk') . ')'
        );

        if (has_capability('local/helpdesk:manage', $context)) {
            $rows[0][] = new tabobject('categories', 'view.php?view=categories', get_string('managecategories', 'local_helpdesk'));
        }

        // Render Subtabs menu

        $selected = null;
        $activated = null;
        switch ($view) {
            case 'view' :
                if (!preg_match('/tickets|browse|search|viewanissue|editanissue/', $screen)) {
                    $screen = 'tickets';
                }
                if (has_capability('local/helpdesk:report', $context)) {
                    $rows[1][] = new tabobject('tickets', 'view.php?view=view&amp;screen=tickets', get_string('tickets', 'local_helpdesk'));
                }
                if (has_capability('local/helpdesk:viewallissues', $context)) {
                    $rows[1][] = new tabobject('browse', 'view.php?view=view&amp;screen=browse', get_string('browse', 'local_helpdesk'));
                }
                break;
            case 'resolved' :
                if (!preg_match('/tickets|browse/', $screen)) {
                    $screen = 'tickets';
                }
                if (has_capability('local/helpdesk:report', $context)) {
                    $rows[1][] = new tabobject('tickets', 'view.php?view=resolved&amp;screen=tickets', get_string('tickets', 'local_helpdesk'));
                }
                if (has_capability('local/helpdesk:viewallissues', $context)) {
                    $rows[1][] = new tabobject('browse', 'view.php?view=resolved&amp;screen=browse', get_string('browse', 'local_helpdesk'));
                }
                break;
            case 'categories':
                if (!preg_match('/addcategory|assignmanagers/', $screen)) {
                    $screen = 'assignmanagers';
                }
                $rows[1][] = new tabobject('addcategory', 'view.php?view=categories&amp;screen=addcategory', get_string('addcategory', 'local_helpdesk'));
                $rows[1][] = new tabobject('assignmanagers', 'view.php?view=categories&amp;screen=assignmanagers', get_string('assignmanagers', 'local_helpdesk'));
                break;
            default:
        }
        if (!empty($screen)) {
            $selected = $screen;
            $activated = [$view];
        } else {
            $selected = $view;
        }
        $str .= $OUTPUT -> container_start('local-header helpdesk-tabs');
        $str .= print_tabs($rows, $selected, '', $activated, true);
        $str .= $OUTPUT -> container_end();

        return $str;
    }

    /**
     * @param $issue
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function edit_link($issue): string
    {
        $params = ['view' => 'view', 'screen' => 'editanissue', 'issueid' => $issue -> id];

        $issueurl = new moodle_url('/local/helpdesk/view.php', $params);

        return '<tr>
                    <td colspan="4"  style="text-align: right">
                        <form method="post" action="' . $issueurl . '">
                            <input type="submit" name="go_btn" value="' . get_string('turneditingon', 'local_helpdesk') . '">
                        </form>
                    </td>
                </tr>';

    }

    /**
     * @param $issue
     * @return string
     * @throws coding_exception
     */
    public function core_issue($issue): string
    {
        global $OUTPUT, $STATUSCODES, $STATUSKEYS;

        if ($issue -> owner) {
            $assignedto = $OUTPUT -> user_picture($issue -> owner, ['size' => 35]) . '&nbsp;' . fullname($issue -> owner);
        } else {
            $assignedto = get_string('unassigned', 'local_helpdesk');
        }

        return '
                <!--The name of issue-->
                <tr style="vertical-align:top">
                    <td colspan="4" style="text-align:left" class="helpdesk-issue-summary">' . format_string($issue -> summary) . '</td>
                </tr>
                <!--The issue info-->
                <tr style="vertical-align: top">
                    <td style="text-align: right; width: 25%;" class="helpdesk-issue-param">
                        <b>' . get_string('issuenumber', 'local_helpdesk') . ':</b><br/>
                    </td>
                    <td style="width: 25%" class="helpdesk-issue-value">
                        ' . $issue -> id . '
                    </td>
                    <td style="text-align: right; width: 25%;" class="helpdesk-issue-param">
                        <b>' . get_string('status', 'local_helpdesk') . ':</b>
                    </td>
                    <td style="width: 25%;" class="status_' . $STATUSCODES[$issue -> status] . ' helpdesk-issue-value">
                        ' . $STATUSKEYS[$issue -> status] . '
                    </td>
                </tr>
                <!--The reported user info-->
                <tr style="vertical-align:top">
                    <td style="text-align: right; width: 25%;" class="helpdesk-issue-param">
                        <b>' . get_string('reportedby', 'local_helpdesk') . ':</b>
                    </td>
                    <td style="width: 25%;" class="helpdesk-issue-value">
                        ' . $OUTPUT -> user_picture($issue -> reporter) . '&nbsp;' . fullname($issue -> reporter) . '
                    </td>
                    <td style="text-align: right; width: 25%;" class="helpdesk-issue-param">
                        <b>' . get_string('datereported', 'local_helpdesk') . ':</b>
                    </td>
                    <td style="width: 25%;" class="helpdesk-issue-value">' . userdate($issue -> datereported) . '</td>
                </tr>
                <!--The assigned issue info-->
                <tr style="vertical-align: top">
                    <td style="text-align: right; width: 25%;" class="helpdesk-issue-param">
                        <b>' . get_string('assignedto', 'local_helpdesk') . ':</b>
                    </td>
                    <td style="width: 25%" class="helpdesk-issue-value">
                        ' . $assignedto . '
                    </td>
             
                </tr>
                <!--The description issue info-->
                <tr style="vertical-align: top">
                    <td style="text-align: right; width: 25%;" class="helpdesk-issue-param">
                        <b>' . get_string('description') . ':</b>
                    </td>
                    <td style="text-align: left; width:75%;" colspan="3" class="helpdesk-issue-value">
                        ' . format_text($issue -> description) . '
                    </td>
                </tr>
                ';
    }

    /**
     * @param $history
     * @param $statehistory
     * @param $initialviewmode
     * @return String
     * @throws coding_exception|dml_exception|moodle_exception
     */
    public function history($history, $statehistory, $initialviewmode): string
    {
        global $DB, $OUTPUT, $STATUSCODES, $STATUSKEYS;

        if (!empty($history)) {
            foreach ($history as $owner) {
                $user = $DB -> get_record('user', ['id' => $owner -> userid]);
                $bywhom = $DB -> get_record('user', ['id' => $owner -> bywhomid]);

                $ownerinfo .= '<tr>
                    <td style="text-align: left">
                        ' . userdate($owner -> timeassigned) . '
                    </td>
                    <td style="text-align: left">
                        ' . $this -> user($user) . ' 
                    </td>
                    <td style="text-align: left">
                        ' . get_string('by', 'local_helpdesk') . ' ' . fullname($bywhom) . '
                    </td>
                </tr>';
            }
        }

        if (!empty($statehistory)) {
            foreach ($statehistory as $state) {
                $bywhom = $DB -> get_record('user', ['id' => $state -> userid]);

                $stateinfo .= '<tr style="vertical-align: top">
                        <td style="text-align: left">
                            ' . userdate($state -> timechange) . '
                        </td>
                        <td style="text-align: left">
                            ' . $this -> user($bywhom) . '
                        </td>
                        <td style="text-align: left">
                            <span class="status_' . $STATUSCODES[$state -> statusfrom] . '">
                                ' . $STATUSKEYS[$state -> statusform] . '
                            </span>
                        </td>
                        <td style="text-align: left">
                            <span class="status_' . $STATUSCODES[$state -> statusto] . '">
                                ' . $STATUSKEYS[$state -> statusto] . '
                            </span>
                        </td>
                    </tr>';
            }
        }

        return
            '<tr>
                <td colspan="4" style="text-align: center; width: 100%">
                    <table id="issuehistory" class="' . $initialviewmode . '" style="width: 100%">
                        <tr style="vertical-align: top">
                            <td style="width: 50%">' . $OUTPUT -> heading(get_string('history', 'local_helpdesk')) . '</td>
                            <td style="width: 50%">' . $OUTPUT -> heading(get_string('statehistory', 'local_helpdesk')) . '</td>
                        </tr>
                        <tr>
                            <td style="width: 50%">
                                <table style="width: 100%">
                                    ' . $ownerinfo . '
                                </table>
                            </td>
                            <td style="width:50%">
                                <table style="width: 100%">
                                    ' . $stateinfo . '
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>';
    }

    /**
     * @param $user
     * @return string
     * @throws moodle_exception
     */
    public function user($user): string
    {
        global $CFG, $OUTPUT;

        $str = '';

        if ($user) {
            $str .= $OUTPUT -> user_picture($user, ['size' => 25]);
            $userurl = new moodle_url('/user/view.php', ['id' => $user -> id]);
            if ($CFG -> messaging) {
                $str .= '<a href="' . $userurl . '">' . fullname($user) . '</a>
                         <a href="" onclick="this.target=\'message\'; return openpopup(\'/message/discussion.php?id=' . $user -> id . '\', \'message\', \'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500\', 0);">
                            <img src="' . $OUTPUT -> image_url('t/message', 'core') . '" alt="t/message">
                         </a>';
            } elseif (!$user -> emailstop && $user -> maildisplay) {
                $str .= '<a href="' . $userurl . '">' . fullname($user) . '</a>
                         <a href="mailto:' . $user -> email . '">
                            <img src="' . $OUTPUT -> image_url('t/mail', 'core') . '"  alt="t/mail">
                         </a>';
            } else {
                $str .= fullname($user);
            }
        }

        return $str;
    }

    /**
     * @param $issueid
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function print_comments($issueid): string
    {
        global $CFG, $DB;

        $comments = $DB -> get_records('helpdesk_issuecomment', ['issueid' => $issueid], 'datecreated');

        $html = '';

        if ($comments) {
            foreach ($comments as $comment) {
                $user = $DB -> get_record('user', ['id' => $comment -> userid]);

                $html .=
                    '<tr>
                        <td style="vertical-align: top; width: 30%" class="commenter">' . $this -> user($user) . '<br/>
                            <span class="timelabel">' . userdate($comment -> datecreated) . '</span>
                        </td>
                        <td colspan="3" style="vertical-align: top" class="comment">' . $comment -> comment . '</td>
                    </tr>';
            }
        }

        return $html;
    }
}