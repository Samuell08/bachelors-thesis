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
$time_period_ph         = $_SESSION["time_period_ph"];
$time_period_format_ph  = $_SESSION["time_period_format_ph"];
$show_wlan_ph           = $_SESSION["show_wlan_ph"];
$show_bt_ph             = $_SESSION["show_bt_ph"];
$time_from_ph           = $_SESSION["time_from_ph"];
$time_to_ph             = $_SESSION["time_to_ph"];
$time_step_ph           = $_SESSION["time_step_ph"];
$time_step_format_ph    = $_SESSION["time_step_format_ph"];

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
} elseif ($time_period_format_ph == NULL) {
  echo "<p class=\"warning\">Time Period format Minute(s)/Hour(s) not selected.</p>";
} elseif ($time_period_ph == NULL) {
  echo "<p class=\"warning\">Invalid time period.</p>";
} elseif ((!($show_wlan_ph == "1")) and (!($show_bt_ph == "1"))) {
  echo "<p class=\"warning\">No data selected to show.</p>";
} elseif (strtotime($time_from_ph) > strtotime($time_to_ph)) {
  echo "<p class=\"warning\">Time range \"From\" is later in time than \"To\".</p>";
} elseif (strtotime($time_to_ph) > time()) {
  echo "<p class=\"warning\">Time range \"To\" is in the future.</p>";
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
  
  // text output
  echo  "Showing results from " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_from_ph)) . "</b>" .
    " to " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_to_ph)) . "</b>" .
    " with period of " . "<b>" . $time_period_ph . " " . strtolower($time_period_format_ph) . "(s)" . "</b>" . "<br><br>";

    foreach ($db_source_ph as $key => $value) {

      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // ---------------------------------------------------------------------- WIFI
      if ($show_wlan_ph == "1") {

        // GLOBAL MAC LOOP
        
        // prepare MySQL statement
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, "SELECT COUNT(DISTINCT station_MAC) AS TotalRows FROM Clients WHERE
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_period_ph . " " . $time_period_format_ph . ")) AND ?) AND
                                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                                    station_MAC LIKE '_4:__:__:__:__:__' OR
                                    station_MAC LIKE '_8:__:__:__:__:__' OR
                                    station_MAC LIKE '_C:__:__:__:__:__');");
        mysqli_stmt_bind_param($stmt, "ss", $time_actual, $time_actual);
        mysqli_stmt_bind_result($stmt, $mac_glbl);

        // reset counters
        $i = 0;
        $time_actual = $time_from_ph;
        
        // loop whole time range
        while (strtotime($time_actual) <= strtotime($time_to_ph)) {

          // execute prepared MySQL statement
          mysqli_stmt_execute($stmt);
          // save MySQL query result to mac_glbl
          mysqli_stmt_fetch($stmt);

          // push new data into chart arrays
          $chart_wifi_unique_ph[$i]["x"]  = strtotime($time_actual)*1000;
          $chart_wifi_unique_ph[$i]["y"] += $mac_glbl;

          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        } // end of global MAC while
        mysqli_stmt_close($stmt);

        // LOCAL MAC LOOP
        
        // prepare MySQL statement
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, "SELECT SUBSTRING(probed_ESSIDs,19,1000) FROM Clients WHERE
                                   (LENGTH(probed_ESSIDs) > 18) AND
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_period_ph . " " . $time_period_format_ph . ")) AND ?) AND NOT
                                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                                    station_MAC LIKE '_4:__:__:__:__:__' OR
                                    station_MAC LIKE '_8:__:__:__:__:__' OR
                                    station_MAC LIKE '_C:__:__:__:__:__')
                                    GROUP BY station_MAC;");
        mysqli_stmt_bind_param($stmt, "ss", $time_actual, $time_actual);

        // reset counters
        $i = 0;
        $time_actual = $time_from_ph;
        
        // loop whole time range
        while (strtotime($time_actual) <= strtotime($time_to_ph)) {
          
          // execute prepared MySQL statement
          mysqli_stmt_execute($stmt);

          $db_result = mysqli_stmt_get_result($stmt);

          unset($fingerprints);
          // fill (append to) fingerprints array
          if (mysqli_num_rows($db_result) > 0) {
            while ($db_row = mysqli_fetch_assoc($db_result)) {
              $fingerprints[] = $db_row["SUBSTRING(probed_ESSIDs,19,1000)"];
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
          } 

          $fingerprints_count = (count($fingerprints) > 0) ? count($fingerprints) : 0;

          // push new data into chart arrays
          $chart_wifi_unique_ph[$i]["x"]  = strtotime($time_actual)*1000;
          $chart_wifi_unique_ph[$i]["y"] += $fingerprints_count;

          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        } // end of local MAC while
        mysqli_stmt_close($stmt);
      } // end of show_wlan_ph

      // ----------------------------------------------------------------- Bluetooth
      if ($show_bt_ph == "1") {
          
        // prepare MySQL statement
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, "SELECT COUNT(DISTINCT BD_ADDR) AS TotalRows FROM Bluetooth WHERE
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_period_ph . " " . $time_period_format_ph . ")) AND ?);");
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
  fwrite($f_wifi_total_ph,  json_encode($chart_wifi_unique_ph)); // ONLY FOR DEBUGGING - RENAME TO wifi_total TO WORK AS INTENDED
  fwrite($f_bt_ph,          json_encode($chart_bt_ph));
  fclose($f_wifi_unique_ph);
  fclose($f_wifi_total_ph);
  fclose($f_bt_ph);

  // algorithm execution end
  $alg_end = time();
  echo "Algorithm finished in " . ($alg_end - $alg_start) . " seconds.";
}
?>
