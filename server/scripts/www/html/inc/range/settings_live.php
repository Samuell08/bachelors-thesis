<?php
// PHP code to process settings form in range_live.php

$settingsok = "1";

// form variables
$db_source  = "";
$timeperiod = "";
$showwlan   = "";
$showbt     = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source          = $_GET["db_source"];
  $timeperiod         = filter_var($_GET["timeperiod"], FILTER_VALIDATE_INT);
  $timeperiod_format  = $_GET["timeperiod_format"];
  $showwlan           = $_GET["showwlan"];
  $showbt             = $_GET["showbt"];
  
  // default values
  if ($timeperiod == "")        { $timeperiod = 15; }
  if ($timeperiod_format == "") { $timeperiod_format = "MINUTE"; }
  
  // store variables in session
  $_SESSION["db_source"]          = $db_source;
  $_SESSION["timeperiod"]         = $timeperiod;
  $_SESSION["timeperiod_format"]  = $timeperiod_format;
  $_SESSION["showwlan"]           = $showwlan;
  $_SESSION["showbt"]             = $showbt;
}
?>
