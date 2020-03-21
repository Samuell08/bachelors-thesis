<?php
// TEXTOUT_HISTORY.php
// PHP code to be called from history.php

session_start();

$session_id = session_id();

// get session variables
// database connection
$db_server          = $_SESSION["db_server"];
$db_user            = $_SESSION["db_user"];
$db_pass            = $_SESSION["db_pass"];
$db_source          = $_SESSION["db_source"];
// settings
$timeperiod         = $_SESSION["timeperiod"];
$timeperiod_format  = $_SESSION["timeperiod_format"];
$showwlan           = $_SESSION["showwlan"];
$showbt             = $_SESSION["showbt"];
// chart arrays
$chart_wifi_bot     = $_SESSION["chart_wifi_bot"];
$chart_wifi_top     = $_SESSION["chart_wifi_top"];
$chart_bt           = $_SESSION["chart_bt"];

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
} elseif ((!($showwlan == "1")) and (!($showbt == "1"))) {
  echo "<p class=\"warning\">No data selected to show.</p>";
} else {

  // reset variables before queries
  $mac_glbl   = 0;
  $mac_local  = 0;
  $bt_total   = 0;
  $fingerprints_count = 0;
  
  // text output
  echo date('G:i:s (j.n.Y)') . "<br>";
  echo "Showing results of last " . $timeperiod . " " . strtolower($timeperiod_format) . "(s) ";
  echo "updated every " . $_SESSION["updateInterval"]/1000 . " seconds" . "<br><br>"; 

  // ---------------------------------------------------------------------- WIFI
  // check if user selected to show wlan
  if ($showwlan == "1") {
  
    // loop every source DB
    foreach ($db_source as $key => $value) {

      // DB conn with specified source
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // global MAC within last $timeperiod minutes
      $db_q      = "SELECT station_MAC FROM Clients WHERE 
                   (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))) AND
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__')
                    GROUP BY station_MAC;";
      $db_result = mysqli_query($db_conn_s, $db_q);
      $mac_glbl  += mysqli_num_rows($db_result);

      // local MAC within last $timeperiod minutes
      $db_q      = "SELECT station_MAC FROM Clients WHERE 
                   (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))) AND NOT
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__')
                    GROUP BY station_MAC;";
      $db_result = mysqli_query($db_conn_s, $db_q);
      $mac_local += mysqli_num_rows($db_result);

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
        if ($master_key != $search_key){
          if(is_anagram($master_value, $search_value)){
            // delete anagram from fingerprints array
            unset($fingerprints[$search_key]);
          }
        }
      }
    }

    $fingerprints_count = count($fingerprints);
    $est_total_wifi = $mac_glbl + $fingerprints_count;

    // text output
    echo "<b>Wi-Fi</b><br>";
    echo "<table class=\"textout\">";
      echo "<tr class=\"textout\"><td>" . "Number of devices with global (unique) MAC adress:" . "</td><td>" . $mac_glbl . "</td></tr>";
      echo "<tr class=\"textout\"><td>" . "Number of identified local MAC address fingerprints:" . "</td><td>" . $fingerprints_count . "</td></tr>";
      echo "<tr class=\"textout\" style=\"border-bottom:3px double black\"><td>" . "Estimated total number of devices within reach:" . "</td><td>" . $est_total_wifi . "</td></tr>";
      // extra
      echo "<tr class=\"textout_extra\"><td>" . "Number of detected local (randomized) MAC adresses:" . "</td><td>" . $mac_local . "</td></tr>";
    echo "</table>";
  }

  // ----------------------------------------------------------------- Bluetooth
  // check if user selected to show bt
  if ($showbt == "1") {

    // loop every source DB
    foreach ($db_source as $key => $value) {

      // DB conn with specified source
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // Bluetooth within last $timeperiod minutes
      $db_q      = "SELECT BD_ADDR FROM Bluetooth
                    WHERE last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $timeperiod . " " . $timeperiod_format . "))
                    GROUP BY BD_ADDR;";

      $db_result = mysqli_query($db_conn_s, $db_q);
      $bt_total  += mysqli_num_rows($db_result);
    }

    // text output
    echo "<b>Bluetooth</b><br>";
    echo "<table class=\"textout\">";
      echo "<tr class=\"textout\"><td>" . "Number of devices discovered:" . "</td><td>" . $bt_total . "</td></tr>";
    echo "</table>";
  }

  // -------------------------------------------------------------- chart arrays
  // push new data into chart arrays
  $current_time = time()*1000;
  array_push($chart_wifi_bot, array("x" => $current_time, "y" => $mac_glbl));
  array_push($chart_wifi_top, array("x" => $current_time, "y" => $fingerprints_count));
  array_push($chart_bt, array("x" => $current_time, "y" => $bt_total));

  // save updated chart arrays to session
  $_SESSION["chart_wifi_bot"] = $chart_wifi_bot;
  $_SESSION["chart_wifi_top"] = $chart_wifi_top;
  $_SESSION["chart_bt"] = $chart_bt;

  // write updated chart arrays to json files
  $json_dir = "../json";
  if (!file_exists($json_dir)){ mkdir($json_dir); }
  $f_bot = fopen($json_dir . "/chart_wifi_bot_" . $session_id, "w");
  $f_top = fopen($json_dir . "/chart_wifi_top_" . $session_id, "w");
  $f_bt  = fopen($json_dir . "/chart_bt_" . $session_id, "w");
  fwrite($f_bot, json_encode($chart_wifi_bot));
  fwrite($f_top, json_encode($chart_wifi_top));
  fwrite($f_bt, json_encode($chart_bt));
  fclose($f_bot);
  fclose($f_top);
  fclose($f_bt);
}

?>
