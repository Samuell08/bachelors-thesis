<?php
// PHP code to process settings form in range_history.php

$settingsok = "1";

// form variables
$db_source          = "";
$time_from          = "";
$time_to            = "";
$time_step          = "";
$time_step_format   = "";
$timeperiod         = "";
$timeperiod_format  = "";
$showwlan           = "";
$showbt             = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source          = $_GET["db_source"];
  $time_from          = filter_var($_GET["time_from"], FILTER_SANITIZE_STRING);
  $time_to            = filter_var($_GET["time_to"], FILTER_SANITIZE_STRING);
  $time_step          = filter_var($_GET["time_step"], FILTER_VALIDATE_INT);
  $time_step_format   = $_GET["time_step_format"];
  $timeperiod         = filter_var($_GET["timeperiod"], FILTER_VALIDATE_INT);
  $timeperiod_format  = $_GET["timeperiod_format"];
  $showwlan           = $_GET["showwlan"];
  $showbt             = $_GET["showbt"];

  // default values
  $today = date('Y-m-d');
  $now   = date('H:i:s');
  if ($time_from == "")         { $time_from = "$today" . " 04:00:00"; }
  if ($time_to == "")           { $time_to = "$today" . " $now"; }
  if ($time_step == "")         { $time_step = 1; }
  if ($time_step_format == "")  { $time_step_format = "MINUTE"; }
  if ($timeperiod == "")        { $timeperiod = 15; }
  if ($timeperiod_format == "") { $timeperiod_format = "MINUTE"; }
  
  // store variables in session
  $_SESSION["db_source"]          = $db_source;
  $_SESSION["time_from"]          = $time_from;
  $_SESSION["time_to"]            = $time_to;
  $_SESSION["time_step"]          = $time_step;
  $_SESSION["time_step_format"]   = $time_step_format;
  $_SESSION["timeperiod"]         = $timeperiod;
  $_SESSION["timeperiod_format"]  = $timeperiod_format;
  $_SESSION["showwlan"]           = $showwlan;
  $_SESSION["showbt"]             = $showbt;
}
?>
