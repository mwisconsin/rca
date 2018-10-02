<?php

require_once('include/database.php');

/**
 * Creates a new task in the system.  The task is created tied to a franchise
 * and user role--it must be completed by a user from the selected franchise,
 * and will be presented only to users with the selected role.
 * 
 * Future:  Could tie tasks to entities in the system.
 *
 * @param franchise_id ID of franchise for completing user.
 * @param role_type role to present this task to
 * @param task_description description of the task.  This is very important.
 * @return task ID or FALSE on error
 */
function new_task( $franchise_id, $role_type, $task_description ) {
    $task_id = FALSE;

    $safe_franchise_id = mysql_real_escape_string($franchise_id);
    $safe_role = mysql_real_escape_string($role_type);

    $sql = "INSERT INTO task (FranchiseID, RoleType, EnteredTime)
                    VALUES ($safe_franchise_id, '$safe_role', NOW())";

    $result = mysql_query($sql);

    if ($result) {
        $task_id = mysql_insert_id();
        $note_result = add_note_to_task($task_id, FALSE, $task_description);

        if ($note_result === FALSE) {

            // TODO:  Log this - this is important
            $task_id = FALSE;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Could not add task', $sql);
    }

    return $task_id;
}

/**
 * Adds a note to a task.
 * @param task_id ID of task to add note to
 * @param user_id ID of user adding note; FALSE if no user (auto-note)
 * @param note_text text of note
 * @return ID of note added or FALSE on error
 */
function add_note_to_task($task_id, $user_id = FALSE, $note_text) {
    $note_id = FALSE;
    $safe_task = mysql_real_escape_string($task_id);
    $safe_user = ($user_id === FALSE) ? 'NULL' : "'" . mysql_real_escape_string($user_id) . "'";
    $safe_note = mysql_real_escape_string($note_text);

    $sql = "INSERT INTO task_note (TaskID, CreatorUserID, NoteText) 
                    VALUES ($safe_task, $safe_user, '$safe_note')";

    echo $sql . "\n";
    $result = mysql_query($sql);

    if ($result) {
        $note_id = mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not add note to task $task_id", $sql);
    }

    return $note_id;
}

/**
 * "Claims" a task for a user.  This will not prevent other users from 
 * working the selected task, but it will show other users that a task is 
 * already being worked.  This can prevent users from repeating work.
 *
 * Future:  Add a way to unclaim tasks.  There is currently no way to unclaim
 * a task.
 *
 * @param task_id ID of task within the system
 * @param user_id ID of user claiming task
 * @return TRUE if task could be claimed, FALSE otherwise.
 */
function claim_task_for_user( $task_id, $user_id ) {
    $claimed = FALSE;

    $safe_task = mysql_real_escape_string($task_id);
    $safe_user = mysql_real_escape_string($user_id);
    
    $sql = "UPDATE task SET ClaimedUser = $safe_user, ClaimedTime = NOW()
            WHERE TaskID = $task_id";

    $result = mysql_query($sql);
    if ($result) {
        $claimed = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not claim task $task_id for user $user_id", $sql);
    }

    return $claimed;
}

/**
 * Marks a task completed by the selected user.
 * @param task_id ID of completed task
 * @param user_id ID of user that completed the task
 * @return TRUE if task could be marked completed, FALSE otherwise.
 */
function task_completed( $task_id, $user_id ) {
    $completed = FALSE;

    $safe_task = mysql_real_escape_string($task_id);
    $safe_user = mysql_real_escape_string($user_id);
    
    $sql = "UPDATE task SET CompletedUser = $safe_user, CompletedTime = NOW()
            WHERE TaskID = $task_id";

    $result = mysql_query($sql);
    if ($result) {
        $completed = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not complete task $task_id for user $user_id", $sql);
    }

    return $completed;
}

/**
 * Gets the incomplete tasks for a given franchise/user role.
 * Tasks are ordered with the oldest to the newest.
 *
 * @param franchise_id ID of franchise for completing user.
 * @param role_type role requesting tasks
 * @return array of task hashes.  A task hash contains the TaskID, FranchiseID, RoleType,
 *                                EnteredTime, ClaimedUser, CompletedUser, CompletedTime,
 *                                and Description.  Description is the text of the original
 *                                task note.
 *
 * FUTURE:  It could be really nice to have a get_tasks($user_id) func.
 */
function get_open_tasks($franchise_id, $user_role) {
    $tasks = FALSE;
    $safe_franchise_id = mysql_real_escape_string($franchise_id);
    $safe_role = mysql_real_escape_string($user_role);

    $sql = "SELECT task.TaskID, FranchiseID, RoleType, EnteredTime, ClaimedUser, CompletedUser,
                   CompletedTime, NoteText AS Description
            FROM task, task_note
            WHERE FranchiseID = $safe_franchise_id AND
                  RoleType = '$safe_role' AND
                  CompletedTime IS NULL AND
                  task.TaskID = task_note.TaskID AND
                  task_note.TaskNoteID IN (
                                SELECT MIN(TaskNoteID) FROM task_note AS sub_task_note
                                WHERE sub_task_note.TimeCreated IN (
                                    SELECT MIN(TimeCreated) FROM task_note AS sub_sub_note
                                    WHERE sub_sub_note.TaskID = task.TaskID) )
            ORDER BY EnteredTime ASC";

    echo $sql . "\n\n";
    $result = mysql_query($sql);

    if ($result) {
        $tasks = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $tasks[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error retrieving tasks for $franchise_id/$user_role", $sql); 
    }

    return $tasks;
}

/**
 * Gets all notes for a task in chronological order.  
 * @param task_id ID of task to get notes for
 * @return array of note hashes (keys: TaskNoteID, CreatorUserID, TimeCreated, ModifierUserID, 
 *                                     TimeModified, NoteText) or FALSE on error.
 */
function get_notes_for_task($task_id) {
    $notes = FALSE;

    $safe_task = mysql_real_escape_string($task_id);

    $sql = "SELECT TaskNoteID, CreatorUserID, TimeCreated, ModifierUserID, TimeModified, NoteText
            FROM task_note WHERE TaskID = $safe_task
            ORDER BY TimeCreated ASC";

    $result = mysql_query($sql);

    if ($result) {
        $notes = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $notes[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not retrieve notes for task $task_id");
    }

    return $notes;
}

?>
