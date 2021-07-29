<?php
/**
 * Description actions
 * @copyright 2021 vshapovalov
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package PhpStorm
 */

function xmldb_local_helpdesk_upgrade($oldversion = 0): bool
{
    global $DB;

    $dbman = $DB -> get_manager();

    $result = true;

    if ($oldversion < 2021042904) {

        // Define table helpdesk_issue to be created.
        $table = new xmldb_table('helpdesk_issue');

        // Adding fields to table helpdesk_issue.
        $table -> add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table -> add_field('summary', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table -> add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table -> add_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table -> add_field('datereported', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table -> add_field('reportedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table -> add_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table -> add_field('assignedto', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table -> add_field('bywhomid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table -> add_field('priority', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table helpdesk_issue.
        $table -> add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for helpdesk_issue.
        if (!$dbman -> table_exists($table)) {
            $dbman -> create_table($table);
        }

        // Helpdesk savepoint reached.
        upgrade_plugin_savepoint(true, 2021042904, 'local', 'helpdesk');
    }

    if ($oldversion < 2021051202) {

        // Define table helpdesk_state_change to be created.
        $table = new xmldb_table('helpdesk_state_change');

        // Adding fields to table helpdesk_state_change.
        $table -> add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table -> add_field('issueid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table -> add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table -> add_field('timechange', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table -> add_field('statusfrom', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table -> add_field('statusto', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table helpdesk_state_change.
        $table -> add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for helpdesk_state_change.
        if (!$dbman -> table_exists($table)) {
            $dbman -> create_table($table);
        }

        // Helpdesk savepoint reached.
        upgrade_plugin_savepoint(true, 2021051202, 'local', 'helpdesk');
    }

    if ($oldversion < 2021060400) {

        // Define table helpdesk_issueownership to be created.
        $table = new xmldb_table('helpdesk_issueownership');

        // Adding fields to table helpdesk_issueownership.
        $table -> add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table -> add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table -> add_field('issueid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table -> add_field('bywhomid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table -> add_field('timeassigned', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table helpdesk_issueownership.
        $table -> add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for helpdesk_issueownership.
        if (!$dbman -> table_exists($table)) {
            $dbman -> create_table($table);
        }

        // Helpdesk savepoint reached.
        upgrade_plugin_savepoint(true, 2021060400, 'local', 'helpdesk');
    }

    if ($oldversion < 2021061501) {

        // Define table helpdesk_issuecomment to be created.
        $table = new xmldb_table('helpdesk_issuecomment');

        // Adding fields to table helpdesk_issuecomment.
        $table -> add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table -> add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table -> add_field('issueid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table -> add_field('comment', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table -> add_field('commentformat', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $table -> add_field('datecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Adding keys to table helpdesk_issuecomment.
        $table -> add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for helpdesk_issuecomment.
        if (!$dbman -> table_exists($table)) {
            $dbman -> create_table($table);
        }

        // Helpdesk savepoint reached.
        upgrade_plugin_savepoint(true, 2021061501, 'local', 'helpdesk');
    }


    return $result;
}