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


// check if user input is correct
if ($db_source == NULL) {
  echo "<p class=\"warning\">Source database(s) not selected.</p>";
} elseif ($timeperiod_format == NULL) {
  echo "<p class=\"warning\">Time Period format Minute(s)/Hour(s) not selected.</p>";
} elseif ($timeperiod == NULL) {
  echo "<p class=\"warning\">Invalid time period.</p>";
} else {

  
  // text output
  echo date('G:i:s (j.n.Y)') . "<br>";
  echo "Showing device count within last " . $timeperiod . " " . strtolower($timeperiod_format) . "(s)" . "<br><br>"; 
  
  // ---------------------------------------------------------------------- WIFI
  // check if user selected to show wlan
  if ($showwlan == "1") {
  
    // variables
    $mac_glbl=0;
    $mac_local=0;

    // loop every source DB
    foreach ($db_source as $key => $value) {

      // DB conn with specified source
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // global MAC within last $timeperiod minutes
      $db_q      = "SELECT COUNT(*) FROM Clients WHERE 
                   (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))) AND
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__');";
      $db_result = mysqli_query($db_conn_s, $db_q);
      $db_row    = mysqli_fetch_assoc($db_result);
      $mac_glbl  += $db_row["COUNT(*)"];

      // local MAC with at least 1 probed SSID within last $timeperiod minutes
      $db_q      = "SELECT COUNT(*) FROM Clients WHERE 
                   (LENGTH(probed_ESSIDs) > 18) AND
                   (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))) AND NOT
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__');";
      $db_result = mysqli_query($db_conn_s, $db_q);
      $db_row    = mysqli_fetch_assoc($db_result);
      $mac_local += $db_row["COUNT(*)"];

    }

    // text output
    echo "Wi-Fi<br>";
    echo "<table class=\"textout\">";
      echo "<tr class=\"textout\"><td>" . "Global (unique) MAC adresses:" . "</td><td>" . $mac_glbl . "</td></tr>";
      echo "<tr class=\"textout\"><td>" . "Local (randomized) MAC adresses:" . "</td><td>" . $mac_local . "</td></tr>";
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
      echo "<tr class=\"textout\"><td>" . "Bluetooth devices:" . "</td><td>" . $bt_total . "</td></tr>";
    echo "</table>";
  }

  // ------------------------------------------------------------------- nothing
  // check if user selected nothing
  if ((!($showwlan == "1")) and (!($showbt == "1"))) {
    echo "<p class=\"warning\">No data selected to show.</p>";
  }
}

?>
