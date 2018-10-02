<?php if (!defined('DATATABLES')) exit(); // Ensure being used in DataTables env.

// Enable error reporting for debugging (remove for production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once($_SERVER['DOCUMENT_ROOT'] . '/../' . 'private_include/riders_club_params.php');

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Database user / pass
 */
$sql_details = array(
	"type" => "Mysql",  // Database type: "Mysql", "Postgres", "Sqlite" or "Sqlserver"
	"user" => $__DB_RW_USER,       // Database user name
	"pass" => $__DB_RW_PASS,       // Database password
	"host" => $__DB_HOSTNAME,       // Database host
	"port" => "",       // Database connection port (can be left empty for default)
	"db"   => $__DB_NAME,       // Database name
	"dsn"  => ""        // PHP DSN extra information. Set as `charset=utf8` if you are using MySQL
);


