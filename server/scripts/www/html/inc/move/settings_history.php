<?php
// PHP code to process settings form in move_history.php

$settingsok = "1";

// form variables
$db_source_A_mh         = "";
$db_source_B_mh         = "";
$time_from_mh           = "";
$time_to_mh             = "";
$time_step_mh           = "";
$time_step_format_mh    = "";
$threshold_mh           = "";
$threshold_format_mh    = "";
$power_limit_chk_mh     = "";
$power_limit_mh         = "";
$timestamp_limit_chk_mh = "";
$timestamp_limit_mh     = "";
$show_wlan_mh           = "";
$show_bt_mh             = "";
$show_wlan_a_mh         = "";
$show_wlan_bg_mh        = "";
$blacklist_wlan_chk_mh  = "";
$blacklist_wlan_mh      = "";
$blacklist_fp_chk_mh    = "";
$blacklist_mode_fp_mh   = "";
$blacklist_fp_mh        = "";
$blacklist_bt_chk_mh    = "";
$blacklist_bt_mh        = "";
$specific_mac_chk_mh    = "";
$specific_mac_mh        = "";
$specific_fp_chk_mh     = "";
$specific_mode_fp_mh    = "";
$specific_fp_mh         = "";
$specific_bt_chk_mh     = "";
$specific_bt_mh         = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source_A_mh         = $_GET["db_source_A_mh"];
  $db_source_B_mh         = $_GET["db_source_B_mh"];
  $time_from_mh           = filter_var($_GET["time_from_mh"], FILTER_SANITIZE_STRING);
  $time_to_mh             = filter_var($_GET["time_to_mh"], FILTER_SANITIZE_STRING);
  $time_step_mh           = filter_var($_GET["time_step_mh"], FILTER_VALIDATE_INT);
  $time_step_format_mh    = $_GET["time_step_format_mh"];
  $threshold_mh           = filter_var($_GET["threshold_mh"], FILTER_VALIDATE_INT);
  $threshold_format_mh    = $_GET["threshold_format_mh"];
  $power_limit_chk_mh     = $_GET["power_limit_chk_mh"];
  $power_limit_mh         = filter_var($_GET["power_limit_mh"], FILTER_VALIDATE_INT);
  $timestamp_limit_chk_mh = $_GET["timestamp_limit_chk_mh"];
  $timestamp_limit_mh     = filter_var($_GET["timestamp_limit_mh"], FILTER_VALIDATE_INT);
  $show_wlan_mh           = $_GET["show_wlan_mh"];
  $show_bt_mh             = $_GET["show_bt_mh"];
  $show_wlan_a_mh         = $_GET["show_wlan_a_mh"];
  $show_wlan_bg_mh        = $_GET["show_wlan_bg_mh"];
  $blacklist_wlan_chk_mh  = $_GET["blacklist_wlan_chk_mh"];
  $blacklist_wlan_mh      = filter_var($_GET["blacklist_wlan_mh"], FILTER_SANITIZE_STRING);
  $blacklist_fp_chk_mh    = $_GET["blacklist_fp_chk_mh"];
  $blacklist_mode_fp_mh   = $_GET["blacklist_mode_fp_mh"];
  $blacklist_fp_mh        = filter_var($_GET["blacklist_fp_mh"], FILTER_SANITIZE_STRING);
  $blacklist_bt_chk_mh    = $_GET["blacklist_bt_chk_mh"];
  $blacklist_bt_mh        = filter_var($_GET["blacklist_bt_mh"], FILTER_SANITIZE_STRING);
  $specific_mac_chk_mh    = $_GET["specific_mac_chk_mh"];
  $specific_mac_mh        = filter_var($_GET["specific_mac_mh"], FILTER_SANITIZE_STRING);
  $specific_fp_chk_mh     = $_GET["specific_fp_chk_mh"];
  $specific_mode_fp_mh    = $_GET["specific_mode_fp_mh"];
  $specific_fp_mh         = filter_var($_GET["specific_fp_mh"], FILTER_SANITIZE_STRING);
  $specific_bt_chk_mh     = $_GET["specific_bt_chk_mh"];
  $specific_bt_mh         = filter_var($_GET["specific_bt_mh"], FILTER_SANITIZE_STRING);

  // default values
  $today = date('Y-m-d');
  $now   = date('H:i:s');
  if ($time_from_mh == "")           { $time_from_mh = "$today" . " 00:00:00"; }
  if ($time_to_mh == "")             { $time_to_mh = "$today" . " $now"; }
  if ($time_step_mh == "")           { $time_step_mh = 1; }
  if ($time_step_format_mh == "")    { $time_step_format_mh = "HOUR"; }
  if ($threshold_mh == "")           { $threshold_mh = 10; }
  if ($threshold_format_mh == "")    { $threshold_format_mh = "MINUTE"; }
  if ($power_limit_mh == "")         { $power_limit_mh = -70; }
  if ($timestamp_limit_mh == "")     { $timestamp_limit_mh = 100; }
  if ($blacklist_wlan_mh == "")      { $blacklist_wlan_mh = "AA:AA:AA:AA:AA:AA,BB:BB:BB:BB:BB:BB,CC:CC:CC:CC:CC:CC"; }
  if ($blacklist_mode_fp_mh == "")   { $blacklist_mode_fp_mh = "ALL"; }
  if ($blacklist_fp_mh == "")        { $blacklist_fp_mh = "eduroam,vutbrno,fekthost,DPMBfree"; }
  if ($blacklist_bt_mh == "")        { $blacklist_bt_mh = "AA:AA:AA:AA:AA:AA,BB:BB:BB:BB:BB:BB,CC:CC:CC:CC:CC:CC"; }
  if ($specific_mac_mh == "")        { $specific_mac_mh = "AA:AA:AA:AA:AA:AA,BB:BB:BB:BB:BB:BB,CC:CC:CC:CC:CC:CC"; }
  if ($specific_mode_fp_mh == "")    { $specific_mode_fp_mh = "EXACT"; }
  if ($specific_fp_mh == "")         { $specific_fp_mh = "aaa,bbb,ccc"; }
  if ($specific_bt_mh == "")         { $specific_bt_mh = "AA:AA:AA:AA:AA:AA,BB:BB:BB:BB:BB:BB,CC:CC:CC:CC:CC:CC"; }
  
  // store variables in session
  $_SESSION["db_source_A_mh"]         = $db_source_A_mh;
  $_SESSION["db_source_B_mh"]         = $db_source_B_mh;
  $_SESSION["time_from_mh"]           = $time_from_mh;
  $_SESSION["time_to_mh"]             = $time_to_mh;
  $_SESSION["time_step_mh"]           = $time_step_mh;
  $_SESSION["time_step_format_mh"]    = $time_step_format_mh;
  $_SESSION["threshold_mh"]           = $threshold_mh;
  $_SESSION["threshold_format_mh"]    = $threshold_format_mh;
  $_SESSION["power_limit_chk_mh"]     = $power_limit_chk_mh;
  $_SESSION["power_limit_mh"]         = $power_limit_mh;
  $_SESSION["timestamp_limit_chk_mh"] = $timestamp_limit_chk_mh;
  $_SESSION["timestamp_limit_mh"]     = $timestamp_limit_mh;
  $_SESSION["show_wlan_mh"]           = $show_wlan_mh;
  $_SESSION["show_bt_mh"]             = $show_bt_mh;
  $_SESSION["show_wlan_a_mh"]         = $show_wlan_a_mh;
  $_SESSION["show_wlan_bg_mh"]        = $show_wlan_bg_mh;
  $_SESSION["blacklist_wlan_chk_mh"]  = $blacklist_wlan_chk_mh;
  $_SESSION["blacklist_wlan_mh"]      = $blacklist_wlan_mh;
  $_SESSION["blacklist_fp_chk_mh"]    = $blacklist_fp_chk_mh;
  $_SESSION["blacklist_mode_fp_mh"]   = $blacklist_mode_fp_mh;
  $_SESSION["blacklist_fp_mh"]        = $blacklist_fp_mh;
  $_SESSION["blacklist_bt_chk_mh"]    = $blacklist_bt_chk_mh;
  $_SESSION["blacklist_bt_mh"]        = $blacklist_bt_mh;
  $_SESSION["specific_mac_chk_mh"]    = $specific_mac_chk_mh;
  $_SESSION["specific_mac_mh"]        = $specific_mac_mh;
  $_SESSION["specific_fp_chk_mh"]     = $specific_fp_chk_mh;
  $_SESSION["specific_mode_fp_mh"]    = $specific_mode_fp_mh;
  $_SESSION["specific_fp_mh"]         = $specific_fp_mh;
  $_SESSION["specific_bt_chk_mh"]     = $specific_bt_chk_mh;
  $_SESSION["specific_bt_mh"]         = $specific_bt_mh;
}
?>
