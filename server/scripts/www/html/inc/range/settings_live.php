<?php
// PHP code to process settings form in range_live.php

$settingsok = "1";

// form variables
$db_source_live         = "";
$timeperiod_live        = "";
$timeperiod_format_live = "";
$showwlan_live          = "";
$showbt_live            = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source_live          = $_GET["db_source_live"];
  $timeperiod_live         = filter_var($_GET["timeperiod_live"], FILTER_VALIDATE_INT);
  $timeperiod_format_live  = $_GET["timeperiod_format_live"];
  $showwlan_live           = $_GET["showwlan_live"];
  $showbt_live             = $_GET["showbt_live"];
  
  // default values
  if ($timeperiod_live == "")        { $timeperiod_live = 15; }
  if ($timeperiod_format_live == "") { $timeperiod_format_live = "MINUTE"; }
  
  // store variables in session
  $_SESSION["db_source_live"]          = $db_source_live;
  $_SESSION["timeperiod_live"]         = $timeperiod_live;
  $_SESSION["timeperiod_format_live"]  = $timeperiod_format_live;
  $_SESSION["showwlan_live"]           = $showwlan_live;
  $_SESSION["showbt_live"]             = $showbt_live;
}
?>
