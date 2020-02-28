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
$showwlan           = $_SESSION["showwlan"];
$showbt             = $_SESSION["showbt"];


// check if user selected source DB
if ($db_source == NULL) {
  echo "<p class=\"warning\">Source database(s) not selected.</p>";
} else {

  // ---------------------------------------------------------------------- WIFI
  // check if user selected to show wlan
  if ($showwlan == "1") {
  
    // variables
    $mac_glbl=0;

    // loop every source DB
    foreach ($db_source as $key => $value) {

      // DB conn with specified source
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // global MAC within last $timeperiod minutes
      $db_q      = "SELECT COUNT(*) FROM Clients
                    WHERE (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))) AND
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__');";

      $db_result = mysqli_query($db_conn_s, $db_q);
      $db_row    = mysqli_fetch_assoc($db_result);

      $mac_glbl  += $db_row["COUNT(*)"];
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
        echo "<tr><td>" . "<p class=\"error\">ERROR: cannot read Minute(s)/Hour(s) input for Time Period</p>" . "</td></tr>";
    }
    echo "</table>";
  }

  // ----------------------------------------------------------------- Bluetooth
  // check if user selected to show bt
  if ($showbt == "1") {
  
    // variables
    $bt_total=0;

    // loop every source DB
    foreach ($db_source as $key => $value) {

      // DB conn with specified source
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // Bluetooth within last $timeperiod minutes
      $db_q      = "SELECT COUNT(*) FROM Bluetooth
                    WHERE last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))";

      $db_result = mysqli_query($db_conn_s, $db_q);
      $db_row    = mysqli_fetch_assoc($db_result);

      $bt_total  += $db_row["COUNT(*)"];
    }

    // text output
    echo "Bluetooth<br>";
    echo "<table class=\"textout\">";
    switch ($timeperiod_format) {
      case "MINUTE":
        echo "<tr><td>" . "Total Bluetooth devices within last " . $timeperiod . " minute(s):" . "</td><td>" . $bt_total . "</td></tr>";
        break;
      case "HOUR":
        echo "<tr><td>" . "Total Bluetooth devices within last " . $timeperiod . " hour(s):" . "</td><td>" . $bt_total . "</td></tr>";
        break;
      default:
        echo "<tr><td>" . "<p class=\"error\">ERROR: cannot read Minute(s)/Hour(s) input for Time Period</p>" . "</td></tr>";
    }
    echo "</table>";
  }

  // ------------------------------------------------------------------- nothing
  // check if user selected nothing
  if ((!($showwlan == "1")) and (!($showbt == "1"))) {
    echo "<p class=\"warning\">No data selected to show.</p>";
  }
}

?>
