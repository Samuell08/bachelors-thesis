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

// functions
function is_anagram($string1, $string2) {
  if (count_chars($string1, 1) == count_chars($string2, 1))
    return 1;
  else
    return 0;
}

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

      // local MAC unique probe request fingerprints assoc array
      $db_q      = "SELECT SUBSTRING(probed_ESSIDs,19,1000) FROM Clients WHERE 
                   (LENGTH(probed_ESSIDs) > 18) AND
                   (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))) AND NOT
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__')
                    GROUP BY SUBSTRING(probed_ESSIDs,19,1000);";
      $db_result = mysqli_query($db_conn_s, $db_q);

      // fill (append to) fingerprints array
      if (mysqli_num_rows($db_result) > 0) {
        while ($db_row = mysqli_fetch_assoc($db_result)) {
          $fingerprints[] = $db_row["SUBSTRING(probed_ESSIDs,19,1000)"];
        }
      } 

    }

    // delete fingerprint anagrams
    foreach ($fingerprints as $master_key => &$master_value) {
      foreach ($fingerprints as $search_key => &$search_value) {
        $anagram = is_anagram($master_value, $search_value);
        // if anagram (self anagram does not count)
        if (($anagram == 1) and ($master_key != $search_key)) {
          // delete anagram from fingerprints array
          unset($fingerprints[$search_key]);
        }
      }
    }

    // text output
    echo "<b>Wi-Fi</b><br>";
    echo "<table class=\"textout\">";
      echo "<tr class=\"textout\"><td>" . "Number of devices with global (unique) MAC adress:" . "</td><td>" . $mac_glbl . "</td></tr>";
      echo "<tr class=\"textout\"><td>" . "Estimated number of devices with local MAC adress:" . "</td><td>" . count($fingerprints) . "</td></tr>";
      echo "<tr class=\"textout_extra\"><td>" . "Number of detected local (randomized) MAC adresses:" . "</td><td>" . $mac_local . "</td></tr>";
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
    echo "<b>Bluetooth</b><br>";
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
