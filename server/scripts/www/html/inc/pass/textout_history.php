<?php
// TEXTOUT_HISTORY.php
// PHP code to be called from pass_history.php

session_start();

$session_id = session_id();

// infinite execution time due to nested loops
set_time_limit(0);

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

function find_passages($mac_timestamps, $threshold_seconds) {
  // open timestamps <td>
  echo "<td><tt>";
  // first is always bold
  echo "<b>" . $mac_timestamps[0] . "</b> | ";
  $mac_pass_subarray[0] = $mac_timestamps[0];
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
  // close timestamps <td>
  echo "</tt></td>";
  echo "</tr>";

  return $mac_pass_subarray;
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
  case "SECOND":
    $threshold_seconds = $threshold_ph;
    break;
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

        // build total and unique arrays
        // reset counters
        $i = 0;
        $time_actual = date('Y-m-d H:i:s', (strtotime($time_from_ph) + $time_increment));
        while (strtotime($time_actual) <= strtotime($time_to_ph)) {
          // initialize whole arrays to 0 with correct timestamps
          $chart_wifi_unique_ph[$i]["x"] = strtotime($time_actual)*1000;
          $chart_wifi_unique_ph[$i]["y"] = 0;
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

        // process MySQL query result - fill macs array
        unset($macs);
        $mac_glbl_passed = mysqli_num_rows($db_result);
        if (mysqli_num_rows($db_result) > 0) {
          while ($db_row = mysqli_fetch_assoc($db_result)) {
            $macs[] = $db_row["station_MAC"];
          }
        }
        mysqli_free_result($db_result);
        
        // text output
        echo "<b>Wi-Fi</b><br>";
        echo "<table class=\"textout\">";
          echo "<tr class=\"textout\"><td>" . "Total number of devices with global (unique) MAC address passed:" . "</td><td>" . $mac_glbl_passed . "</td></tr>";
          // extra
        echo "</table>";

        echo "<br>Wi-Fi devices with global MAC addresses:<br>";
        echo "<table style=\"border-collapse:collapse\">";

        // loop every MAC in macs array
        // prepare MySQL statement
        $query = "SELECT last_time_seen FROM Clients WHERE
                 (last_time_seen BETWEEN '" . $time_from_ph . "' AND '" . $time_to_ph . "') AND (station_MAC = ?);";
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "s", $macs_value);

        foreach ($macs as $macs_key => $macs_value) {

          // output global MAC addresses with timestamps table
          echo "<tr class=\"info\">";
          echo "<td><tt>" . $macs_value . "&nbsp&nbsp&nbsp&nbsp&nbsp</tt></td>";

          // process MySQL query result - fill timestamps array for given MAC
          mysqli_stmt_execute($stmt);
          $db_result = mysqli_stmt_get_result($stmt);
          unset($mac_timestamps);
          if (mysqli_num_rows($db_result) > 0) {
            while ($db_row = mysqli_fetch_array($db_result, MYSQLI_ASSOC)) {
              $mac_timestamps[] = $db_row["last_time_seen"];
            }
          }
          mysqli_free_result($db_result);
        
          // build passages subarray based on MAC timestamps
          $mac_pass_subarray = find_passages($mac_timestamps, $threshold_seconds);

          // fill total and unique passages arrays based on passages subarray
          $unique = 1;
          // reset counters
          $i = 0;
          $time_actual = $time_from_ph;
          while (strtotime($time_actual) <= (strtotime($time_to_ph) - $time_increment)) {
            // calculate next time value
            $time_next = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
            // passage in current time step?
            foreach ($mac_pass_subarray as $key => $value){
              if ((strtotime($value) > strtotime($time_actual)) && (strtotime($value) <= strtotime($time_next))){
                $chart_wifi_total_ph[$i]["y"] += 1;
                if ($unique) {
                  $chart_wifi_unique_ph[$i]["y"] += 1;
                  $unique = 0;
                }
              }
            }

            // moving to next time step
            $unique = 1;
            // increment counters
            $i += 1;
            $time_actual = $time_next;
          }

          // moving to next MAC addr
          unset($mac_pass_subarray);

        } // end foreach MAC (global)
        echo "</table><br>";

                

        // LOCAL MAC LOOP (TOTAL)

      } // end of show_wlan_ph

      // ----------------------------------------------------------------- Bluetooth
      if ($show_bt_ph == "1") {
          
        // build total and unique arrays
        // reset counters
        $i = 0;
        $time_actual = date('Y-m-d H:i:s', (strtotime($time_from_ph) + $time_increment));
        while (strtotime($time_actual) <= strtotime($time_to_ph)) {
          // initialize whole arrays to 0 with correct timestamps
          $chart_bt_unique_ph[$i]["x"] = strtotime($time_actual)*1000;
          $chart_bt_unique_ph[$i]["y"] = 0;
          $chart_bt_total_ph[$i]["x"] = strtotime($time_actual)*1000;
          $chart_bt_total_ph[$i]["y"] = 0;
          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        }

        // every unique BD_ADDR saved to PHP array $bd_addrs
        $db_q = "SELECT BD_ADDR FROM Bluetooth WHERE
                (last_time_seen BETWEEN '" . $time_from_ph . "' AND '" . $time_to_ph . "')
                 GROUP BY BD_ADDR;";
        $db_result = mysqli_query($db_conn_s, $db_q);

        // process MySQL query result - fill bd_addrs array
        unset($bd_addrs);
        $bt_passed = mysqli_num_rows($db_result);
        if (mysqli_num_rows($db_result) > 0) {
          while ($db_row = mysqli_fetch_assoc($db_result)) {
            $bd_addrs[] = $db_row["BD_ADDR"];
          }
        }
        mysqli_free_result($db_result);
        
        // text output
        echo "<b>Bluetooth</b><br>";
        echo "<table class=\"textout\">";
          echo "<tr class=\"textout\"><td>" . "Total number of devices passed:" . "</td><td>" . $bt_passed . "</td></tr>";
          // extra
        echo "</table>";

        echo "<br>Bluetooth devices:<br>";
        echo "<table style=\"border-collapse:collapse\">";

        // loop every BD_ADDR in bd_addrs array
        // prepare MySQL statement
        $query = "SELECT last_time_seen FROM Bluetooth WHERE
                 (last_time_seen BETWEEN '" . $time_from_ph . "' AND '" . $time_to_ph . "') AND (BD_ADDR = ?);";
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "s", $bd_addrs_value);

        foreach ($bd_addrs as $bd_addrs_key => $bd_addrs_value) {

          // output BD_ADDR addresses with timestamps table
          echo "<tr class=\"info\">";
          echo "<td><tt>" . $bd_addrs_value . "&nbsp&nbsp&nbsp&nbsp&nbsp</tt></td>";

          // process MySQL query result - fill timestamps array for given BD_ADDR
          mysqli_stmt_execute($stmt);
          $db_result = mysqli_stmt_get_result($stmt);
          unset($bd_addr_timestamps);
          if (mysqli_num_rows($db_result) > 0) {
            while ($db_row = mysqli_fetch_array($db_result, MYSQLI_ASSOC)) {
              $bd_addr_timestamps[] = $db_row["last_time_seen"];
            }
          }
          mysqli_free_result($db_result);
        
          // build passages subarray based on BD_ADDR timestamps
          $bd_addr_pass_subarray = find_passages($bd_addr_timestamps, $threshold_seconds);

          // fill total and unique passages arrays based on passages subarray
          $unique = 1;
          // reset counters
          $i = 0;
          $time_actual = $time_from_ph;
          while (strtotime($time_actual) <= (strtotime($time_to_ph) - $time_increment)) {
            // calculate next time value
            $time_next = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
            // passage in current time step?
            foreach ($bd_addr_pass_subarray as $key => $value){
              if ((strtotime($value) > strtotime($time_actual)) && (strtotime($value) <= strtotime($time_next))){
                $chart_bt_total_ph[$i]["y"] += 1;
                if ($unique) {
                  $chart_bt_unique_ph[$i]["y"] += 1;
                  $unique = 0;
                }
              }
            }

            // moving to next time step
            $unique = 1;
            // increment counters
            $i += 1;
            $time_actual = $time_next;
          }

          // moving to next BD_ADDR addr
          unset($bd_addr_pass_subarray);

        } // end foreach BD_ADDR
        echo "</table><br>";

      } // end of show_bt_ph
    } // end of foreach DB

  // write completed chart arrays to json files
  $json_dir = "../../json";
  if (!file_exists($json_dir)){ mkdir($json_dir); }
  $f_wifi_unique_ph = fopen($json_dir . "/chart_wifi_unique_ph_" . $session_id, "w");
  $f_wifi_total_ph  = fopen($json_dir . "/chart_wifi_total_ph_" . $session_id, "w");
  $f_bt_unique_ph   = fopen($json_dir . "/chart_bt_unique_ph_" . $session_id, "w");
  $f_bt_total_ph    = fopen($json_dir . "/chart_bt_total_ph_" . $session_id, "w");
  fwrite($f_wifi_unique_ph, json_encode($chart_wifi_unique_ph));
  fwrite($f_wifi_total_ph,  json_encode($chart_wifi_total_ph));
  fwrite($f_bt_unique_ph,   json_encode($chart_bt_unique_ph));
  fwrite($f_bt_total_ph,    json_encode($chart_bt_total_ph));
  fclose($f_wifi_unique_ph);
  fclose($f_wifi_total_ph);
  fclose($f_bt_unique_ph);
  fclose($f_bt_total_ph);

  // algorithm execution end
  $alg_end = time();
  $mem_peak = memory_get_peak_usage();
  $mem_peak = $mem_peak / 1000000; // MB
  echo "Algorithm finished in " . ($alg_end - $alg_start) . " seconds.<br>";
  echo "Memory usage peak: " . round($mem_peak, 2) . " MB";
}
?>
