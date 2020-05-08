<?php
// TEXTOUT_HISTORY.php
// PHP code to be called from range_history.php

session_start();

$session_id = session_id();

// infinite execution time
set_time_limit(0);

// get session variables
// database connection
$db_server     = $_SESSION["db_server"];
$db_user       = $_SESSION["db_user"];
$db_pass       = $_SESSION["db_pass"];
$db_source_rh  = $_SESSION["db_source_rh"];
// settings
$time_from_rh           = $_SESSION["time_from_rh"];
$time_to_rh             = $_SESSION["time_to_rh"];
$time_step_rh           = $_SESSION["time_step_rh"];
$time_step_format_rh    = $_SESSION["time_step_format_rh"];
$time_period_rh         = $_SESSION["time_period_rh"];
$time_period_format_rh  = $_SESSION["time_period_format_rh"];
$show_wlan_rh           = $_SESSION["show_wlan_rh"];
$show_bt_rh             = $_SESSION["show_bt_rh"];
$show_wlan_a_rh         = $_SESSION["show_wlan_a_rh"];
$show_wlan_bg_rh        = $_SESSION["show_wlan_bg_rh"];
$specific_addr_chk_rh   = $_SESSION["specific_addr_chk_rh"];
$specific_addr_rh       = $_SESSION["specific_addr_rh"];

// functions
function is_anagram($string1, $string2) {
  if (count_chars($string1, 1) == count_chars($string2, 1))
    return 1;
  else
    return 0;
}

