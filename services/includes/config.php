<?php 
define("SITE_URL","");
define("BASE_PATH","");
define("SITE_NAME","To-do List");
define("DB_HOST","localhost");
define("DB_USER","root");
define("DB_PASS","");
define("DB_NAME","todo_list");
define("SEND_ERRORS_TO","");
define("DISPLAY_DEBUG", false );
error_reporting(1);
ini_set('display_errors', 1);
date_default_timezone_set("Asia/Kolkata");
//Database
require_once('classes/class.db.php');
require_once('classes/dashboard.php');
$db = new DB();
?>