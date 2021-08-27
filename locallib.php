<?php
/**
 * Description actions
 * @copyright 2021 vshapovalov
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package PhpStorm
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filelib.php");

/**
 *
 */
const OPEN = 1;
/**
 *
 */
const RESOLVING = 2;
/**
 *
 */
const RESOLVED = 3;

global $STATUSCODES;
global $STATUSKEYS;
global $FULLSTATUSKEYS;

$STATUSCODES = array(
    OPEN => 'open',
    RESOLVING => 'resolving',
    RESOLVED => 'resolved'
);

$STATUSKEYS = helpdesk_get_status_keys();
$FULLSTATUSKEYS = helpdesk_get_status_keys();

/**
 * @return array|false|float|int|mixed|string|null
 * @throws coding_exception
 * @throws dml_exception
 */
function helpdesk_resolve_screen()
{
    global $SESSION;

    $screen = optional_param('screen', @$SESSION -> helpdesk_current_screen, PARAM_ALPHA);

    if (empty($screen)) {
        if (has_capability('local/helpdesk:report', context_system ::instance())) {
            $defaultscreen = 'tickets';
        } else {
            $defaultscreen = 'browse';
        }
        $screen = $defaultscreen;
    }

    $SESSION -> helpdesk_current_screen = $screen;
    return $screen;
}

/**
 * @return array|false|float|int|mixed|string|null
 * @throws coding_exception
 */
function helpdesk_resolve_view()
{
    global $SESSION;

    $view = optional_param('view', @$SESSION -> helpdesk_current_view, PARAM_ALPHA);

    if (empty($view)) {
        $defaultview = 'view';
        $view = $defaultview;
    }

    $SESSION -> helpdesk_current_view = $view;
    return $view;
}

/**
 * @param $data
 * @return StdClass|null
 * @throws dml_exception
 * @throws moodle_exception
 */
function helpdesk_submit_issue_form(&$data)
{
    global $DB, $USER;

    $issue = new StdClass();
    $issue -> summary = $data -> summary;
    $issue -> description = $data -> description_editor['text'];
    $issue -> descriptionformat = $data -> description_editor['format'];
    $issue -> datereported = time();
    $issue -> reportedby = $USER -> id;
    $issue -> status = OPEN;
    $issue -> assignedto = 0;
    $issue -> bywhomid = 0;

    $maxpriority = $DB -> get_field_select('helpdesk_issue', 'MAX(priority)', '');
    $issue -> priority = $maxpriority + 1;

    $issue -> id = $DB -> insert_record('helpdesk_issue', $issue);
    if ($issue -> id) {
        $data -> issueid = $issue -> id;
        return $issue;
    }

    print_error('errorrecordissue', 'local_helpdesk');
    return null;
}

/**
 * @return array|mixed
 * @throws coding_exception
 */
function helpdesk_get_status_keys()
{
    static $FULLSTATUSKEYS;

    if (!isset($FULLSTATUSKEYS)) {
        $FULLSTATUSKEYS = array(
            OPEN => get_string('open', 'local_helpdesk'),
            RESOLVING => get_string('resolving', 'local_helpdesk'),
            RESOLVED => get_string('resolved', 'local_helpdesk')
        );
    }

    return $FULLSTATUSKEYS;
}

/**
 * @throws dml_exception
 */
function helpdesk_update_priority_stack()
{
    global $DB;

    // discards resolved
    $sql = '
        UPDATE
            {helpdesk_issue}
        SET
            priority = 0
        WHERE
            status IN (' . RESOLVED . ')
    ';
    $DB -> execute($sql);

    // fetch prioritized by order
    $issues = $DB -> get_records_select('helpdesk_issue', 'priority != 0', null, 'priority', 'id, priority');
    $i = 1;
    if (!empty($issues)) {
        foreach ($issues as $issue) {
            $issue -> priority = $i;
            $DB -> update_record('helpdesk_issue', $issue);
            $i++;
        }
    }
}

