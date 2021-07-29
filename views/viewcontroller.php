<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/****************************** update an issue ******************************/
if ($action === 'updateanissue') {

    $issue = new StdClass;

    $issue -> id = required_param('issueid', PARAM_INT);
    $issue -> issueid = $issue -> id;
    $issue -> status = required_param('status', PARAM_INT);
    $issue -> assignedto = required_param('assignedto', PARAM_INT);
    $issue -> summary = required_param('summary', PARAM_TEXT);
    $issue -> description_editor = required_param_array('description_editor', PARAM_CLEANHTML);
    $issue -> descriptionformat = $issue -> description_editor['format'];
    $editoroptions = ['maxfiles' => 99, 'maxbytes' => $CFG -> maxbytes, 'context' => $context];

    $issue -> resolution_editor = required_param_array('resolution_editor', PARAM_CLEANHTML);
    $issue -> resolutionformat = $issue -> resolution_editor['format'];

    $issue -> description = file_save_draft_area_files(
        $issue -> description_editor['itemid'],
        $context -> id,
        'helpdesk_local',
        'issuedescription',
        $issue -> id,
        $editoroptions,
        $issue -> description_editor['text']
    );

    $issue -> resolution = file_save_draft_area_files(
        $issue -> resolution_editor['itemid'],
        $context -> id,
        'helpdesk_local',
        'issueresolution',
        $issue -> id,
        $editoroptions,
        $issue -> resolution_editor['text']
    );

    $issue -> datereported = required_param('datereported', PARAM_INT);

    // if ownership has changed, prepare logging
    $oldrecord = $DB -> get_record('helpdesk_issue', ['id' => $issue -> id]);
    if ($oldrecord -> assignedto != $issue -> assignedto) {
        $ownership = new StdClass;
        $ownership -> issueid = $oldrecord -> id;
        $ownership -> userid = $oldrecord -> assignedto;
        $ownership -> bywhomid = $oldrecord -> bywhomid;
        $ownership -> timeassigned = ($oldrecord -> timeassigned) ? $oldrecord -> timeassigned : time();
        if (!$DB -> insert_record('helpdesk_issueownership', $ownership)) {
            print_error('errorcannotlogoldownership', 'local_helpdesk');
        }
    }
    $issue -> bywhoid = $USER -> id;
    $issue -> timeassigned = time();

    if (!$DB -> update_record('helpdesk_issue', $issue)) {
        print_error('errorcannotupdateissue', 'local_helpdesk');
    }

    // send state change notification
    if ($oldrecord -> status != $issue -> status) {

        // log state change
        $stc = new StdClass;
        $stc -> userid = $USER -> id;
        $stc -> timechange = time();
        $stc -> statusfrom = $oldrecord -> status;
        $stc -> statustp = $issue -> status;
        $DB -> insert_record('helpdesk_state_change', $stc);
    }
} /****************************** updating list and status ******************************/
elseif ($action === 'updatelist') {
    $keys = array_keys($_POST);
    $statuskeys = preg_grep('/status./', $keys);              // filter out only the status
    $assignedtokeys = preg_grep('/assignedto./', $keys);      // filter out only the assigned updating
    $newassignedtokeys = preg_grep('/assignedtoi./', $keys);
    foreach ($statuskeys as $akey) {
        $issueid = str_replace('status', '', $akey);
        $haschanged = optional_param('schanged' . $issueid, 0, PARAM_INT);
        if ($haschanged) {
            $issue = new StdClass;
            $issue -> id = $issueid;
            $issue -> status = required_param($akey, PARAM_INT);
            $oldstatus = $DB -> get_field('helpdesk_issue', 'status', ['id' => $issue -> id]);
            $DB -> update_record('helpdesk_issue', $issue);
            // check status changing and send notifications
            if ($oldstatus !== $issue -> status) {
                $stc = new StdClass;
                $stc -> userid = $USER -> id;
                $stc -> issueid = $issue -> id;
                $stc -> timechange = time();
                $stc -> statusform = $oldstatus;
                $stc -> statusto = $issue -> status;
                $DB -> insert_record('helpdesk_state_change', $stc);
            }
        }
    }

    // always add a record for history

    foreach ($assignedtokeys as $akey) {
        $issueid = str_replace('assignedto', '', $akey);

        // new ownership is triggered only when a change occured
        $haschanged = optional_param('changed' . $issueid, 0, PARAM_INT);

        if ($haschanged) {
            // save old assignment in history
            $oldassign = $DB -> get_record('helpdesk_issue', ['id' => $issueid]);
            if ($oldassignd -> assignedto != 0) {
                $ownership = new StdClass;
                $ownership -> issueid = $issueid;
                $ownership -> userid = $oldassign -> assignedto;
                $onwership -> bywhomid = $oldassign -> bywhomid;
                $ownership -> timeassigned = 0 + @$oldassign -> timeassigned;
                if (!$DB -> insert_record('helpdesk_issueownership', $onwership)) {
                    notice("Error saving ownership for issue $issueid");
                }
            }


            // update actual helpdesk

            $issue = new StdClass;
            $issue -> id = $issueid;
            $issue -> bywhomid = $USER -> id;
            $issue -> timeassigned = time();
            $issue -> assignedto = required_param($akey, PARAM_INT);
            if (!$DB -> update_record('helpdesk_issue', $issue)) {
                notice("Error updating assignation for issue $issueid");
            }
        }
    }

    helpdesk_update_priority_stack();
} /****************************** delete an issue record ******************************/
elseif ($action === 'delete') {
    $issueid = required_param('issueid', PARAM_INT);

    $maxpriority = $DB -> get_field('helpdesk_issue', 'priority', ['id' => $issueid]);

    $DB -> delete_records('helpdesk_issue', ['id' => $issueid]);
    $commentids = $DB -> get_records('helpdesk_issuecomment', ['issueid' => $issueid], 'id', 'id,id');
    $DB -> delete_records('helpdesk_issuecomment', ['issueid' => $issueid]);
    $DB -> delete_records('helpdesk_issueownership', ['issueid' => $issueid]);
    $DB -> delete_records('helpdesk_state_change', ['issueid' => $issueid]);

    // lower priority of every issue above

    $sql = '
        UPDATE
            {helpdesk_issue}
        SET
            priority = priority - 1
        WHERE
            priority > ?
    ';

    $DB -> execute($sql, [$maxpriority]);

    // clear all associated fileareas

    $fs = get_file_storage();
    $fs -> delete_area_files($context -> id, 'local_helpdesk', 'issuedescription', $issueid);
    $fs -> delete_area_files($context -> id, 'local_helpdesk', 'issueresolution', $issueid);

    if ($commentids) {
        foreach ($commentids as $commentid => $void) {
            $fs -> delete_area_files($context -> id, 'local_helpdesk', 'issuecomment', $commentid);
        }
    }
} /****************************** raises the priority of the issue ******************************/
elseif ($action == 'raisepriority') {
    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB -> get_record('helpdesk_issue', array('id' => $issueid));
    $nextissue = $DB -> get_record('helpdesk_issue', array('priority' => $issue -> priority + 1));
    if ($nextissue) {
        $issue -> priority++;
        $nextissue -> priority--;
        $DB -> update_record('helpdesk_issue', $issue);
        $DB -> update_record('helpdesk_issue', $nextissue);
    }
    helpdesk_update_priority_stack();
} /****************************** raises the priority at top of list ******************************/
elseif ($action == 'raisetotop') {
    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB -> get_record('helpdesk_issue', ['id' => $issueid]);
    $maxpriority = $DB -> get_field_select('helpdesk_issue', 'MAX(priority)', '');

    if ($issue -> priority != $maxpriority) {

        // lower everyone above
        $sql = '
            UPDATE
                {helpdesk_issue}
            SET
                priority = priority - 1
            WHERE
                priority > ?
        ';

        $DB -> execute($sql, [$issue -> priority]);
        // update to max priority
        $issue -> priority = $maxpriority;
        $DB -> update_record('helpdesk_issue', $issue);
    }
    helpdesk_update_priority_stack();
} /****************************** lowers the priority of the issue ******************************/
elseif ($action == 'lowerpriority') {
    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB -> get_record('helpdesk_issue', ['id' => $issueid]);
    if ($issue -> priority > 0) {
        $nextissue = $DB -> get_record('helpdesk_issue', ['priority' => $issue -> priority - 1]);
        $issue -> priority--;
        $nextissue -> priority++;
        $DB -> update_record('helpdesk_issue', $issue);
        $DB -> update_record('helpdesk_issue', $nextissue);
    }
    helpdesk_update_priority_stack();
} /****************************** raises the priority at top of list  ******************************/
elseif ($action == 'lowertobottom') {
    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB -> get_record('helpdesk_issue', ['id' => $issueid]);

    if ($issue -> priority > 0) {
        // raise everyone beneath
        $sql = '
            UPDATE
                {helpdesk_issue}
            SET
                priority = priority + 1
            WHERE
                priority < ?
        ';

        $DB -> execute($sql, [$issue -> priority]);
        // update to min priority
        $issue -> priority = 0;
        $DB -> update_record('helpdesk_issue', $issue);
    }
    helpdesk_update_priority_stack();
}