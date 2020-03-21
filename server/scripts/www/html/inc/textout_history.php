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

$time_since     = "2020-03-20 11:00:00";
$time_actual    = $time_since;
$time_until     = "2020-03-21 11:00:00";
$time_increment = $_SESSION["updateInterval"]/1000;
//date('Y-m-d H:i:s');


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
  echo  "Showing results since " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_since)) . "</b>" .
    " until " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_until)) . "</b>" .
    " with period of " . $timeperiod . " " . strtolower($timeperiod_format) . "(s)" . "<br><br>";

  // ---------------------------------------------------------------------- WIFI
  // check if user selected to show wlan
  if ($showwlan == "1") {
  
      // DB conn with specified source
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, "rpi_mon_node_99");

      // while actual < until
      while (strtotime($time_actual) <= strtotime($time_until)) {

        // global MAC within time period
        $db_q    = "SELECT station_MAC FROM Clients WHERE
                   (last_time_seen BETWEEN (DATE_SUB('" . $time_actual . "', INTERVAL " . $timeperiod . " " . $timeperiod_format . ")) AND '" . $time_actual . "') AND
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__')
                    GROUP BY station_MAC;";
        $db_result = mysqli_query($db_conn_s, $db_q);
        $mac_glbl  = mysqli_num_rows($db_result);

        // local MAC unique probe request fingerprints assoc array within time period
        $db_q    = "SELECT SUBSTRING(probed_ESSIDs,19,1000) FROM Clients WHERE
                   (LENGTH(probed_ESSIDs) > 18) AND
                   (last_time_seen BETWEEN (DATE_SUB('" . $time_actual . "', INTERVAL " . $timeperiod . " " . $timeperiod_format . ")) AND '" . $time_actual . "') AND NOT
                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                    station_MAC LIKE '_4:__:__:__:__:__' OR
                    station_MAC LIKE '_8:__:__:__:__:__' OR
                    station_MAC LIKE '_C:__:__:__:__:__')
                    GROUP BY station_MAC;";
        $db_result = mysqli_query($db_conn_s, $db_q);

        unset($fingerprints);
        // fill (append to) fingerprints array
        if (mysqli_num_rows($db_result) > 0) {
          while ($db_row = mysqli_fetch_assoc($db_result)) {
            $fingerprints[] = $db_row["SUBSTRING(probed_ESSIDs,19,1000)"];
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

        // push new data into chart arrays
        array_push($chart_wifi_bot, array("x" => (strtotime($time_actual)*1000), "y" => $mac_glbl));
        array_push($chart_wifi_top, array("x" => (strtotime($time_actual)*1000), "y" => $fingerprints_count));

        // increment counter
        $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
      }

    // debug
    echo "<br>chart_wifi_bot:<br>";
    print_r($chart_wifi_bot);
    echo "<br><br>chart_wifi_top:<br>";
    print_r($chart_wifi_top);
  }

  // ----------------------------------------------------------------- Bluetooth
  // check if user selected to show bt
  if ($showbt == "1") {
      // DB conn with specified source
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, "rpi_mon_node_99");

      // while actual < until
      while (strtotime($time_actual) <= strtotime($time_until)) {

        // Bluetooth within time period
        $db_q    = "SELECT BD_ADDR FROM Bluetooth WHERE
                   (last_time_seen BETWEEN (DATE_SUB('" . $time_actual . "', INTERVAL " . $timeperiod . " " . $timeperiod_format . ")) AND '" . $time_actual . "')
                    GROUP BY BD_ADDR;";
        $db_result = mysqli_query($db_conn_s, $db_q);
        $bt_total  = mysqli_num_rows($db_result);

        // push new data into chart arrays
        array_push($chart_bt, array("x" => (strtotime($time_actual)*1000), "y" => $bt_total));

        // increment counter
        $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
      }

    // debug
    echo "<br><br>chart_bt:<br>";
    print_r($chart_bt);

  }

}

?>
