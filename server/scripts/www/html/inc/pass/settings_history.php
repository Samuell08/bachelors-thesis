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
$specific_addr_chk_ph   = "";
$specific_addr_ph       = "";

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
  $specific_addr_chk_ph   = $_GET["specific_addr_chk_ph"];
  $specific_addr_ph       = filter_var($_GET["specific_addr_ph"], FILTER_SANITIZE_STRING);

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
  if ($specific_addr_ph == "")       { $specific_addr_ph = "12:34:56:AB:CD:EF"; }
  
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
  $_SESSION["specific_addr_chk_ph"]   = $specific_addr_chk_ph;
  $_SESSION["specific_addr_ph"]       = $specific_addr_ph;
}
?>