// check if user input is correct
if ($db_source_rh == NULL) {
  echo "<p class=\"warning\">Source database(s) not selected.</p>";
} elseif ($time_period_format_rh == NULL) {
  echo "<p class=\"warning\">Time Period format Minute(s)/Hour(s) not selected.</p>";
} elseif ($time_period_rh == NULL) {
  echo "<p class=\"warning\">Invalid time period.</p>";
} elseif ((!($show_wlan_rh == "1")) and (!($show_bt_rh == "1"))) {
  echo "<p class=\"warning\">No data selected to show.</p>";
} elseif (strtotime($time_from_rh) > strtotime($time_to_rh)) {
  echo "<p class=\"warning\">Time range \"From\" is later in time than \"To\".</p>";
} elseif (strtotime($time_to_rh) > time()) {
  echo "<p class=\"warning\">Time range \"To\" is in the future.</p>";
} elseif ($show_wlan_rh == "1" and $show_wlan_a_rh != "1" and $show_wlan_bg_rh != "1") {
  echo "<p class=\"warning\">Wi-Fi Standard not selected.</p>";
} else {

  // algorithm execution start
  $alg_start = time();

  // reset variables before queries
  $mac_glbl   = 0;
  $mac_local  = 0;
  $bt_total   = 0;
  $fingerprints_count = 0;
  $db_q_standard = "1";

  // calculate time increment
  switch ($time_step_format_rh) {
  case "SECOND":
    $time_increment = $time_step_rh;
    break;
  case "MINUTE":
    $time_increment = $time_step_rh*60;
    break;
  case "HOUR":
    $time_increment = $time_step_rh*3600;
    break;
  }    

  if ($show_wlan_rh == "1") {
    // standard
    if ($show_wlan_a_rh == "1" and $show_wlan_bg_rh == "1") {
      $db_q_standard = "(standard = 'a' OR standard = 'bg')";
    } else {
      if ($show_wlan_a_rh == "1") {
        $db_q_standard = "(standard = 'a')";
      }
      if ($show_wlan_bg_rh == "1") {
        $db_q_standard = "(standard = 'bg')";
      }
    }
  }
  
  // text output
  echo  "Showing results from " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_from_rh)) . "</b>" .
    " to " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_to_rh)) . "</b>" .
    " with period of " . "<b>" . $time_period_rh . " " . strtolower($time_period_format_rh) . "(s)" . "</b>" . "<br><br>";

  if ($specific_addr_chk_rh == 1) {
   
    // look only for specific MAC/BD_ADDR address
    
    echo "Looked only for this MAC/BD_ADDR address: " . $specific_addr_rh . "<br><br>";

    foreach ($db_source_rh as $key => $value) {

      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // ---------------------------------------------------------------------- WIFI
      if ($show_wlan_rh == "1") {

        // prepare MySQL statement
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, "SELECT COUNT(DISTINCT station_MAC) AS TotalRows FROM Clients WHERE
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_period_rh . " " . $time_period_format_rh . ")) AND ?) AND
                                   (station_MAC = '" . $specific_addr_rh . "') AND " . $db_q_standard . ";");
        mysqli_stmt_bind_param($stmt, "ss", $time_actual, $time_actual);
        mysqli_stmt_bind_result($stmt, $mac_glbl);

        // reset counters
        $i = 0;
        $time_actual = $time_from_rh;
        
        // loop whole time range
        while (strtotime($time_actual) <= strtotime($time_to_rh)) {

          // execute prepared MySQL statement
          mysqli_stmt_execute($stmt);
          // save MySQL query result to mac_glbl
          mysqli_stmt_fetch($stmt);

          // push new data into chart arrays
          $chart_wifi_bot_rh[$i]["x"]  = strtotime($time_actual)*1000;
          $chart_wifi_bot_rh[$i]["y"] += $mac_glbl;

          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        } // end of global MAC while
        mysqli_stmt_close($stmt);
      } // end of show_wlan_rh

      // ----------------------------------------------------------------- Bluetooth
      if ($show_bt_rh == "1") {
          
        // prepare MySQL statement
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, "SELECT COUNT(DISTINCT BD_ADDR) AS TotalRows FROM Bluetooth WHERE
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_period_rh . " " . $time_period_format_rh . ")) AND ?) AND
                                   (BD_ADDR = '" . $specific_addr_rh . "');");
        mysqli_stmt_bind_param($stmt, "ss", $time_actual, $time_actual);
        mysqli_stmt_bind_result($stmt, $bt_total);

        // reset counters
        $i = 0;
        $time_actual = $time_from_rh;
        
        // loop whole time range
        while (strtotime($time_actual) <= strtotime($time_to_rh)) {

          // execute prepared MySQL statement
          mysqli_stmt_execute($stmt);
          // save MySQL query result to bt_total
          mysqli_stmt_fetch($stmt);

          // push new data into chart arrays
          $chart_bt_rh[$i]["x"]  = strtotime($time_actual)*1000;
          $chart_bt_rh[$i]["y"] += $bt_total;

          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        } // end of Bluetooth while
        mysqli_stmt_close($stmt);
      } // end of show_bt_rh
    } // end of foreach DB



  } else {



    // look for any MAC/BD_ADDR address
    
    foreach ($db_source_rh as $key => $value) {

      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

      // ---------------------------------------------------------------------- WIFI
      if ($show_wlan_rh == "1") {

        // GLOBAL MAC LOOP
        
        // prepare MySQL statement
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, "SELECT COUNT(DISTINCT station_MAC) AS TotalRows FROM Clients WHERE
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_period_rh . " " . $time_period_format_rh . ")) AND ?) AND
                                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                                    station_MAC LIKE '_4:__:__:__:__:__' OR
                                    station_MAC LIKE '_8:__:__:__:__:__' OR
                                    station_MAC LIKE '_C:__:__:__:__:__') AND " . $db_q_standard . ";");
        mysqli_stmt_bind_param($stmt, "ss", $time_actual, $time_actual);
        mysqli_stmt_bind_result($stmt, $mac_glbl);

        // reset counters
        $i = 0;
        $time_actual = $time_from_rh;
        
        // loop whole time range
        while (strtotime($time_actual) <= strtotime($time_to_rh)) {

          // execute prepared MySQL statement
          mysqli_stmt_execute($stmt);
          // save MySQL query result to mac_glbl
          mysqli_stmt_fetch($stmt);

          // push new data into chart arrays
          $chart_wifi_bot_rh[$i]["x"]  = strtotime($time_actual)*1000;
          $chart_wifi_bot_rh[$i]["y"] += $mac_glbl;

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
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_period_rh . " " . $time_period_format_rh . ")) AND ?) AND NOT
                                   (station_MAC LIKE '_0:__:__:__:__:__' OR
                                    station_MAC LIKE '_4:__:__:__:__:__' OR
                                    station_MAC LIKE '_8:__:__:__:__:__' OR
                                    station_MAC LIKE '_C:__:__:__:__:__') AND " . $db_q_standard .
                                   "GROUP BY station_MAC;");
        mysqli_stmt_bind_param($stmt, "ss", $time_actual, $time_actual);

        // reset counters
        $i = 0;
        $time_actual = $time_from_rh;
        
        // loop whole time range
        while (strtotime($time_actual) <= strtotime($time_to_rh)) {
          
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
          $chart_wifi_top_rh[$i]["x"]  = strtotime($time_actual)*1000;
          $chart_wifi_top_rh[$i]["y"] += $fingerprints_count;

          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        } // end of local MAC while
        mysqli_stmt_close($stmt);
      } // end of show_wlan_rh

      // ----------------------------------------------------------------- Bluetooth
      if ($show_bt_rh == "1") {
          
        // prepare MySQL statement
        $stmt = mysqli_stmt_init($db_conn_s);
        mysqli_stmt_prepare($stmt, "SELECT COUNT(DISTINCT BD_ADDR) AS TotalRows FROM Bluetooth WHERE
                                   (last_time_seen BETWEEN (DATE_SUB(?, INTERVAL " . $time_period_rh . " " . $time_period_format_rh . ")) AND ?);");
        mysqli_stmt_bind_param($stmt, "ss", $time_actual, $time_actual);
        mysqli_stmt_bind_result($stmt, $bt_total);

        // reset counters
        $i = 0;
        $time_actual = $time_from_rh;
        
        // loop whole time range
        while (strtotime($time_actual) <= strtotime($time_to_rh)) {

          // execute prepared MySQL statement
          mysqli_stmt_execute($stmt);
          // save MySQL query result to bt_total
          mysqli_stmt_fetch($stmt);

          // push new data into chart arrays
          $chart_bt_rh[$i]["x"]  = strtotime($time_actual)*1000;
          $chart_bt_rh[$i]["y"] += $bt_total;

          // increment counters
          $i += 1;
          $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
        } // end of Bluetooth while
        mysqli_stmt_close($stmt);
      } // end of show_bt_rh
    } // end of foreach DB
  } // end of if specific_addr

  // write completed chart arrays to json files
  $json_dir = "../../json";
  if (!file_exists($json_dir)){ mkdir($json_dir); }
  $f_bot_rh = fopen($json_dir . "/chart_wifi_bot_rh_" . $session_id, "w");
  $f_top_rh = fopen($json_dir . "/chart_wifi_top_rh_" . $session_id, "w");
  $f_bt_rh  = fopen($json_dir . "/chart_bt_rh_" . $session_id, "w");
  fwrite($f_bot_rh, json_encode($chart_wifi_bot_rh));
  fwrite($f_top_rh, json_encode($chart_wifi_top_rh));
  fwrite($f_bt_rh, json_encode($chart_bt_rh));
  fclose($f_bot_rh);
  fclose($f_top_rh);
  fclose($f_bt_rh);

  // algorithm execution end
  $alg_end = time();
  $mem_peak = memory_get_peak_usage();
  $mem_peak = $mem_peak / 1000000; // MB
  echo "Algorithm finished in " . ($alg_end - $alg_start) . " seconds.<br>";
  echo "Memory usage peak: " . round($mem_peak, 2) . " MB";
}
?>
