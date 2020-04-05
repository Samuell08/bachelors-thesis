<?php
// SETTINGS.php
// PHP code to be expanded in range_live.php and range_history.php

$settingsok = "1";

// form variables
$db_source  = "";
$timeperiod = "";
$showwlan   = "";
$showbt     = "";
$time_from  = "";
$time_to    = "";
$time_step  = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source          = $_GET["db_source"];
  $timeperiod         = filter_var($_GET["timeperiod"], FILTER_VALIDATE_INT);
  $timeperiod_format  = $_GET["timeperiod_format"];
  $refresh            = filter_var($_GET["refresh"], FILTER_VALIDATE_INT);
  $refresh_format     = $_GET["refresh_format"];
  $showwlan           = $_GET["showwlan"];
  $showbt             = $_GET["showbt"];

  $time_from          = filter_var($_GET["time_from"], FILTER_SANITIZE_STRING);
  $time_to            = filter_var($_GET["time_to"], FILTER_SANITIZE_STRING);
  $time_step          = filter_var($_GET["time_step"], FILTER_VALIDATE_INT);
  $time_step_format   = $_GET["time_step_format"];
  
  // default values
  if ($timeperiod == "") { $timeperiod = 15; }
  if ($timeperiod_format == "") { $timeperiod_format = "MINUTE"; }
  $today = date('Y-m-d');
  $now = date('H:i:s');
  if ($time_from == "") { $time_from = "$today" . " 04:00:00"; }
  if ($time_to == "") { $time_to = "$today" . " $now"; }
  if ($time_step == "") { $time_step = 1; }
  if ($time_step_format == "") { $time_step_format = "MINUTE"; }
  
  // store variables in session
  $_SESSION["db_source"]          = $db_source;
  $_SESSION["timeperiod"]         = $timeperiod;
  $_SESSION["timeperiod_format"]  = $timeperiod_format;
  $_SESSION["showwlan"]           = $showwlan;
  $_SESSION["showbt"]             = $showbt;
  $_SESSION["time_from"]          = $time_from;
  $_SESSION["time_to"]            = $time_to;
  $_SESSION["time_step"]          = $time_step;
  $_SESSION["time_step_format"]   = $time_step_format;
}

// DYNAMIC FORM PART
// Select Source Database(s)
echo "<b>Select Source Database(s)</b><br>";
echo "<table class=\"form\">";

if (!$db_conn) {
  echo "<tr><td>" . "<p class=\"error\">Database not connected!</p>" . "</td></tr>";
} else {
  $db_q = "SHOW DATABASES LIKE 'rpi_mon_%';";
  $db_result = mysqli_query($db_conn, $db_q);
  
  if (mysqli_num_rows($db_result) > 0) {
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      // table row checkbox
      echo "<tr><td>" . "<input type=\"checkbox\" name=\"db_source[]\" value=\"" . $db_row["Database (rpi_mon_%)"] . "\"";
        // keep checked?
        foreach ($db_source as $key => $val) {
          if ($val == $db_row["Database (rpi_mon_%)"]) { echo "checked"; }
        }
      echo ">" . "</td>";
      // table row name
      echo "<td>" . $db_row["Database (rpi_mon_%)"] . "</td></tr>";
    }
  } else {
    echo "<tr><td>" . "0 available databases" . "</td></tr>";
  }
}

echo "</table>";
?>