/**
 * @throws coding_exception
 */
function helpdesk_can_workon(&$context, $issue = null): bool
{
    global $USER;

    if ($issue) {
        return $issue -> assignedto === $USER -> id && has_capability('local/helpdesk:resolve', $context);
    }

    return has_capability('local/helpdesk:resolve', $context);
}

/**
 * @param $context
 * @param $issue
 * @return bool
 * @throws coding_exception
 */
function helpdesk_can_edit(&$context, &$issue): bool
{
    return
        has_capability('local/helpdesk:manage', $context) ||
        $USER -> id === $issue -> repotedby ||
        ($issue -> assgnedto === $USER -> id && has_capability('local/helpdesk:resolve', $context));
}

/**
 * @param $context
 * @return array
 * @throws coding_exception
 */
function helpdesk_getresolvers($context): array
{
    $allnames = get_all_user_name_fields(true, 'u');
    return get_users_by_capability($context, 'local/helpdesk:resolve', 'u.id, u.email,' . $allnames, 'lastname', '', '', '', '', false);
}

/**
 * @param $context
 * @return array
 * @throws coding_exception
 */
function helpdesk_getmanagers($context): array
{
    $allnames = get_all_user_name_fields(true, 'u');
    return get_users_by_capability($context, 'local/helpdesk:manage', 'u.id,' . $allnames, 'lastname', '', '', '', '', false);
}

/**
 * @param $attributes
 * @param $values
 * @param $options
 * @return string
 * @throws coding_exception
 * @throws moodle_exception
 */
