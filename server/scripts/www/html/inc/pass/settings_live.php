<?php
// PHP code to process settings form in pass_live.php

$settingsok = "1";

// form variables
$db_source_pl           = "";
$time_period_pl         = "";
$time_period_format_pl  = "";
$show_wlan_pl           = "";
$show_bt_pl             = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source_pl           = $_GET["db_source_pl"];
  $time_period_pl         = filter_var($_GET["time_period_pl"], FILTER_VALIDATE_INT);
  $time_period_format_pl  = $_GET["time_period_format_pl"];
  $show_wlan_pl           = $_GET["show_wlan_pl"];
  $show_bt_pl             = $_GET["show_bt_pl"];
  
  // default values
  if ($time_period_pl == "")        { $time_period_pl = 15; }
  if ($time_period_format_pl == "") { $time_period_format_pl = "MINUTE"; }
  
  // store variables in session
  $_SESSION["db_source_pl"]           = $db_source_pl;
  $_SESSION["time_period_pl"]         = $time_period_pl;
  $_SESSION["time_period_format_pl"]  = $time_period_format_pl;
  $_SESSION["show_wlan_pl"]           = $show_wlan_pl;
  $_SESSION["show_bt_pl"]             = $show_bt_pl;
}
?>
