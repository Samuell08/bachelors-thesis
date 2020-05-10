<?php
// TEXTOUT_HISTORY.php
// PHP code to be called from pass_history.php

session_start();

$session_id = session_id();

// infinite execution time
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
$timestamp_limit_chk_ph = $_SESSION["timestamp_limit_chk_ph"];
$timestamp_limit_ph     = $_SESSION["timestamp_limit_ph"];
$show_wlan_ph           = $_SESSION["show_wlan_ph"];
$show_bt_ph             = $_SESSION["show_bt_ph"];
$show_wlan_a_ph         = $_SESSION["show_wlan_a_ph"];
$show_wlan_bg_ph        = $_SESSION["show_wlan_bg_ph"];
$blacklist_wlan_ph      = $_SESSION["blacklist_wlan_ph"];
$blacklist_fp_ph        = $_SESSION["blacklist_fp_ph"];
$blacklist_bt_ph        = $_SESSION["blacklist_bt_ph"];
$specific_mac_chk_ph    = $_SESSION["specific_mac_chk_ph"];
$specific_mac_ph        = $_SESSION["specific_mac_ph"];
$specific_fp_chk_ph     = $_SESSION["specific_fp_chk_ph"];
$specific_fp_ph         = $_SESSION["specific_fp_ph"];
$specific_bt_chk_ph     = $_SESSION["specific_bt_chk_ph"];
$specific_bt_ph         = $_SESSION["specific_bt_ph"];

function is_anagram($string1, $string2) {
  if (count_chars($string1, 1) == count_chars($string2, 1))
    return 1;
  else
    return 0;
}

// function accepts list of keys (eg. MAC addresses) and compares
// it to blacklist (echoing 'Blacklisted' instead of timestamps)
// returns:  0 - key not blacklisted
//           1 - key blacklisted
//          -1 - unknown type
function blacklisted($type, $key, $blacklist) {
  
  $blacklist_wlan_chk_ph  = $_SESSION["blacklist_wlan_chk_ph"];
  $blacklist_fp_chk_ph    = $_SESSION["blacklist_fp_chk_ph"];
  $blacklist_bt_chk_ph    = $_SESSION["blacklist_bt_chk_ph"];

  $blacklist_exploded = explode(",", $blacklist);
  switch ($type) {
    case "wifi_global":
      if ($blacklist_wlan_chk_ph == "1") {
        foreach ($blacklist_exploded as $blacklist_key => $blacklist_value) {
          if ($key == $blacklist_value) {
            // found key in blacklist
            return 1;
          }
        }
      }
      break;
    
    case "wifi_local":
      if ($blacklist_fp_chk_ph == "1") {
        $essids = explode(",", $key);
        $essids_blacklisted = 0;
        foreach ($blacklist_exploded as $blacklist_key => $blacklist_value) {
          foreach ($essids as $essid_key => $essid_value) {
            if ($essid_value == $blacklist_value) {
              $essids_blacklisted++;
            }
          }
        }
        if ($essids_blacklisted == count($essids)) {
          // fingerprint is made of blacklisted essids only
          return 1;
        }
      }
      break;

    case "bt":
      if ($blacklist_bt_chk_ph == "1") {
        foreach ($blacklist_exploded as $blacklist_key => $blacklist_value) {
          if ($key == $blacklist_value) {
            // found key in blacklist
            return 1;
          }
        }
      }
      break;

    default:
      return -1;
  }
  return 0;
}