function helpdesk_print_direct_editor($attributes, $values, &$options): string
{
    global $CFG, $PAGE;

    require_once($CFG -> dirroot . '/repository/lib.php');

    $ctx = $options['context'];

    $id = $attributes['id'];
    $elname = $attributes['name'];

    $subdirs = @$options['subdirs'];
    $maxbytes = @$options['maxbytes'];
    $areamaxbytes = @$options['areamaxbytes'];
    $maxfiles = @$options['maxfiles'];
    $changeformat = @$options['changeformat'];

    $text = $values['text'];
    $format = $values['format'];
    $draftitemid = $values['itemid'];

    if (!isloggedin() || isguestuser()) {
        $maxfiles = 0;
    }

    $str = '<div>';

    $editor = editors_get_preferred_editor($format);
    $strformats = format_text_menu();
    $formats = $editor -> get_supported_formats();
    foreach ($formats as $fid) {
        $formats[$fid] = $strformats;
    }

    // get filepicker info

    if ($maxfiles != 0) {
        if (empty($draftitemid)) {
            // no existing area info provided - let's use fresh new draft are
            require_once("$CFG->libdir/filelib.php");
            $draftitemid = file_get_unused_draft_itemid();
            echo "Generating fresh filearea $draftitemid";
        }

        $args = new stdClass();
        // need these three to filter repositories list
        $args -> accepted_types = ['web_image'];
        $args -> return_types = @$options['return_types'];
        $args -> context = $ctx;
        $args -> env = 'filepicker';

        // advimage plugin
        $image_options = initialise_filepicker($args);
        $image_options -> context = $ctx;
        $image_options -> client_id = uniqid();
        $image_options -> maxbytes = @$options['maxbytes'];
        $image_options -> areamaxbytes = @$options['areamaxbytes'];
        $image_options -> env = 'editor';
        $image_options -> itemid = $draftitemid;

        //moodlemedia plugin
        $args -> accepted_types = ['video', 'audio'];
        $media_options = initialise_filepicker($args);
        $media_options -> context = $ctx;
        $media_options -> client_id = uniqid();
        $media_options -> maxbytes = @$options['maxbytes'];
        $media_options -> areamaxbytes = @$options['areamaxbytes'];
        $media_options -> env = 'editor';
        $media_options -> itemid = $draftitemid;

        //advlink plugin
        $args -> accepted_types = '*';
        $link_options = initialise_filepicker($args);
        $link_options -> context = $ctx;
        $link_options -> client_id = uniqid();
        $link_options -> maxbytes = @$options['maxbytes'];
        $link_options -> areamaxbytes = @$options['areamaxbytes'];
        $link_options -> env = 'editor';
        $link_options -> itemid = $draftitemid;

        $fpoptions['image'] = $image_options;
        $fpoptions['media'] = $media_options;
        $fpoptions['link'] = $link_options;
    }

    //If editor is required tinymce, then set required_tinymce option to initalize tinymce validation.
    if (($editor instanceof tinymce_texteditor) && !empty($attributes['onchange'])) {
        $options['required'] = true;
    }

    $editor -> use_editor($id, $options, $fpoptions);

    $rows = empty($attributes['rows']) ? 15 : $attributes['rows'];
    $cols = empty($attributes['cols']) ? 80 : $attributes['cols'];

    //Apply editor validation if required field
    $editorrules = '';
    if (!empty($attributes['onblur']) && !empty($attributes['onchange'])) {
        $editorrules = ' onblur="' . htmlspecialchars($attributes['onblur']) . '" onchange="' . htmlspecialchars($attributes['onchange']) . '"';
    }
    $str .= '<div><textarea id="' . $id . '" name="' . $elname . '[text]" rows="' . $rows . '" cols="' . $cols . ' " ' . $editorrules . ' > ';
    $str .= s($text);
    $str .= '</textarea></div>';

    $str .= '<div>';
    if (count($formats) > 1) {
        $str .= html_writer ::label(get_string('format'), 'menu' . $elname . 'format', false, ['class' => 'accesshide']);

        $str .= html_writer ::select($formats, $elname . '[format]', $format, false, ['id' => 'menu', $elname . 'format']);
    } else {
        $keys = array_keys($formats);
        $str .= html_writer ::empty_tag('input', ['name' => $elname . '[format]', 'type' => 'hidden', 'value' => array_pop($keys)]);
    }
    $str .= '</div>';

    // during moodle installation, user area doesn't exist
    // so we need to disable filepicker here.
    // 0 means no files, -1 unlimited
    if (!($maxfiles == 0) && empty($CFG -> adminsetuppending) && !during_initial_install()) {
        $str .= '<input type="hidden" name="' . $elname . '[itemid]" value="' . $draftitemid . '" />';

        // used by non js editor only
        $editorurl = new moodle_url("$CFG->wwwroot/repository/draftfiles_manager.php", [
            'action' => 'browse',
            'env' => 'editor',
            'itemid' => $draftitemid,
            'subdirs' => $subdirs,
            'maxbytes' => $maxbytes,
            'areamaxbytes' => $areamaxbytes,
            'maxfiles' => $maxfiles,
            'ctx_id' => $ctx -> id,
            'course' => $PAGE -> course -> id,
            'sesskey' => sesskey()
        ]);
        $str .= '<noscript>';
        $str .= "<div><object type='text/html' data='$editorurl' height='160' width='600' style='border: 1px solid #000'></object></div>";
        $str .= '</noscript>';
    }

    $str .= '</div>';

    return $str;
}

// TODO: Create the many to many sequence

/**
 * @return stdClass[]
 * @throws dml_exception
 */
function helpdesk_get_all_categories(): array
{
    global $CFG, $DB;

    return $DB -> get_records_sql('SELECT * FROM {helpdesk_categories} ORDER BY id ASC');
}

/**
 * @return false|mixed
 * @throws moodle_exception
 */
function helpdesk_categories_param_action(string $prefix = 'action_')
{
    $action = false;
    $form_vars = null;

    if ($_POST) {
        $form_vars = $_POST;
    } elseif ($_GET) {
        $form_vars = $_GET;
    }
    if ($form_vars) {
        foreach ($form_vars as $key => $value) {
            if (preg_match("/$prefix(.+)/", $key, $matches)) {
                $action = $matches[1];
                break;
            }
        }
    }
    if ($action && !preg_match('/^\w+$/', $action)) {
        $action = false;
        print_error('unknowaction');
    }
    return $action;
}

