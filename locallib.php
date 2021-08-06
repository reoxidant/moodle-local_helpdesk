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
    return get_users_by_capability($context, 'local/helpdesk:resolve', 'u.id,' . $allnames, 'lastname', '', '', '', '', false);
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
        $image_options = initialise_filepicker((array)$args);
        $image_options -> context = $ctx;
        $image_options -> client_id = uniqid();
        $image_options -> maxbytes = @$options['maxbytes'];
        $image_options -> areamaxbytes = @$options['areamaxbytes'];
        $image_options -> env = 'editor';
        $image_options -> itemid = $draftitemid;

        //moodlemedia plugin
        $args -> accepted_types = ['video', 'audio'];
        $media_options = initialise_filepicker((array)$args);
        $media_options -> context = $ctx;
        $media_options -> client_id = uniqid();
        $media_options -> maxbytes = @$options['maxbytes'];
        $media_options -> areamaxbytes = @$options['areamaxbytes'];
        $media_options -> env = 'editor';
        $media_options -> itemid = $draftitemid;

        //advlink plugin
        $args -> accepted_types = '*';
        $link_options = initialise_filepicker((array)$args);
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

    /*
    create table issue_category (id int, categoryname text);
      insert into issue_category values
       (1, 'Академическая задолженность')
      ,(2, 'Выдача документов')
      ,(3, 'Запрос документа');


    create table users (id int, username text);
        insert into users values
        (1, 'Админ')
       ,(2, 'Студент')
       ,(3, 'Учитель');

    create table issue_category_users (categoryid int, userid int);
        insert into issue_category_users values
        (1, 1)
        ,(1, 2)
        ,(1, 3)
        ,(2, 1)
        ,(2, 3)
        ,(3, 2);
    */

    /*
    select ic.*, group_concat(u.username)
     from issue_category as ic
    join issue_category_users as icu
     on icu.categoryid = ic.id
    join users as u
     on u.id = icu.userid
    group by ic.id;
    */

    /*$sql = '
       SELECT
          ic.id, group_concat(ic.name)
       FROM
          {helpdesk_usercategory} ic
       LEFT JOIN
          {user} u
       ON
          ic.userid = u.id
       GROUP BY
          ic.id';
    */

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

function helpdesk_get_members_category($categoryid): array
{
    global $DB;

//    SELECT gm.*, e.courseid, g.name AS groupname
//              FROM {groups_members} gm
//              JOIN {groups} g ON (g.id = gm.groupid)

    return array();
}

function helpdesk_get_all_members(): array
{
    return $DB -> get_records_sql( $sql = 'SELECT * FROM {user} ORDER BY lastname ASC');
}