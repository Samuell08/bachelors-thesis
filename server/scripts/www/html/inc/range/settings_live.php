<?php
// PHP code to process settings form in range_live.php

$settingsok = "1";

// form variables
$db_source_rl           = "";
$time_delay_rl          = "";
$time_delay_format_rl   = "";
$time_period_rl         = "";
$time_period_format_rl  = "";
$show_wlan_rl           = "";
$show_bt_rl             = "";
$show_wlan_a_rl         = "";
$show_wlan_bg_rl        = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source_rl           = $_GET["db_source_rl"];
  $time_delay_rl          = filter_var($_GET["time_delay_rl"], FILTER_VALIDATE_INT);
  $time_delay_format_rl   = $_GET["time_delay_format_rl"];
  $time_period_rl         = filter_var($_GET["time_period_rl"], FILTER_VALIDATE_INT);
  $time_period_format_rl  = $_GET["time_period_format_rl"];
  $show_wlan_rl           = $_GET["show_wlan_rl"];
  $show_bt_rl             = $_GET["show_bt_rl"];
  $show_wlan_a_rl         = $_GET["show_wlan_a_rl"];
  $show_wlan_bg_rl        = $_GET["show_wlan_bg_rl"];
  
  // default values
  if ($time_delay_rl == "")         { $time_delay_rl = 30; }
  if ($time_delay_format_rl == "")  { $time_delay_format_rl = "SECOND"; }
  if ($time_period_rl == "")        { $time_period_rl = 15; }
  if ($time_period_format_rl == "") { $time_period_format_rl = "MINUTE"; }
  
  // store variables in session
  $_SESSION["db_source_rl"]           = $db_source_rl;
  $_SESSION["time_delay_rl"]          = $time_delay_rl;
  $_SESSION["time_delay_format_rl"]   = $time_delay_format_rl;
  $_SESSION["time_period_rl"]         = $time_period_rl;
  $_SESSION["time_period_format_rl"]  = $time_period_format_rl;
  $_SESSION["show_wlan_rl"]           = $show_wlan_rl;
  $_SESSION["show_bt_rl"]             = $show_bt_rl;
  $_SESSION["show_wlan_a_rl"]         = $show_wlan_a_rl;
  $_SESSION["show_wlan_bg_rl"]        = $show_wlan_bg_rl;
}
?>