/**
 * @param $categoryid
 * @return array
 * @throws dml_exception
 */
function helpdesk_get_members_category($categoryid): array
{
    global $DB;

    $allnames = get_all_user_name_fields(true, 'u');
    $sql = "SELECT u.id, u.email, {$allnames} FROM mdl_helpdesk_categories_members hcm
    LEFT JOIN mdl_helpdesk_categories hc ON hcm.categoryid = hc.id
    LEFT JOIN mdl_user u ON hcm.userid = u.id
    WHERE hc.id = ?
    ORDER BY u.id ASC";

    $members = $DB -> get_records_sql($sql, [$categoryid]);

    $result = [];

    if ($members) {
        foreach ($members as $member) {
            $result[$member -> id] = $member;
        }
    }


    return $result;
}

/**
 * @param $categoryid
 * @param $userid
 * @throws dml_exception
 */
function helpdesk_add_member_category($categoryid, $userid)
{
    global $DB;

    $member = new stdClass();
    $member -> categoryid = $categoryid;
    $member -> userid = $userid;

    $DB -> insert_record('helpdesk_categories_members', $member);
}

/**
 * @param $categoryid
 * @param $userid
 * @throws dml_exception
 */
function helpdesk_remove_member_category($categoryid, $userid)
{
    global $DB;

    $member = [];
    $member['categoryid'] = $categoryid;
    $member['userid'] = $userid;

    $DB -> delete_records('helpdesk_categories_members', $member);
}

function helpdesk_delete_category($categoryid): bool
{
    global $CFG, $DB;

    $category = $DB -> get_record('helpdesk_categories', ['id' => $categoryid]);

    if (!$category) {
        //silently ignore attempts to delete missing already deleted categories ;-)
        return true;
    }

    //delete members
    $DB -> delete_records('helpdesk_categories_members', ['categoryid' => $categoryid]);
    //category itself last
    $DB -> delete_records('helpdesk_categories', ['id' => $categoryid]);

    return true;
}

function helpdesk_getassignees(): array
{
    global $CFG, $DB;

    $allnames = get_all_user_name_fields(true, 'u');

    $sql = "
        SELECT DISTINCT
            u.id,
            {$allnames},
            u.picture,
            u.email,
            u.emailstop,
            u.maildisplay,
            u.imagealt,
            COUNT(i.id) as issues
        FROM
            {helpdesk_issue} i,
            {user} u
        WHERE
            i.assignedto = u.id
        GROUP BY
            u.id,
            u.firstname,
            u.lastname,
            u.picture,
            u.email,
            u.emailstop,
            u.maildisplay,
            u.imagealt
    ";


//    WHERE
//            i.assignedto = u.id AND
//            i.bywhomid = ?

    return $DB -> get_records_sql($sql);
}

function helpdesk_searchforissues(){
    global $CFG;

    helpdesk_clearsearchcookies();
    $fields = helpdesk_extractsearchparametersfrompost();
    $success = helpdesk_setsearchcookies($fields);

    if ($success) {
        if ($tracker->supportmode == 'bugtracker') {
            redirect (new moodle_url('/mod/tracker/view.php', array('id' => $cmid, 'view' => 'view', 'screen' => 'browse')));
        } else {
            redirect(new moodle_url('/mod/tracker/view.php', array('id' => $cmid, 'view' => 'view', 'screen' => 'mytickets')));
        }
    } else {
        print_error('errorcookie', 'helpdesk');
    }
}

function helpdesk_clearsearchcookies(): bool
{
    $success = true;
    $keys = array_keys($_COOKIE); // get the key value of all the cookies
    $cookiekeys = preg_grep('/moodle_helpdesk_search./' , $keys); // filter all search cookies

    foreach ($cookiekeys as $cookiekey) {
        $result = setcookie($cookiekey, '');
        $success = $success && $result;
    }

    return $success;
}


