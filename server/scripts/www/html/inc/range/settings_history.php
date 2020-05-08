<?php
// PHP code to process settings form in range_history.php

$settingsok = "1";

// form variables
$db_source_rh          = "";
$time_from_rh          = "";
$time_to_rh            = "";
$time_step_rh          = "";
$time_step_format_rh   = "";
$time_period_rh        = "";
$time_period_format_rh = "";
$show_wlan_rh          = "";
$show_bt_rh            = "";
$show_wlan_a_rh        = "";
$show_wlan_bg_rh       = "";
$specific_addr_chk_rh  = "";
$specific_addr_rh      = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source_rh          = $_GET["db_source_rh"];
  $time_from_rh          = filter_var($_GET["time_from_rh"], FILTER_SANITIZE_STRING);
  $time_to_rh            = filter_var($_GET["time_to_rh"], FILTER_SANITIZE_STRING);
  $time_step_rh          = filter_var($_GET["time_step_rh"], FILTER_VALIDATE_INT);
  $time_step_format_rh   = $_GET["time_step_format_rh"];
  $time_period_rh        = filter_var($_GET["time_period_rh"], FILTER_VALIDATE_INT);
  $time_period_format_rh = $_GET["time_period_format_rh"];
  $show_wlan_rh          = $_GET["show_wlan_rh"];
  $show_bt_rh            = $_GET["show_bt_rh"];
  $show_wlan_a_rh        = $_GET["show_wlan_a_rh"];
  $show_wlan_bg_rh       = $_GET["show_wlan_bg_rh"];
  $specific_addr_chk_rh  = $_GET["specific_addr_chk_rh"];
  $specific_addr_rh      = $_GET["specific_addr_rh"];

  // default values
  $today = date('Y-m-d');
  $now   = date('H:i:s');
  if ($time_from_rh == "")          { $time_from_rh = "$today" . " 00:00:00"; }
  if ($time_to_rh == "")            { $time_to_rh = "$today" . " $now"; }
  if ($time_step_rh == "")          { $time_step_rh = 5; }
  if ($time_step_format_rh == "")   { $time_step_format_rh = "MINUTE"; }
  if ($time_period_rh == "")        { $time_period_rh = 15; }
  if ($time_period_format_rh == "") { $time_period_format_rh = "MINUTE"; }
  if ($specific_addr_rh == "")      { $specific_addr_rh = "12:34:56:AB:CD:EF"; }
  
  // store variables in session
  $_SESSION["db_source_rh"]          = $db_source_rh;
  $_SESSION["time_from_rh"]          = $time_from_rh;
  $_SESSION["time_to_rh"]            = $time_to_rh;
  $_SESSION["time_step_rh"]          = $time_step_rh;
  $_SESSION["time_step_format_rh"]   = $time_step_format_rh;
  $_SESSION["time_period_rh"]        = $time_period_rh;
  $_SESSION["time_period_format_rh"] = $time_period_format_rh;
  $_SESSION["show_wlan_rh"]          = $show_wlan_rh;
  $_SESSION["show_bt_rh"]            = $show_bt_rh;
  $_SESSION["show_wlan_a_rh"]        = $show_wlan_a_rh;
  $_SESSION["show_wlan_bg_rh"]       = $show_wlan_bg_rh;
  $_SESSION["specific_addr_chk_rh"]  = $specific_addr_chk_rh;
  $_SESSION["specific_addr_rh"]      = $specific_addr_rh;
}
?>
