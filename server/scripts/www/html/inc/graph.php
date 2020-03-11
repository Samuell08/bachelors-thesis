<?php
// GRAPH.php
// PHP code to be called from visual.php

// get session variables
session_start();
$db_server          = $_SESSION["db_server"];
$db_user            = $_SESSION["db_user"];
$db_pass            = $_SESSION["db_pass"];
$db_source          = $_SESSION["db_source"];
$timeperiod         = $_SESSION["timeperiod"];
$timeperiod_format  = $_SESSION["timeperiod_format"];
$showwlan           = $_SESSION["showwlan"];
$showbt             = $_SESSION["showbt"];

// check if user input is correct
if ($db_source == NULL) {
  echo "<p class=\"warning\">Source database(s) not selected.</p>";
} elseif ($timeperiod_format == NULL) {
  echo "<p class=\"warning\">Time Period format Minute(s)/Hour(s) not selected.</p>";
} elseif ($timeperiod == NULL) {
  echo "<p class=\"warning\">Invalid time period.</p>";
} elseif ((!($showwlan == "1")) and (!($showbt == "1"))) {
  echo "<p class=\"warning\">No data selected to show.</p>";
} else {
  echo "graph OK";
}

?>
