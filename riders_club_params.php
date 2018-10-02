<?php 
/** 
  Preferences and parameters for the Riders Club website.  
  When it makes sense to do so, use all-caps variable names
  with a double-underscore prefix, e.g. $__MY_PREF.

  Consider keeping a custom version of this available on your 
  dev/test server.

  // TODO:  Change these to constants.  Silly to start with these as vars.

  */

define('DEPLOYMENT_ENVIRONMENT', 'DEVELOPMENT');
#const DEPLOYMENT_ENVIRONMENT = 'PRODUCTION';

/** Database server */
$__DB_HOSTNAME = 'rca-main-db.myridersclub.com';
$__DB_HOSTNAME = 'ridersclub-test-db.myridersclub.com';
//$__DB_HOSTNAME = 'localhost';   If using your local server, uncomment this line.

/** Database Name */
$__DB_NAME = 'ridersclubmain';
$__DB_NAME = 'ridersclubtestdb';

/** Read-Write database user and password */
$__DB_RW_USER = 'ridersclubdb';
$__DB_RW_USER = 'testrcadb';
//$__DB_RW_USER = 'devrca';
//$__DB_RW_USER = 'root';
$__DB_RW_PASS = 'Pi=3.1416';
$__DB_RW_PASS = 'rcatestRCa';
//$__DB_RW_PASS = 'rcadevRCa';
//$__DB_RW_PASS = 'root';


/** USPS WebTools Username */
$__USPS_WEBTOOLS_USERNAME = '493WESTC5514';
$__USPS_WEBTOOLS_SERVER = 'http://testing.shippingapis.com';
$__USPS_WEBTOOLS_SERVER = 'http://production.shippingapis.com';

/** Log Constants */
#const RC_LOG_PATH = '/home/ridersclubuser/rc_logs/test_site/';
#const RC_LOG_PATH = '/home/ridersclubuser/rc_logs/production/';
//define('RC_LOG_PATH', '/home/matt/projects/ridersclub/rc_logs/');
define('RC_LOG_PATH', '/home/rcauser/rc_logs/test/');
define('FILELOG_INSTANCE_NAME', 'RC_LOG');  // For IDs in the log

if (DEPLOYMENT_ENVIRONMENT == 'PRODUCTION') {
    define('DEFAULT_EMAIL_FROM', "From: Riders Club of America <admin@myridersclub.com>\r\nCc: Admin Copy <admin@myridersclub.com>\r\n");
    define('DEFAULT_ADMIN_EMAIL', "admin@myridersclub.com");
    define('DEFAULT_CORD_EMAIL', "cord@myridersclub.com");
} else {
    define('DEFAULT_EMAIL_FROM', "From: Riders Club of America <admin@myridersclub.com>");
    define('DEFAULT_ADMIN_EMAIL', "joelwbixby@gmail.com");
    define('DEFAULT_CORD_EMAIL', "joelwbixby@gmail.com");
}

?>
