<?php
// PHP code to process settings form in range_history.php

$settingsok = "1";

// form variables
$db_source_history          = "";
$time_from                  = "";
$time_to                    = "";
$time_step                  = "";
$time_step_format           = "";
$timeperiod_history         = "";
$timeperiod_format_history  = "";
$showwlan_history           = "";
$showbt_history             = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source_history          = $_GET["db_source_history"];
  $time_from                  = filter_var($_GET["time_from"], FILTER_SANITIZE_STRING);
  $time_to                    = filter_var($_GET["time_to"], FILTER_SANITIZE_STRING);
  $time_step                  = filter_var($_GET["time_step"], FILTER_VALIDATE_INT);
  $time_step_format           = $_GET["time_step_format"];
  $timeperiod_history         = filter_var($_GET["timeperiod_history"], FILTER_VALIDATE_INT);
  $timeperiod_format_history  = $_GET["timeperiod_format_history"];
  $showwlan_history           = $_GET["showwlan_history"];
  $showbt_history             = $_GET["showbt_history"];

  // default values
  $today = date('Y-m-d');
  $now   = date('H:i:s');
  if ($time_from == "")                 { $time_from = "$today" . " 04:00:00"; }
  if ($time_to == "")                   { $time_to = "$today" . " $now"; }
  if ($time_step == "")                 { $time_step = 1; }
  if ($time_step_format == "")          { $time_step_format = "MINUTE"; }
  if ($timeperiod_history == "")        { $timeperiod_history = 15; }
  if ($timeperiod_format_history == "") { $timeperiod_format_history = "MINUTE"; }
  
  // store variables in session
  $_SESSION["db_source_history"]          = $db_source_history;
  $_SESSION["time_from"]                  = $time_from;
  $_SESSION["time_to"]                    = $time_to;
  $_SESSION["time_step"]                  = $time_step;
  $_SESSION["time_step_format"]           = $time_step_format;
  $_SESSION["timeperiod_history"]         = $timeperiod_history;
  $_SESSION["timeperiod_format_history"]  = $timeperiod_format_history;
  $_SESSION["showwlan_history"]           = $showwlan_history;
  $_SESSION["showbt_history"]             = $showbt_history;
}
?>