function helpdesk_extractsearchparametersfrompost(): array
{
    $count = 0;
    $fields = array();
    $issuenumber = optional_param('issueid', '', PARAM_INT);
    if (!empty ($issuenumber)) {
        $issuenumberarray = explode(',', $issuenumber);
        foreach ($issuenumberarray as $issueid) {
            if (is_numeric($issueid)) {
                $fields['id'][] = $issueid;
            } else {
                print_error('errorbadlistformat', 'helpdesk');
            }
        }
    } else {
        $checkdate = optional_param('checkdate', 0, PARAM_INT);
        if ($checkdate) {
            $month = optional_param('month', '', PARAM_INT);
            $day = optional_param('day', '', PARAM_INT);
            $year = optional_param('year', '', PARAM_INT);

            if (!empty($month) && !empty($day) && !empty($year)) {
                $datereported = make_timestamp($year, $month, $day);
                $fields['datereported'][] = $datereported;
            }
        }

        $description = optional_param('description', '', PARAM_CLEANHTML);
        if (!empty($description)) {
            $fields['description'][] = stripslashes($description);
        }

        $reportedby = optional_param('reportedby', '', PARAM_INT);
        if (!empty($reportedby)) {
            $fields['reportedby'][] = $reportedby;
        }

        $assignedto = optional_param('assignedto', '', PARAM_INT);
        if (!empty($assignedto)) {
            $fields['assignedto'][] = $assignedto;
        }

        $summary = optional_param('summary', '', PARAM_TEXT);
        if (!empty($summary)) {
            $fields['summary'][] = $summary;
        }

        $keys = array_keys($_POST);                         // get the key value of all the fields submitted
        $elementkeys = preg_grep('/element./' , $keys);     // filter out only the element keys

        foreach ($elementkeys as $elementkey) {
            preg_match('/element(.*)$/', $elementkey, $elementid);
            if (!empty($_POST[$elementkey])) {
                if (is_array($_POST[$elementkey])) {
                    foreach ($_POST[$elementkey] as $elementvalue) {
                        $fields[$elementid[1]][] = $elementvalue;
                    }
                } else {
                    $fields[$elementid[1]][] = $_POST[$elementkey];
                }
            }
        }
    }
    return $fields;
}

function helpdesk_setsearchcookies($fields){
    $success = true;
    if (is_array($fields)) {
        $keys = array_keys($fields);

        foreach ($keys as $key) {
            $cookie = '';
            foreach ($fields[$key] as $value) {
                if (empty($cookie)) {
                    $cookie .= $value;
                } else {
                    $cookie .= ', ' . $value;
                }
            }

            $result = setcookie('moodle_helpdesk_search_' . $key, $cookie);
            $success = $success && $result;
        }
    } else {
        $success = false;
    }
    return $success;
}

function helpdesk_extractsearchcookies(): array
{
    $keys = array_keys($_COOKIE);                                           // get the key value of all the cookies
    $cookiekeys = preg_grep('/moodle_helpdesk_search./' , $keys);            // filter all search cookies
    $fields = null;
    foreach ($cookiekeys as $cookiekey) {
        preg_match('/moodle_helpdesk_search_(.*)$/', $cookiekey, $fieldname);
        $fields[$fieldname[1]] = explode(', ', $_COOKIE[$cookiekey]);
    }
    return $fields;
}

