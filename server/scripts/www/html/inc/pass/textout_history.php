<?php
// TEXTOUT_HISTORY.php
// PHP code to be called from pass_history.php

session_start();

$session_id = session_id();

// get session variables
// database connection
$db_server     = $_SESSION["db_server"];
$db_user       = $_SESSION["db_user"];
$db_pass       = $_SESSION["db_pass"];
$db_source_ph  = $_SESSION["db_source_ph"];
// settings
$time_from_ph           = $_SESSION["time_from_ph"];
$time_to_ph             = $_SESSION["time_to_ph"];
$time_step_ph           = $_SESSION["time_step_ph"];
$time_step_format_ph    = $_SESSION["time_step_format_ph"];
$threshold_ph           = $_SESSION["threshold_ph"];
$threshold_format_ph    = $_SESSION["threshold_format_ph"];
$show_wlan_ph           = $_SESSION["show_wlan_ph"];
$show_bt_ph             = $_SESSION["show_bt_ph"];

// functions
function is_anagram($string1, $string2) {
  if (count_chars($string1, 1) == count_chars($string2, 1))
    return 1;
  else
    return 0;
}

// check if user input is correct
if ($db_source_ph == NULL) {
  echo "<p class=\"warning\">Source database(s) not selected.</p>";
} elseif ((!($show_wlan_ph == "1")) and (!($show_bt_ph == "1"))) {
  echo "<p class=\"warning\">No data selected to show.</p>";
} elseif (strtotime($time_from_ph) > strtotime($time_to_ph)) {
  echo "<p class=\"warning\">Time range \"From\" is later in time than \"To\".</p>";
} elseif (strtotime($time_to_ph) > time()) {
  echo "<p class=\"warning\">Time range \"To\" is in the future.</p>";
} elseif ($threshold_ph == NULL) {
  echo "<p class=\"warning\">Invalid threshold.</p>";
} elseif ($threshold_format_ph == NULL) {
  echo "<p class=\"warning\">Threshold format Minute(s)/Hour(s) not selected.</p>";
} else {

  // algorithm execution start
  $alg_start = time();

  // reset variables before queries
  $mac_glbl   = 0;
  $mac_local  = 0;
  $bt_total   = 0;
  $fingerprints_count = 0;

  // calculate time increment
  switch ($time_step_format_ph) {
  case "SECOND":
    $time_increment = $time_step_ph;
    break;
  case "MINUTE":
    $time_increment = $time_step_ph*60;
    break;
  case "HOUR":
    $time_increment = $time_step_ph*3600;
    break;
  }    

  // calculate threshold seconds
  switch ($threshold_format_ph) {
  case "MINUTE":
    $threshold_seconds = $threshold_ph*60;
    break;
  case "HOUR":
    $threshold_seconds = $threshold_ph*3600;
    break;
  }    
  
  // text output
  echo  "Showing results from " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_from_ph)) . "</b>" .
        " to " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_to_ph)) . "</b>" .
        " with step of " . "<b>" . $time_step_ph . " " . strtolower($time_step_format_ph) . "(s)" . "</b>" . "<br><br>";

    foreach ($db_source_ph as $key => $value) {

      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // ---------------------------------------------------------------------- WIFI
      if ($show_wlan_ph == "1") {

        // build total array
        // reset counters
        $i = 0;
        $time_actual = date('Y-m-d H:i:s', (strtotime($time_from_ph) + $time_increment));
        while (strtotime($time_actual) <= strtotime($time_to_ph)) {
          // initialize whole array to 0 with correct timestamps
          $chart_wifi_total_ph[$i]["x"] = strtotime($time_actual)*1000;
          $chart_wifi_total_ph[$i]["y"] = 0;
          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        }

        // GLOBAL MAC LOOP (TOTAL)

        // every unique global MAC saved to PHP array $macs
        $db_q = "SELECT station_MAC FROM Clients WHERE
                (last_time_seen BETWEEN '" . $time_from_ph . "' AND '" . $time_to_ph . "') AND
                (station_MAC LIKE '_0:__:__:__:__:__' OR
                 station_MAC LIKE '_4:__:__:__:__:__' OR
                 station_MAC LIKE '_8:__:__:__:__:__' OR
                 station_MAC LIKE '_C:__:__:__:__:__')
                 GROUP BY station_MAC;";
        $db_result = mysqli_query($db_conn_s, $db_q);

        unset($macs);
        // fill macs array
        if (mysqli_num_rows($db_result) > 0) {
          while ($db_row = mysqli_fetch_assoc($db_result)) {
            $macs[] = $db_row["station_MAC"];
          }
        }

        // loop every MAC from last query
        echo "<br>Wi-Fi devices with global MAC addresses:<br>";
        echo "<table style=\"border-collapse:collapse\">";
        foreach ($macs as $macs_key => $macs_value) {

          // output MAC address
          echo "<tr class=\"info\">";
          echo "<td><tt>" . $macs_value . "&nbsp&nbsp&nbsp&nbsp&nbsp</tt></td>";

          // every timestamp for given MAC in time from From to To
          $db_q = "SELECT last_time_seen FROM Clients WHERE
                  (last_time_seen BETWEEN '" . $time_from_ph . "' AND '" . $time_to_ph . "') AND (station_MAC = '" . $macs_value . "');";
          $db_result = mysqli_query($db_conn_s, $db_q);

          unset($mac_timestamps);
          // fill mac_timestamps array
          if (mysqli_num_rows($db_result) > 0) {
            while ($db_row = mysqli_fetch_assoc($db_result)) {
              $mac_timestamps[] = $db_row["last_time_seen"];
            }
          }

          // build passages subarray
          unset($mac_pass_subarray);
          echo "<td><tt>"; // open timestamps <td>
          // first is always bold
          echo "<b>" . $mac_timestamps[0] . "</b> | ";
          $mac_pass_subarray[] = $mac_timestamps[0];

          // loop every timestamp for current MAC address
          for ($i = 1; $i < count($mac_timestamps); $i++){
            if ((strtotime($mac_timestamps[$i]) - strtotime($mac_timestamps[$i-1]) > $threshold_seconds)) {
              // output bold timestamp
              echo "<b>" . $mac_timestamps[$i] . "</b> | ";
              $mac_pass_subarray[] = $mac_timestamps[$i];
            } else {
              // output normal timestamp
              echo $mac_timestamps[$i] . " | ";
            }
          }

          echo "</tt></td>"; // close timestamps <td>
          echo "</tr>";

          // fill total passages array based on passages subarray
          // reset counters
          $i = 0;
          $time_actual = $time_from_ph;
          while (strtotime($time_actual) <= strtotime($time_to_ph)) {
            // calculate next time value
            $time_next = (strtotime($time_actual) + $time_increment);
            // passage in current time step?
            foreach ($mac_pass_subarray as $key => $value){
              if ((strtotime($value) > strtotime($time_actual)) && (strtotime($value) <= $time_next)){
                $chart_wifi_total_ph[$i]["y"] += 1;
                if ($unique) {
                  $chart_wifi_unique_ph[$i]["y"] += 1;
                  $unique = 0;
                }
              }
            }

            // increment counters
            $i += 1;
            $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
          }
          

        } // end foreach MAC (global)
        echo "</table><br>";

                

        // LOCAL MAC LOOP (TOTAL)

      } // end of show_wlan_ph

      // ----------------------------------------------------------------- Bluetooth
      if ($show_bt_ph == "1") {
          
        // prepare MySQL statement
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, "SELECT COUNT(DISTINCT BD_ADDR) AS TotalRows FROM Bluetooth WHERE
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_step_ph . " " . $time_step_format_ph . ")) AND ?);");
        mysqli_stmt_bind_param($stmt, "ss", $time_actual, $time_actual);
        mysqli_stmt_bind_result($stmt, $bt_total);

        // reset counters
        $i = 0;
        $time_actual = $time_from_ph;
        
        // loop whole time range
        while (strtotime($time_actual) <= strtotime($time_to_ph)) {

          // execute prepared MySQL statement
          mysqli_stmt_execute($stmt);
          // save MySQL query result to bt_total
          mysqli_stmt_fetch($stmt);

          // push new data into chart arrays
          $chart_bt_ph[$i]["x"]  = strtotime($time_actual)*1000;
          $chart_bt_ph[$i]["y"] += $bt_total;

          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        } // end of Bluetooth while
        mysqli_stmt_close($stmt);
      } // end of show_bt_ph
    } // end of foreach DB

  // write completed chart arrays to json files
  $json_dir = "../../json";
  if (!file_exists($json_dir)){ mkdir($json_dir); }
  $f_wifi_unique_ph = fopen($json_dir . "/chart_wifi_unique_ph_" . $session_id, "w");
  $f_wifi_total_ph  = fopen($json_dir . "/chart_wifi_total_ph_" . $session_id, "w");
  $f_bt_ph          = fopen($json_dir . "/chart_bt_ph_" . $session_id, "w");
  fwrite($f_wifi_unique_ph, json_encode($chart_wifi_unique_ph));
  fwrite($f_wifi_total_ph,  json_encode($chart_wifi_total_ph));
  fwrite($f_bt_ph,          json_encode($chart_bt_ph));
  fclose($f_wifi_unique_ph);
  fclose($f_wifi_total_ph);
  fclose($f_bt_ph);

  // algorithm execution end
  $alg_end = time();
  echo "Algorithm finished in " . ($alg_end - $alg_start) . " seconds.";
}
?>
