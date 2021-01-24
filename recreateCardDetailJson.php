

<?php
define('SL_DEBUG', false);

require_once("slreplayparser/replayparser.inc.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."slreplayparser".DIRECTORY_SEPARATOR."cardbasedetails".DIRECTORY_SEPARATOR."cardbasedetails.inc.php");

$cardbase = SkylordsCardbase::getInstance();
$cardbase->loadCards();

$cardbasedetails = SkylordsCardbaseDetails::getInstance();
$cardbasedetails->recreateFromCardbase();