function helpdesk_constructsearchqueries($fields, $own = false): StdClass
{
    global $CFG, $USER, $DB;

    $keys = array_keys($fields);

    // Check to see if we are search using elements as a parameter.
    // If so, we need to include the table tracker_issueattribute in the search query.
    $elementssearch = false;
    foreach ($keys as $key) {
        if (is_numeric($key)) {
            $elementssearch = true;
        }
    }
    $elementsSearchClause = ($elementssearch) ? " {tracker_issueattribute} AS ia, " : '' ;

    $elementsSearchConstraint = '';
    foreach ($keys as $key) {
        if ($key == 'id') {
            $elementsSearchConstraint .= ' AND  (';
            foreach ($fields[$key] as $idtoken) {
                $elementsSearchConstraint .= (empty($idquery)) ? 'i.id =' . $idtoken : ' OR i.id = ' . $idtoken ;
            }
            $elementsSearchConstraint .= ')';
        }

        if ($key == 'datereported' && array_key_exists('checkdate', $fields) ) {
            $datebegin = $fields[$key][0];
            $dateend = $datebegin + 86400;
            $elementsSearchConstraint .= " AND i.datereported > {$datebegin} AND i.datereported < {$dateend} ";
        }

        if ($key == 'description') {
            $tokens = explode(' ', $fields[$key][0], ' ');
            foreach ($tokens as $token) {
                $elementsSearchConstraint .= " AND i.description LIKE '%{$descriptiontoken}%' ";
            }
        }

        if ($key == 'reportedby') {
            $elementsSearchConstraint .= ' AND i.reportedby = ' . $fields[$key][0];
        }

        if ($key == 'assignedto') {
            $elementsSearchConstraint .= ' AND i.assignedto = ' . $fields[$key][0];
        }

        if ($key == 'summary') {
            $summarytokens = explode(' ', $fields[$key][0]);
            foreach ($summarytokens as $summarytoken) {
                $elementsSearchConstraint .= " AND i.summary LIKE '%{$summarytoken}%'";
            }
        }

        if (is_numeric($key)) {
            foreach ($fields[$key] as $value) {
                $elementsSearchConstraint .= ' AND i.id IN (SELECT issue FROM {tracker_issueattribute} WHERE elementdefinition=' . $key . ' AND elementitemid=' . $value . ')';
            }
        }
    }
    if ($own == false) {
        $sql = new StdClass();
        $sql->search = "
            SELECT DISTINCT
                i.id,
                i.trackerid,
                i.summary,
                i.datereported,
                i.reportedby,
                i.assignedto,
                i.resolutionpriority,
                i.status,
                COUNT(cc.userid) AS watches,
                u.firstname,
                u.lastname
            FROM
                {user} AS u,
                $elementsSearchClause
                {tracker_issue} i
            LEFT JOIN
                {tracker_issuecc} cc
            ON
                cc.issueid = i.id
            WHERE
                i.trackerid = {$trackerid} AND
                i.reportedby = u.id $elementsSearchConstraint
            GROUP BY
                i.id,
                i.trackerid,
                i.summary,
                i.datereported,
                i.reportedby,
                i.assignedto,
                i.status,
                u.firstname,
                u.lastname
        ";
        $sql->count = "
            SELECT COUNT(DISTINCT
                (i.id)) as reccount
            FROM
                {tracker_issue} i
                $elementsSearchClause
            WHERE
                i.trackerid = {$trackerid}
                $elementsSearchConstraint
        ";
    } else {
        $sql->search = "
            SELECT DISTINCT
                i.id,
                i.trackerid,
                i.summary,
                i.datereported,
                i.reportedby,
                i.resolutionpriority,
                i.assignedto,
                i.status,
                COUNT(cc.userid) AS watches
            FROM
                $elementsSearchClause
                {tracker_issue} i
            LEFT JOIN
                {tracker_issuecc} cc
            ON
                cc.issueid = i.id
            WHERE
                i.trackerid = {$trackerid} AND
                i.reportedby = {$USER->id}
                $elementsSearchConstraint
            GROUP BY
                i.id, i.trackerid, i.summary, i.datereported, i.reportedby, i.assignedto, i.status
        ";
        $sql->count = "
            SELECT COUNT(DISTINCT
                (i.id)) as reccount
            FROM
                {tracker_issue} i
                $elementsSearchClause
            WHERE
                i.trackerid = {$trackerid} AND
                i.reportedby = $USER->id
                $elementsSearchConstraint
        ";
    }
    return $sql;
}