// function accepts list of keys (eg. MAC addresses) and fills
// chart arrays with passages
function process_keys($type, $db_q_standard, $keys,
                      $blacklist, $db_conn_s, $threshold,
                      $timestamp_limit, $time_from, $time_to,
                      $time_increment, &$chart_unique, &$chart_total,
                      &$ignored, &$blacklisted) {
  
  // customize algorithm to specific keys type
  switch ($type) {
    case "wifi_global":
      $query = "SELECT last_time_seen FROM Clients WHERE
               (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                AND " . $db_q_standard . " AND (station_MAC = ?);"; break;
    case "wifi_local": 
      $query = "SELECT last_time_seen FROM Clients WHERE
               (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                AND " . $db_q_standard . " AND (SUBSTRING(probed_ESSIDs,19,1000) = ?);"; break;
    case "bt":
      $query = "SELECT last_time_seen FROM Bluetooth WHERE
               (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                AND (BD_ADDR = ?);"; break;
    default:
      echo "function process_keys ERROR: Unknown type: " . $type . "<br>";
      return -1;
  }
  $stmt = mysqli_stmt_init($db_conn_s);
  mysqli_stmt_prepare($stmt, $query);
  mysqli_stmt_bind_param($stmt, "s", $keys_value);
  
  echo "<table style=\"border-collapse:collapse\">";

  foreach ($keys as $keys_key => $keys_value) {
    // output keys with timestamps table
    echo "<tr class=\"info\">";
    echo "<td><tt>" . $keys_value . "&nbsp&nbsp&nbsp&nbsp&nbsp</tt></td>";
    
    // Blacklist processing
    $blacklist_retval = blacklisted($type, $keys_value, $blacklist);
    switch ($blacklist_retval) {
      case 0:
        break;
      case 1:
        // blacklisted - end processing of key and go to next
        echo "<td><tt style=\"color:orangered;\"><b>";
        echo "Blacklisted";
        echo "</b></tt></td>";
        echo "</tr>";
        $blacklisted++;
        continue 2; // foreach keys
        break;
      default:
        echo "function process_keys ERROR: error while processing blacklist";
        return -1;
    }
    
    // process MySQL query result - fill timestamps array for given key
    mysqli_stmt_execute($stmt);
    $db_result = mysqli_stmt_get_result($stmt);
    unset($key_timestamps);
    if (mysqli_num_rows($db_result) <= $timestamp_limit) {
      while ($db_row = mysqli_fetch_array($db_result, MYSQLI_ASSOC)) {
        $key_timestamps[] = $db_row["last_time_seen"];
      }
    } else {
      // end processing of key - go to next
      echo "<td><tt style=\"color:orangered;\"><b>";
      echo "Number of timestamps over limit";
      echo "</b></tt></td>";
      echo "</tr>";
      $ignored++;
      continue; // foreach keys
    }
    mysqli_free_result($db_result);

    // build passages subarray based on key timestamps
    // open timestamps <td>
    echo "<td><tt>";
    // first is always bold
    echo "<b>" . $key_timestamps[0] . "</b> | ";
    $key_passages[0] = $key_timestamps[0];
    // loop every timestamp for current key
    for ($i = 1; $i < count($key_timestamps); $i++){
      if ((strtotime($key_timestamps[$i]) - strtotime($key_timestamps[$i-1]) > $threshold)) {
        // output bold timestamp
        echo "<b>" . $key_timestamps[$i] . "</b> | ";
        $key_passages[] = $key_timestamps[$i];
      } else {
        // output normal timestamp
        echo $key_timestamps[$i] . " | ";
      }
    }
    // close timestamps <td>
    echo "</tt></td>";
    echo "</tr>";

    // fill chart arrays based on passages subarray
    $unique = 1;
    // reset counters
    $i = 0;
    $time_actual = $time_from;
    while (strtotime($time_actual) <= (strtotime($time_to) - $time_increment)) {
      // calculate next time value
      $time_next = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
      // passage in current time step?
      foreach ($key_passages as $pass_key => $pass_value){
        if ((strtotime($pass_value) > strtotime($time_actual)) && (strtotime($pass_value) <= strtotime($time_next))){
          $chart_total[$i]["y"] += 1;
          if ($unique) {
            $chart_unique[$i]["y"] += 1;
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
    // moving to next key
    unset($key_passages);
  } // end foreach keys
  echo "</table><br>";
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
} elseif ($show_wlan_ph == "1" and $show_wlan_a_ph != "1" and $show_wlan_bg_ph != "1") {
  echo "<p class=\"warning\">Wi-Fi Standard not selected.</p>";
} else {

  // algorithm execution start
  $alg_start = time();

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

  // "disable" limit
  if ($timestamp_limit_chk_ph != "1"){
    $timestamp_limit_ph = PHP_INT_MAX;
  }

  // wifi standard
  if ($show_wlan_ph == "1") {
    if ($show_wlan_a_ph == "1" and $show_wlan_bg_ph == "1") {
      $db_q_standard = "(standard = 'a' OR standard = 'bg')";
    } else {
      if ($show_wlan_a_ph == "1") {
        $db_q_standard = "(standard = 'a')";
      }
      if ($show_wlan_bg_ph == "1") {
        $db_q_standard = "(standard = 'bg')";
      }
    }
  }
  
  // text output
  echo  "Showing results from " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_from_ph)) . "</b>" .
        " to " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_to_ph)) . "</b>" .
        " with step of " . "<b>" . $time_step_ph . " " . strtolower($time_step_format_ph) . "(s)" . "</b>" .
        "<br><br>";

  if ($show_wlan_ph == "1") {
    if ($specific_mac_chk_ph == "1") {
      echo "Looked only for this global MAC addresses: <b>" . $specific_mac_ph . "</b><br>";
    }
    if ($specific_fp_chk_ph == "1") {
      echo "Looked only for this ESSID combination: <b>" . $specific_fp_ph . "</b><br>";
    }
  }
  if ($show_bt_ph == "1") {
    if ($specific_bt_chk_ph == "1") {
      echo "Looked only for this BD_ADDR addresses: <b>" . $specific_bt_ph . "</b><br>";
    }
  }
  echo "<br>";

  // prepare chart arrays
  if ($show_wlan_ph == "1") {
    $i = 0;
    $time_actual = date('Y-m-d H:i:s', (strtotime($time_from_ph) + $time_increment));
    while (strtotime($time_actual) <= strtotime($time_to_ph)) {
      $chart_wifi_unique_ph[$i]["x"] = strtotime($time_actual)*1000;
      $chart_wifi_unique_ph[$i]["y"] = 0;
      $chart_wifi_total_ph[$i]["x"] = strtotime($time_actual)*1000;
      $chart_wifi_total_ph[$i]["y"] = 0;
      $i += 1;
      $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
    }
  }
  if ($show_bt_ph == "1") { 
    $i = 0;
    $time_actual = date('Y-m-d H:i:s', (strtotime($time_from_ph) + $time_increment));
    while (strtotime($time_actual) <= strtotime($time_to_ph)) {
      $chart_bt_unique_ph[$i]["x"] = strtotime($time_actual)*1000;
      $chart_bt_unique_ph[$i]["y"] = 0;
      $chart_bt_total_ph[$i]["x"] = strtotime($time_actual)*1000;
      $chart_bt_total_ph[$i]["y"] = 0;
      $i += 1;
      $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
    }
  }

  // fill key arrays
  unset($macs);
  unset($fingerprints);
  unset($bd_addrs);
  $mac_glbl_passed = 0;
  $mac_local_passed = 0;
  $bt_passed = 0;
  foreach ($db_source_ph as $key => $value) {
    $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);

    if ($specific_mac_chk_ph == "1" or $specific_fp_chk_ph == "1" or $specific_bt_chk_ph == "1") {

      // process only specific keys

      if ($show_wlan_ph == "1") {
        if ($specific_mac_chk_ph == "1") {
          // macs array contains only specific global MAC addresses
          foreach (explode(",", $specific_mac_ph) as $specific_key => $specific_value) {
            $macs[] = $specific_value;
          }
          $mac_glbl_passed = count($macs);
        }
        if ($specific_fp_chk_ph == "1") {
          // fingerprints array contains only specific ESSID combination
          $fingerprints[0] = $specific_fp_ph;
          $mac_local_passed = 1;
        }
      }

      if ($show_bt_ph == "1") {
        if ($specific_bt_chk_ph == "1") {
          // bd_addrs array contains only specific BD_ADDR addresses
          foreach (explode(",", $specific_bt_ph) as $specific_key => $specific_value) {
            $bd_addrs[] = $specific_value;
          }
          $bt_passed = count($bd_addrs);
        }
      }

    } else {

      // process everything

      if ($show_wlan_ph == "1") {

        // GLOBAL MAC
        // every unique global MAC in time range 
        $db_q = "SELECT station_MAC FROM Clients WHERE
                (last_time_seen BETWEEN '" . $time_from_ph . "' AND '" . $time_to_ph . "') AND
                (station_MAC LIKE '_0:__:__:__:__:__' OR
                 station_MAC LIKE '_4:__:__:__:__:__' OR
                 station_MAC LIKE '_8:__:__:__:__:__' OR
                 station_MAC LIKE '_C:__:__:__:__:__') AND " . $db_q_standard .
                "GROUP BY station_MAC;";
        $db_result = mysqli_query($db_conn_s, $db_q);
        $mac_glbl_passed += mysqli_num_rows($db_result);
        // append result to macs
        if (mysqli_num_rows($db_result) > 0) {
          while ($db_row = mysqli_fetch_assoc($db_result)) {
            $macs[] = $db_row["station_MAC"];
          }
        }
        mysqli_free_result($db_result);

        // LOCAL MAC
        // every unique local MAC fingerprint
        $db_q = "SELECT SUBSTRING(probed_ESSIDs,19,1000) FROM Clients WHERE
                (LENGTH(probed_ESSIDs) > 18) AND
                (last_time_seen BETWEEN '" . $time_from_ph . "' AND '" . $time_to_ph . "') AND NOT
                (station_MAC LIKE '_0:__:__:__:__:__' OR
                 station_MAC LIKE '_4:__:__:__:__:__' OR
                 station_MAC LIKE '_8:__:__:__:__:__' OR
                 station_MAC LIKE '_C:__:__:__:__:__') AND " . $db_q_standard .
                "GROUP BY SUBSTRING(probed_ESSIDs,19,1000);";
        $db_result = mysqli_query($db_conn_s, $db_q);
        // append result to fingerprints
        if (mysqli_num_rows($db_result) > 0) {
          while ($db_row = mysqli_fetch_assoc($db_result)) {
            $fingerprints[] = $db_row["SUBSTRING(probed_ESSIDs,19,1000)"];
          }
          mysqli_free_result($db_result);
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
          $mac_local_passed = count($fingerprints);
        }
      } // end of show_wlan_ph

      if ($show_bt_ph == "1") {

        // every unique BD_ADDR in time range
        $db_q = "SELECT BD_ADDR FROM Bluetooth WHERE
                (last_time_seen BETWEEN '" . $time_from_ph . "' AND '" . $time_to_ph . "')
                 GROUP BY BD_ADDR;";
        $db_result = mysqli_query($db_conn_s, $db_q);
        $bt_passed += mysqli_num_rows($db_result);
        // append result to bd_addrs
        if (mysqli_num_rows($db_result) > 0) {
          while ($db_row = mysqli_fetch_assoc($db_result)) {
            $bd_addrs[] = $db_row["BD_ADDR"];
          }
        }
        mysqli_free_result($db_result);
      } // end of show_bt_ph
    } // end of else clause of specific keys
  } // end of foreach DB
  
  echo "<b>Statistics table is located at the bottom of the page</b>" . "<br><br>";

  $total_passed = $mac_glbl_passed + $mac_local_passed + $bt_passed;

  // ignored due to exceeding timestamp limit
  $mac_glbl_ignored  = 0;
  $mac_local_ignored = 0;
  $bt_ignored        = 0;
  // ignored due to being blacklisted
  $mac_glbl_blacklisted  = 0;
  $mac_local_blacklisted = 0;
  $bt_blacklisted        = 0;

  // keys processing
  if ($total_passed > 0) {
    foreach ($db_source_ph as $key => $value) {
      echo "<b>Database: " . $value . "</b><br>";
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);
      if ($show_wlan_ph == "1") {
        if ($mac_glbl_passed > 0) {
          echo "Wi-Fi devices with global MAC address:<br>";
          process_keys("wifi_global", $db_q_standard, $macs,
                       $blacklist_wlan_ph, $db_conn_s, $threshold_seconds,
                       $timestamp_limit_ph, $time_from_ph, $time_to_ph,
                       $time_increment, $chart_wifi_unique_ph, $chart_wifi_total_ph,
                       $mac_glbl_ignored, $mac_glbl_blacklisted);
        }
          if ($mac_local_passed > 0) {
          echo "Wi-Fi devices with local MAC address:<br>";
          process_keys("wifi_local", $db_q_standard, $fingerprints,
                       $blacklist_fp_ph, $db_conn_s, $threshold_seconds,
                       $timestamp_limit_ph, $time_from_ph, $time_to_ph,
                       $time_increment, $chart_wifi_unique_ph, $chart_wifi_total_ph,
                       $mac_local_ignored, $mac_local_blacklisted);
        }
      }
      if ($show_bt_ph == "1") {
        if ($bt_passed > 0) {
          echo "Bluetooth devices:<br>";        
          process_keys("bt", "1", $bd_addrs,
                       $blacklist_bt_ph, $db_conn_s, $threshold_seconds,
                       $timestamp_limit_ph, $time_from_ph, $time_to_ph,
                       $time_increment, $chart_bt_unique_ph, $chart_bt_total_ph,
                       $bt_ignored, $bt_blacklisted);
        }
      }
    }
  }
  
  // text output table
  if ($show_wlan_ph == "1") {
    echo "<b>Wi-Fi</b><br>";
    echo "<table class=\"textout\">";
    echo "<tr class=\"textout\"><td>" . "Devices with global MAC address:" .
                                        "</td><td>" .
                                        $mac_glbl_passed . " - " .
                                        $mac_glbl_ignored . " - " .
                                        $mac_glbl_blacklisted . " = " .
                                        "<b>" . ($mac_glbl_passed-$mac_glbl_ignored-$mac_glbl_blacklisted) .
                                        "</b></td></tr>";
    echo "<tr class=\"textout\"><td>" . "Devices with local MAC address:" .
                                        "</td><td>" . $mac_local_passed . " - " .
                                        $mac_local_ignored . " - " .
                                        $mac_local_blacklisted . " = " .
                                        "<b>" . ($mac_local_passed-$mac_local_ignored-$mac_local_blacklisted) .
                                        "</b></td></tr>";
    echo "</table>";
  }
  if ($show_bt_ph == "1") {
    echo "<b>Bluetooth</b><br>";
    echo "<table class=\"textout\">";
    echo "<tr class=\"textout\"><td>" . "Devices:" .
                                        "</td><td>" .
                                        $bt_passed . " - " .
                                        $bt_ignored . " - " .
                                        $bt_blacklisted . " = " .
                                        "<b>" . ($bt_passed-$bt_ignored-$bt_blacklisted) .
                                        "</b></td></tr>";
    echo "</table>";
  }
  echo "<br>" . "<b>Legend:</b> <i>passed - over limit - blacklisted = <b>processed</b></i>" . "<br><br>";

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
