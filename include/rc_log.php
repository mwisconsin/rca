<?php

//if (DEPLOYMENT_ENVIRONMENT=='PRODUCTION') {

require_once('include/Pear/Log.php');
require_once(__DIR__ . '/../../' . 'private_include/riders_club_params.php');

//}
/*
 * Some usage examples:
 * 
 * rc_log(PEAR_LOG_DEBUG, "This is my debug message";  // DEBUG log to syslog
 * rc_log(PEAR_LOG_WARNING, "Oh no!  A warning!");      // WARNING log to syslog
 * rc_development_log(PEAR_LOG_DEBUG, "Debug info going to Firebug", 'firebug');   // DEBUG log to Firebug console
 */

/**
 * Log a message to the syslog.
 *
 * @param Log Level $level (PEAR_LOG_XYZ, where XYZ is generally ERR, WARNING, NOTICE, INFO, DEBUG)
 *                         NOTICE is "normal but significant", INFO is informational.
 * (borrowed from Log class docs):
 * <table><tr><th>Level</th><th>Description</th></tr>
 * <tr><td>             PEAR_LOG_EMERG      </td><td>       System is unusable      </td></tr>
 * <tr><td>             PEAR_LOG_ALERT      </td><td>       Immediate action required </td></tr>
 * <tr><td>             PEAR_LOG_CRIT       </td><td>       Critical conditions     </td></tr>
 * <tr><td>             PEAR_LOG_ERR        </td><td>       Error conditions        </td></tr>
 * <tr><td>             PEAR_LOG_WARNING    </td><td>       Warning conditions      </td></tr>
 * <tr><td>             PEAR_LOG_NOTICE     </td><td>       Normal but significant  </td></tr>
 * <tr><td>             PEAR_LOG_INFO       </td><td>       Informational           </td></tr>
 * <tr><td>             PEAR_LOG_DEBUG      </td><td>       Debug-level messages    </td></tr></table>
 * @param string $message text to log
 */
function rc_sys_log($level, $message) {
  if (DEPLOYMENT_ENVIRONMENT=='PRODUCTION') {
    $sys_logger = get_sys_logger();   

    $sys_logger->log($message, $level);
  }
}

/**
 * Logs a message to the Riders Club log directory.
 * Levels are the same as for rc_sys_log
 */
function rc_log($level, $message, $filename = 'rc_log.log') {
//  if (DEPLOYMENT_ENVIRONMENT=='PRODUCTION') {
    $file_logger = get_file_logger($filename);
    
    $file_logger->log($message, $level);
//  }
}

function debug_string_backtrace() { 
    ob_start(); 
    debug_print_backtrace(); 
    $trace = ob_get_contents(); 
    ob_end_clean(); 

    // Remove first item from backtrace as it's this function which 
    // is redundant. 
    $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1); 

    // Renumber backtrace items. 
    $trace = preg_replace ('/^#(\d+)/m', '\'#\' . ($1 - 1)', $trace); 

    return $trace; 
} 

/**
 * Logs a database error to the db_error_log.log file.
 * Levels are the same as for rc_sys_log.
 * @param $level error level
 * @param $db_error Error from DB - e.g. mysql_error()
 * @param $extra_info Context or other info from the function that experienced the error
 * @param $sql optional parameter containing the guilty SQL
 * @return nothing
 */
function rc_log_db_error($level, $db_error, $extra_info, $sql = 'no sql') {
//  if (DEPLOYMENT_ENVIRONMENT=='PRODUCTION') {
    $message = "[$extra_info] [$db_error] [$sql]\n";
    $message .= debug_string_backtrace();
    rc_log($level, $message, 'db_error_log.log');
//  }
}

/**
 * Gets a logger instance to write to a file in the Riders Club log directory.  
 * 
 * The logger will be configured to log up to PEAR_LOG_DEBUG level in development, and
 * up to PEAR_LOG_INFO in other environments.  If we ever decide to keep a "max level" in 
 * riders_club_params, we will need to use either a string or numeric representation of the 
 * level, then translate it to PEAR_LOG_xyz here (because riders_club_params will most 
 * likely not have included Log.php).
 *
 * @return Log class instance
 */
function get_file_logger($filename) {
//  if (DEPLOYMENT_ENVIRONMENT=='PRODUCTION') {
    $configured_instance_name = (defined(FILELOG_INSTANCE_NAME)) ? FILELOG_INSTANCE_NAME : 'RC_LOG';
    $max_log_level = (DEPLOYMENT_ENVIRONMENT == 'DEVELOPMENT') ? PEAR_LOG_DEBUG : PEAR_LOG_INFO;
    $file_with_path = RC_LOG_PATH . $filename;
    // TODO:  Trim/look for abs/relative  
    
    $logger = &Log::singleton('file', $file_with_path, $configured_instance_name, '', $max_log_level);
    return $logger;    
//  }
}


// TODO:  make this configurable enough that different dev environments can use 
//        different loggers


/**
 * Writes a log to a configurable logger (syslog, Firebug, mail, etc)
 *
 * @param Log Level $level (PEAR_LOG_XYZ, where XYZ is generally ERR, WARNING, NOTICE, INFO, DEBUG)
 *                         NOTICE is "normal but significant", INFO is informational.
 * @param string $message message to log
 * @param string $type logger type (e.g. 'syslog', 'firebug', 'file', etc)
 * @param string $ident log identity (e.g. 'RC_LOG')
 * @param string $name Name of the log file, table, etc to use
 * @param Array $conf configuration hash for the desired type
 */
function rc_development_log($level, $message, $type, $ident = 'DEV_LOG', $name = '', $conf = array()) {
  if (DEPLOYMENT_ENVIRONMENT=='PRODUCTION') {
    if (DEPLOYMENT_ENVIRONMENT != "DEVELOPMENT") {
        rc_log(PEAR_LOG_WARNING, "DEVELOPMENT LOG CALL LEFT IN!  (Original at $level:  $message)");
    } else {
        $logger = &Log::singleton($type, $name, $ident, $conf);
        $logger->log($message, $level);
    }
  }
}

?>
