<?php
// TEXTOUT_LIVE.php
// PHP code to be called from range_live.php

session_start();

$session_id = session_id();

// infinite execution time
set_time_limit(0);

// get session variables
// database connection
$db_server    = $_SESSION["db_server"];
$db_user      = $_SESSION["db_user"];
$db_pass      = $_SESSION["db_pass"];
$db_source_rl = $_SESSION["db_source_rl"];
// settings
$time_period_rl         = $_SESSION["time_period_rl"];
$time_period_format_rl  = $_SESSION["time_period_format_rl"];
$show_wlan_rl           = $_SESSION["show_wlan_rl"];
$show_bt_rl             = $_SESSION["show_bt_rl"];
// chart arrays
$chart_wifi_bot_rl = $_SESSION["chart_wifi_bot_rl"];
$chart_wifi_top_rl = $_SESSION["chart_wifi_top_rl"];
$chart_bt_rl       = $_SESSION["chart_bt_rl"];

// functions
function is_anagram($string1, $string2) {
  if (count_chars($string1, 1) == count_chars($string2, 1))
    return 1;
  else
    return 0;
}

// check if user input is correct
if ($db_source_rl == NULL) {
  echo "<p class=\"warning\">Source database(s) not selected.</p>";
} elseif ($time_period_format_rl == NULL) {
  echo "<p class=\"warning\">Time Period format Minute(s)/Hour(s) not selected.</p>";
} elseif ($time_period_rl == NULL) {
  echo "<p class=\"warning\">Invalid time period.</p>";
} elseif ((!($show_wlan_rl == "1")) and (!($show_bt_rl == "1"))) {
  echo "<p class=\"warning\">No data selected to show.</p>";
} else {

  // prepare variables
  $mac_glbl = 0;
  $mac_local = 0;
  $bt_total = 0;
  $fingerprints_count = 0;
  
  // text output
  echo date('G:i:s (j.n.Y)') . "<br>";
  echo "Showing results of last " . $time_period_rl . " " . strtolower($time_period_format_rl) . "(s) ";
  echo "updated every " . $_SESSION["updateInterval"]/1000 . " seconds" . "<br><br>"; 

  // ---------------------------------------------------------------------- WIFI
  if ($show_wlan_rl == "1") {
  
    // loop every source DB
    foreach ($db_source_rl as $key => $value) {

      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // global MAC within last $time_period_rl
      $db_q      = "SELECT station_MAC FROM Clients WHERE 
                   (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $time_period_rl . " " . $time_period_format_rl . "))) AND
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__')
                    GROUP BY station_MAC;";
      $db_result = mysqli_query($db_conn_s, $db_q);
      $mac_glbl  += mysqli_num_rows($db_result);

      // local MAC within last $time_period_rl
      $db_q      = "SELECT station_MAC FROM Clients WHERE 
                   (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $time_period_rl . " " . $time_period_format_rl . "))) AND NOT
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__')
                    GROUP BY station_MAC;";
      $db_result = mysqli_query($db_conn_s, $db_q);
      $mac_local += mysqli_num_rows($db_result);

      // local MAC unique probe request fingerprints assoc array within last $time_period_rl time
      $db_q      = "SELECT SUBSTRING(probed_ESSIDs,19,1000) FROM Clients WHERE 
                   (LENGTH(probed_ESSIDs) > 18) AND
                   (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $time_period_rl . " " . $time_period_format_rl . "))) AND NOT
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
      echo "<tr class=\"textout\"><td>" . "Number of devices with global (unique) MAC address:" . "</td><td>" . $mac_glbl . "</td></tr>";
      echo "<tr class=\"textout\"><td>" . "Number of identified local MAC address fingerprints:" . "</td><td>" . $fingerprints_count . "</td></tr>";
      echo "<tr class=\"textout\" style=\"border-bottom:3px double black\"><td>" . "Estimated total number of devices within reach:" . "</td><td>" . $est_total_wifi . "</td></tr>";
      // extra
      echo "<tr class=\"textout_extra\"><td>" . "Number of detected local (randomized) MAC addresses:" . "</td><td>" . $mac_local . "</td></tr>";
    echo "</table>";
  }

  // ----------------------------------------------------------------- Bluetooth
  if ($show_bt_rl == "1") {

    // loop every source DB
    foreach ($db_source_rl as $key => $value) {

      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // Bluetooth within last $time_period_rl time
      $db_q      = "SELECT BD_ADDR FROM Bluetooth
                    WHERE last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . $time_period_rl . " " . $time_period_format_rl . "))
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
  array_push($chart_wifi_bot_rl, array("x" => $current_time, "y" => $mac_glbl));
  array_push($chart_wifi_top_rl, array("x" => $current_time, "y" => $fingerprints_count));
  array_push($chart_bt_rl, array("x" => $current_time, "y" => $bt_total));

  // write updated chart arrays to json files
  $json_dir = "../../json";
  if (!file_exists($json_dir)){ mkdir($json_dir); }
  $f_bot = fopen($json_dir . "/chart_wifi_bot_rl_" . $session_id, "w");
  $f_top = fopen($json_dir . "/chart_wifi_top_rl_" . $session_id, "w");
  $f_bt  = fopen($json_dir . "/chart_bt_rl_" . $session_id, "w");
  fwrite($f_bot, json_encode($chart_wifi_bot_rl));
  fwrite($f_top, json_encode($chart_wifi_top_rl));
  fwrite($f_bt, json_encode($chart_bt_rl));
  fclose($f_bot);
  fclose($f_top);
  fclose($f_bt);
  
  // save updated chart arrays to session
  $_SESSION["chart_wifi_bot_rl"] = $chart_wifi_bot_rl;
  $_SESSION["chart_wifi_top_rl"] = $chart_wifi_top_rl;
  $_SESSION["chart_bt_rl"] = $chart_bt_rl;
}

?>
