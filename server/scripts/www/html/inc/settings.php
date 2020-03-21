<?php
// SETTINGS.php
// PHP code to be expanded in live.php and history.php

$settingsok = "1";

// form variables
$db_source  = "";
$timeperiod = "";
$showwlan   = "";
$showbt     = "";
$time_since = "";
$time_until = "";

// RECEIVE SETTINGS FORM
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source          = $_GET["db_source"];
  $timeperiod         = filter_var($_GET["timeperiod"], FILTER_VALIDATE_INT);
  $timeperiod_format  = $_GET["timeperiod_format"];
  $refresh            = filter_var($_GET["refresh"], FILTER_VALIDATE_INT);
  $refresh_format     = $_GET["refresh_format"];
  $showwlan           = $_GET["showwlan"];
  $showbt             = $_GET["showbt"];

  $time_since         = filter_var($_GET["time_since"], FILTER_SANITIZE_STRING);
  $time_until         = filter_var($_GET["time_until"], FILTER_SANITIZE_STRING);
  
  // default values
  if ($timeperiod == "") { $timeperiod = 15; }
  if ($timeperiod_format == "") { $timeperiod_format = "MINUTE"; }
  if ($time_since == "") { $time_since = "2020-03-20 10:00:00"; }
  if ($time_until == "") { $time_until = "2020-03-20 12:00:00"; }
  
  // store variables in session
  $_SESSION["db_source"]          = $db_source;
  $_SESSION["timeperiod"]         = $timeperiod;
  $_SESSION["timeperiod_format"]  = $timeperiod_format;
  $_SESSION["showwlan"]           = $showwlan;
  $_SESSION["showbt"]             = $showbt;
  $_SESSION["time_since"]         = $time_since;
  $_SESSION["time_until"]         = $time_until;
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
