<?php
// SETTINGS.php
// PHP code to be expanded in visual.php

$settingsok="1";

// form variables
$db_source="";
$timeperiod="";
$refresh="";
$showwlan="";
$showbt="";

// receive settings form
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $db_source  = $_GET["db_source"];
  $timeperiod = filter_var($_GET["timeperiod"], FILTER_VALIDATE_INT);
  $refresh    = filter_var($_GET["refresh"], FILTER_VALIDATE_INT);
  $showwlan   = $_GET["showwlan"];
  $showbt     = $_GET["showbt"];
  // store variables in session for TEXTOUTPUT.php
  $_SESSION["db_source"]  = $db_source;
  $_SESSION["timeperiod"] = $timeperiod;
}

// dynamic form content
if (!$db_conn) {
  echo "<p style=\"color:OrangeRed;font-weight:bold\">Database not connected!</p>";
} else {
  // show available DB
  echo "<b>Select source database(s)</b><br>";
  $db_q = "SHOW DATABASES LIKE 'rpi_mon_%';";
  $db_result = mysqli_query($db_conn, $db_q);

  echo "<table class=\"t_avail_db\">";
  if (mysqli_num_rows($db_result) > 0) {
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      // table row name
      echo "<tr><td>" . "<i>" . $db_row["Database (rpi_mon_%)"] . "</i>" . "</td>";
      // table row checkbox
      echo "<td>" . "<input type=\"checkbox\" name=\"db_source[]\" value=\"" . $db_row["Database (rpi_mon_%)"] . "\"";
        // keep checked?
        foreach ($db_source as $key => $val) {
          if ($val == $db_row["Database (rpi_mon_%)"]) { echo "checked"; }
        }
      echo ">" . "</td></tr>";
    }
  } else {
    echo "0 available databases<br>";
  }
  echo "</table>";
}
?>
