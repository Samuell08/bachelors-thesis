<?php
// PHP code to process settings form in pass_history.php

$settingsok = "1";

// form variables
$db_source_ph           = "";
$time_from_ph           = "";
$time_to_ph             = "";
$time_step_ph           = "";
$time_step_format_ph    = "";
$threshold_ph           = "";
$threshold_format_ph    = "";
$timestamp_limit_chk_ph = "";
$timestamp_limit_ph     = "";
$show_wlan_ph           = "";
$show_bt_ph             = "";
$show_wlan_a_ph         = "";
$show_wlan_bg_ph        = "";
$blacklist_wlan_chk_ph  = "";
$blacklist_wlan_ph      = "";
$blacklist_fp_chk_ph    = "";
$blacklist_fp_ph        = "";
$blacklist_bt_chk_ph    = "";
$blacklist_bt_ph        = "";
$specific_mac_chk_ph    = "";
$specific_mac_ph        = "";
$specific_fp_chk_ph     = "";
$specific_fp_ph         = "";
$specific_bt_chk_ph     = "";
$specific_bt_ph         = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source_ph           = $_GET["db_source_ph"];
  $time_from_ph           = filter_var($_GET["time_from_ph"], FILTER_SANITIZE_STRING);
  $time_to_ph             = filter_var($_GET["time_to_ph"], FILTER_SANITIZE_STRING);
  $time_step_ph           = filter_var($_GET["time_step_ph"], FILTER_VALIDATE_INT);
  $time_step_format_ph    = $_GET["time_step_format_ph"];
  $threshold_ph           = filter_var($_GET["threshold_ph"], FILTER_VALIDATE_INT);
  $threshold_format_ph    = $_GET["threshold_format_ph"];
  $timestamp_limit_chk_ph = $_GET["timestamp_limit_chk_ph"];
  $timestamp_limit_ph     = filter_var($_GET["timestamp_limit_ph"], FILTER_VALIDATE_INT);
  $show_wlan_ph           = $_GET["show_wlan_ph"];
  $show_bt_ph             = $_GET["show_bt_ph"];
  $show_wlan_a_ph         = $_GET["show_wlan_a_ph"];
  $show_wlan_bg_ph        = $_GET["show_wlan_bg_ph"];
  $blacklist_wlan_chk_ph  = $_GET["blacklist_wlan_chk_ph"];
  $blacklist_wlan_ph      = filter_var($_GET["blacklist_wlan_ph"], FILTER_SANITIZE_STRING);
  $blacklist_fp_chk_ph    = $_GET["blacklist_fp_chk_ph"];
  $blacklist_fp_ph        = filter_var($_GET["blacklist_fp_ph"], FILTER_SANITIZE_STRING);
  $blacklist_bt_chk_ph    = $_GET["blacklist_bt_chk_ph"];
  $blacklist_bt_ph        = filter_var($_GET["blacklist_bt_ph"], FILTER_SANITIZE_STRING);
  $specific_mac_chk_ph    = $_GET["specific_mac_chk_ph"];
  $specific_mac_ph        = filter_var($_GET["specific_mac_ph"], FILTER_SANITIZE_STRING);
  $specific_fp_chk_ph     = $_GET["specific_fp_chk_ph"];
  $specific_fp_ph         = filter_var($_GET["specific_fp_ph"], FILTER_SANITIZE_STRING);
  $specific_bt_chk_ph     = $_GET["specific_bt_chk_ph"];
  $specific_bt_ph         = filter_var($_GET["specific_bt_ph"], FILTER_SANITIZE_STRING);

  // default values
  $today = date('Y-m-d');
  $now   = date('H:i:s');
  if ($time_from_ph == "")           { $time_from_ph = "$today" . " 00:00:00"; }
  if ($time_to_ph == "")             { $time_to_ph = "$today" . " $now"; }
  if ($time_step_ph == "")           { $time_step_ph = 1; }
  if ($time_step_format_ph == "")    { $time_step_format_ph = "HOUR"; }
  if ($threshold_ph == "")           { $threshold_ph = 10; }
  if ($threshold_format_ph == "")    { $threshold_format_ph = "MINUTE"; }
  if ($timestamp_limit_ph == "")     { $timestamp_limit_ph = 100; }
  if ($blacklist_wlan_ph == "")      { $blacklist_wlan_ph = "AA:AA:AA:AA:AA:AA,BB:BB:BB:BB:BB:BB,CC:CC:CC:CC:CC:CC"; }
  if ($blacklist_fp_ph == "")        { $blacklist_fp_ph = "eduroam,vutbrno,fekthost,DPMBfree"; }
  if ($blacklist_bt_ph == "")        { $blacklist_bt_ph = "AA:AA:AA:AA:AA:AA,BB:BB:BB:BB:BB:BB,CC:CC:CC:CC:CC:CC"; }
  if ($specific_mac_ph == "")        { $specific_mac_ph = "AA:AA:AA:AA:AA:AA,BB:BB:BB:BB:BB:BB,CC:CC:CC:CC:CC:CC"; }
  if ($specific_fp_ph == "")         { $specific_fp_ph = "aaa,bbb,ccc"; }
  if ($specific_bt_ph == "")         { $specific_bt_ph = "AA:AA:AA:AA:AA:AA,BB:BB:BB:BB:BB:BB,CC:CC:CC:CC:CC:CC"; }
  
  // store variables in session
  $_SESSION["db_source_ph"]           = $db_source_ph;
  $_SESSION["time_from_ph"]           = $time_from_ph;
  $_SESSION["time_to_ph"]             = $time_to_ph;
  $_SESSION["time_step_ph"]           = $time_step_ph;
  $_SESSION["time_step_format_ph"]    = $time_step_format_ph;
  $_SESSION["threshold_ph"]           = $threshold_ph;
  $_SESSION["threshold_format_ph"]    = $threshold_format_ph;
  $_SESSION["timestamp_limit_chk_ph"] = $timestamp_limit_chk_ph;
  $_SESSION["timestamp_limit_ph"]     = $timestamp_limit_ph;
  $_SESSION["show_wlan_ph"]           = $show_wlan_ph;
  $_SESSION["show_bt_ph"]             = $show_bt_ph;
  $_SESSION["show_wlan_a_ph"]         = $show_wlan_a_ph;
  $_SESSION["show_wlan_bg_ph"]        = $show_wlan_bg_ph;
  $_SESSION["blacklist_wlan_chk_ph"]  = $blacklist_wlan_chk_ph;
  $_SESSION["blacklist_wlan_ph"]      = $blacklist_wlan_ph;
  $_SESSION["blacklist_fp_chk_ph"]    = $blacklist_fp_chk_ph;
  $_SESSION["blacklist_fp_ph"]        = $blacklist_fp_ph;
  $_SESSION["blacklist_bt_chk_ph"]    = $blacklist_bt_chk_ph;
  $_SESSION["blacklist_bt_ph"]        = $blacklist_bt_ph;
  $_SESSION["specific_mac_chk_ph"]    = $specific_mac_chk_ph;
  $_SESSION["specific_mac_ph"]        = $specific_mac_ph;
  $_SESSION["specific_fp_chk_ph"]     = $specific_fp_chk_ph;
  $_SESSION["specific_fp_ph"]         = $specific_fp_ph;
  $_SESSION["specific_bt_chk_ph"]     = $specific_bt_chk_ph;
  $_SESSION["specific_bt_ph"]         = $specific_bt_ph;
}
?>
