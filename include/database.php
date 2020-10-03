<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/..');

// TODO:  Make this more reliable.
require_once(__DIR__ . '/../../' . 'private_include/riders_club_params.php');

$link;

$db_connection_link;

define('MYSQL_BOTH',MYSQLI_BOTH);
define('MYSQL_NUM',MYSQLI_NUM);
define('MYSQL_ASSOC',MYSQLI_ASSOC);

/**
* Database Connect
*
* The function db_connect() connects to the server or dies
* 
* @author Joel Bixby 
*/
function db_connect()
{
    global $__DB_HOSTNAME, $__DB_RW_USER, $__DB_RW_PASS, $__DB_NAME, $link, $db_connection_link;

    $link = mysqli_connect($__DB_HOSTNAME, $__DB_RW_USER, $__DB_RW_PASS);
    if (!$link) {
        die('Could not connect to the Database: ' . mysqli_error());
    }
    $database = mysqli_select_db($link,$__DB_NAME);
    if (!$database) {
        die('Could not connect to the database ' . mysqli_error());
    }
	mysqli_query($link,"SET time_zone = '-5:00';");
	
	$db_connection_link = $link;
}

if(!function_exists('mysql_query')) {
	function mysql_query($sql) {
		global $db_connection_link;
		return mysqli_query($db_connection_link, $sql);
	}
}

if(!function_exists('mysql_real_escape_string')) {
	function mysql_real_escape_string($str) {
		global $db_connection_link;
		return mysqli_real_escape_string($db_connection_link, $str);
	}
}

if(!function_exists('mysql_escape_string')) {
	function mysql_escape_string($str) {
		global $db_connection_link;
		return mysqli_real_escape_string($db_connection_link, $str);	
	}
}

if(!function_exists('mysql_fetch_array')) {
	function mysql_fetch_array(mysqli_result $r) {
		global $db_connection_link;
		return mysqli_fetch_array($r,MYSQLI_BOTH);
	}
}

if(!function_exists('mysql_fetch_assoc')) {
	function mysql_fetch_assoc(mysqli_result $r) {
		global $db_connection_link;
		return mysqli_fetch_array($r,MYSQLI_BOTH);
	}
}

if(!function_exists('mysql_num_rows')) {
	function mysql_num_rows($r) {
		if($r === FALSE) return 0;
		global $db_connection_link;
		return mysqli_num_rows($r);
	}
}

if(!function_exists('mysql_error')) {
	function mysql_error() {
		global $db_connection_link;
		return mysqli_error($db_connection_link);
	}
}

if(!function_exists('mysql_insert_id')) {
	function mysql_insert_id() {
		global $db_connection_link;
		return mysqli_insert_id($db_connection_link);
	}
}

if(!function_exists('mysql_errno')) {
	function mysql_errno() {
		global $db_connection_link;
		return mysqli_errno($db_connection_link);
	}
}

/**
* Database Connect Read Only
*
* The function db_connect_readonly() connects to the server with read only privliages or dies
* 
* @author Joel Bixby 
*/
function db_connect_readonly()
{
    db_connect();
    return;
    global $__DB_HOSTNAME, $__DB_READ_ONLY_USER, $__DB_READ_ONLY_PASS, $__DB_NAME;

    $link = mysql_connect($__DB_HOSTNAME, $__DB_READ_ONLY_USER, $__DB_READ_ONLY_PASS);
    if (!$link) {
        die('Could not connect to mySQL ' . mysql_error());
    }
    $database = mysql_select_db($__DB_NAME, $link);
    if (!$database) {
        die('Could not connect to the database ' . mysql_error());
    }
}

/**
 * Starts a database transaction.  InnoDB tables are transactional, MyISAM are not.
 * It is up to the application to know what's what.
 * @return TRUE on success, FALSE on failure
 */
function db_start_transaction() {
    $result = mysql_query('START TRANSACTION');

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not start transaction");
        return FALSE;
    }
}

/**
 * Commits a database transaction.  InnoDB tables are transactional, MyISAM are not.
 * It is up to the application to know what's what.
 * @return TRUE on success, FALSE on failure
 */
function db_commit_transaction() {
    $result = mysql_query('COMMIT');

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not commit transaction");
        return FALSE;
    }
}

/**
 * Rolls back a database transaction.  InnoDB tables are transactional, MyISAM are not.
 * It is up to the application to know what's what.
 * @return TRUE on success, FALSE on failure
 */
function db_rollback_transaction() {
    $result = mysql_query('ROLLBACK');

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not rollback transaction");
        return FALSE;
    }
}

db_connect();
?>
