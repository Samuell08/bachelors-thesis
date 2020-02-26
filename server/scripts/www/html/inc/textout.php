<?php
// TEXTOUT.php
// PHP code to be called from visual.php

// get session variables
session_start();
$db_server          = $_SESSION["db_server"];
$db_user            = $_SESSION["db_user"];
$db_pass            = $_SESSION["db_pass"];
$db_source          = $_SESSION["db_source"];
$timeperiod         = $_SESSION["timeperiod"];
$timeperiod_format  = $_SESSION["timeperiod_format"];

// check if user selected source DB
if ($db_source == NULL) {
  echo "<p style=\"color:OrangeRed;font-weight:bold\">Source database(s) not selected.</p>";
} else {
  
  // variables
  $mac_glbl=0;

  // loop every source DB
  foreach ($db_source as $key => $value) {

    // DB conn with specified source
    $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

    // global MAC within last $timeperiod minutes
    $db_q =      "SELECT COUNT(*) FROM Clients
                  WHERE (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))) AND
                 (station_MAC LIKE '_0:__:__:__:__:__' OR
                  station_MAC LIKE '_4:__:__:__:__:__' OR
                  station_MAC LIKE '_8:__:__:__:__:__' OR
                  station_MAC LIKE '_C:__:__:__:__:__');";

    $db_result  = mysqli_query($db_conn_s, $db_q);
    $db_row     = mysqli_fetch_assoc($db_result);

    $mac_glbl += $db_row["COUNT(*)"];
  }

  // text output
  echo "Wi-Fi<br>";
  echo "<table class=\"textout\">";
  switch ($timeperiod_format) {
    case "MINUTE":
      echo "<tr><td>" . "Global MAC adresses within last " . $timeperiod . " minute(s):" . "</td><td>" . $mac_glbl . "</td></tr>";
      break;
    case "HOUR":
      echo "<tr><td>" . "Global MAC adresses within last " . $timeperiod . " hour(s):" . "</td><td>" . $mac_glbl . "</td></tr>";
      break;
    default:
      echo "<tr><td>" . "<p class=\"p_incl_ERROR\">ERROR: cannot read Minute(s)/Hour(s) input for Time Period</p>" . "</td></tr>";
  }
  echo "</table>";
}
?>
