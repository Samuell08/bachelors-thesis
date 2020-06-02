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
$specific_bt_chk_ph     = $_SESSION["specific_bt_chk_ph"];
$specific_bt_ph         = $_SESSION["specific_bt_ph"];

class Passenger {
  public $key = NULL; // MAC, fingerprint or BD_ADDR
  public $blacklisted = 0; // if 1, key is blacklisted
  public $over_timestamp_limit = 0; // if 1, numer of timestamps is over limit
  public $passages = NULL; // 2D array timestamp | passage?
}

function is_anagram($string1, $string2) {
  if (count_chars($string1, 1) == count_chars($string2, 1))
    return 1;
  else
    return 0;
}

// Function accepts time parameters from Settings form and builds
// array that will be accepted by charts.
// axis x - time
// axis y - values (default = 0)
function prepare_chart_array($time_from, $time_to, $time_increment) {
  $i = 0;
  $time_actual = date('Y-m-d H:i:s', (strtotime($time_from) + $time_increment));
  while (strtotime($time_actual) <= strtotime($time_to)) {
    $chart_array[$i]["x"] = strtotime($time_actual)*1000;
    $chart_array[$i]["y"] = 0;
    $i += 1;
    $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
  }
  return $chart_array;
}

// Function accepts time parameters and wlan standard from Settings form and 
// returns all GLOBAL MAC addresses within given time range.
function get_macs($db_conn, $time_from, $time_to, $db_q_standard, &$macs) {
  $db_q = "SELECT station_MAC FROM Clients WHERE
          (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "') AND
          (station_MAC LIKE '_0:__:__:__:__:__' OR
           station_MAC LIKE '_4:__:__:__:__:__' OR
           station_MAC LIKE '_8:__:__:__:__:__' OR
           station_MAC LIKE '_C:__:__:__:__:__') AND " . $db_q_standard .
          "GROUP BY station_MAC;";
  $db_result = mysqli_query($db_conn, $db_q);
  // append result to macs
  if (mysqli_num_rows($db_result) > 0) {
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      $macs[] = $db_row["station_MAC"];
    }
  }
  mysqli_free_result($db_result);
}

// function finds every probed ESSIDs fingerprint and returns
// 2D array (2nd dimension containing found anagrams)
function get_fingerprints($mode, $db_conn_s, $time_from_ph, $time_to_ph, $db_q_standard, &$fingerprints) {

  if ($mode == "specific") {
    $specific_mode_fp_ph   = $_SESSION["specific_mode_fp_ph"];
    $specific_fp_ph        = $_SESSION["specific_fp_ph"];
  }

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
      $probed_essids = $db_row["SUBSTRING(probed_ESSIDs,19,1000)"];
      switch ($mode) {
      
        case "all":
          $fingerprints[][0] = $probed_essids;
          break;

        case "specific": 
          switch ($specific_mode_fp_ph) {
            case "EXACT":
              // fingerprint must be an anagram of specific ESSID list
              if (is_anagram($probed_essids, $specific_fp_ph)){
                $fingerprints[][0] = $probed_essids;
	            }
              break;
	          case "ATLEAST":
              // fingerprint must contain all specified ESSIDs or more
              $essids = explode(",", $probed_essids);
              $specific_exploded = explode(",", $specific_fp_ph);
              $essids_specified = 0;
              foreach ($specific_exploded as $specific_key => $specific_value) {
                foreach ($essids as $essid_key => $essid_value) {
                  if ($essid_value == $specific_value) {
                    $essids_specified++;
                  }
                }
              }
              if ($essids_specified == count($specific_exploded)){
                $fingerprints[][0] = $probed_essids;
              }
              break;
            default:
              exit("function get_fingerprints ERROR: Unknown specific ESSID mode: " . $specific_mode_fp_ph);
          }
          break;
        default:
          exit("function get_fingerprints ERROR: Unknown mode: " . $mode);
      }
    }
    mysqli_free_result($db_result);
    // merge anagrams into 2D array
    for ($x1 = 0; $x1 < count($fingerprints); $x1++){
      $fp_col = 1;
      if ($fingerprints[$x1][0] != NULL){
        for ($x2 = 0; $x2 < count($fingerprints); $x2++){
          if ($x1 != $x2) {
            if (is_anagram($fingerprints[$x1][0], $fingerprints[$x2][0])){
              $fingerprints[$x1][$fp_col] = $fingerprints[$x2][0];
              unset($fingerprints[$x2][0]);
              $fp_col++;
            }
          }
        }
      }
    }
  // filter merged array
  $fingerprints = array_filter($fingerprints);
  $fingerprints = array_values($fingerprints);
  }
}

// Function accepts time parameters from Settings form and
// returns all BD_ADDR addresses within given time range.
function get_bd_addrs($db_conn, $time_from, $time_to, &$bd_addrs) {
  $db_q = "SELECT BD_ADDR FROM Bluetooth WHERE
          (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
           GROUP BY BD_ADDR;";
  $db_result = mysqli_query($db_conn, $db_q);
  // append result to bd_addrs
  if (mysqli_num_rows($db_result) > 0) {
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      $bd_addrs[] = $db_row["BD_ADDR"];
    }
  }
  mysqli_free_result($db_result);
}

function find_passages($timestamps, $threshold) {
  $passages[0] = array($timestamps[0], 1);
  for ($i = 1; $i < count($timestamps); $i++){
    if ((strtotime($timestamps[$i]) - strtotime($timestamps[$i-1]) > $threshold)) {
      // timestamp is a passage
      $passages[] = array($timestamps[$i], 1);
    } else {
      $passages[] = array($timestamps[$i], 0);
    }
  }
  return $passages;
}

// function accepts list of keys (eg. MAC addresses) and compares
// it to blacklist (echoing 'Blacklisted' instead of timestamps)
// returns:  0 - key not blacklisted
//           1 - key blacklisted
//          -1 - unknown type
function blacklisted($type, $key, $blacklist) {
  
  $blacklist_wlan_chk_ph  = $_SESSION["blacklist_wlan_chk_ph"];
  $blacklist_fp_chk_ph    = $_SESSION["blacklist_fp_chk_ph"];
  $blacklist_mode_fp_ph   = $_SESSION["blacklist_mode_fp_ph"];
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
        switch ($blacklist_mode_fp_ph) {
          case "ALL":
            // fingerprint is made of blacklisted essids only
            if ($essids_blacklisted == count($essids)) { return 1; }
            break;
          case "ONE":
            // fingerprint contains at least one blacklisted essid
            if ($essids_blacklisted > 0) { return 1; }
            break;
          default:
            exit("function blacklisted ERROR: Unknown local MAC mode: " . $blacklist_mode_fp_ph);
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

// function accepts list of MAC/BD_ADDR addresses or 2D array of
// probed ESSIDs fingerprints and fills chart arrays with passages
function process_keys($type, $db_q_standard, $keys,
                      $blacklist, $db_conn_s, $threshold,
                      $timestamp_limit, $time_from, $time_to,
                      $time_increment, &$chart_unique, &$chart_total,
                      &$over_timestamp_limit, &$blacklisted) {
  
  $stmt = mysqli_stmt_init($db_conn_s);
  // customize algorithm to specific keys type
  switch ($type) {
    case "wifi_global":
      $query = "SELECT last_time_seen FROM Clients WHERE
               (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                AND " . $db_q_standard . " AND (station_MAC = ?);";
      mysqli_stmt_prepare($stmt, $query);
      mysqli_stmt_bind_param($stmt, "s", $keys_value);
      break;
    case "wifi_local": 
      $query = "SELECT last_time_seen FROM Clients WHERE
               (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                AND " . $db_q_standard . " AND (SUBSTRING(probed_ESSIDs,19,1000) = ?);";
      mysqli_stmt_prepare($stmt, $query);
      mysqli_stmt_bind_param($stmt, "s", $anagram_value);
      break;
    case "bt":
      $query = "SELECT last_time_seen FROM Bluetooth WHERE
               (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                AND (BD_ADDR = ?);";
      mysqli_stmt_prepare($stmt, $query);
      mysqli_stmt_bind_param($stmt, "s", $keys_value);
      break;
    default:
      exit("function process_keys ERROR: Unknown type: " . $type);
  }
  
  foreach ($keys as $keys_key => $keys_value) {

    // new Passenger
    $Passenger_key = new Passenger();
    $Passenger_key->key = $keys_value;

    // Blacklist processing
    if ($type == "wifi_local") {
      $blacklist_retval = blacklisted($type, $keys_value[0], $blacklist);
    } else {
      $blacklist_retval = blacklisted($type, $keys_value, $blacklist);
    }
    switch ($blacklist_retval) {
      case 0:
        break;
      case 1:
        // blacklisted - end processing of key and go to next
        // fill Passenger object and push it to output array
        $Passenger_key->blacklisted = 1;
        $Passenger_array[] = $Passenger_key;
        $blacklisted++;
        continue 2; // foreach keys
        break;
      default:
        exit("function process_keys ERROR: error while processing blacklist");
    }

    // get timestamps
    unset($key_timestamps);
    if ($type == "wifi_local") {
      // append all anagram timestamps to single array
      foreach ($keys_value as $anagram_key => $anagram_value){
        mysqli_stmt_execute($stmt);
        $db_result = mysqli_stmt_get_result($stmt);
        while ($db_row = mysqli_fetch_array($db_result, MYSQLI_ASSOC)) {
          $key_timestamps[] = $db_row["last_time_seen"];
        }
      }
    } else {
      // fill timestamps array for given key
      mysqli_stmt_execute($stmt);
      $db_result = mysqli_stmt_get_result($stmt);
      while ($db_row = mysqli_fetch_array($db_result, MYSQLI_ASSOC)) {
        $key_timestamps[] = $db_row["last_time_seen"];
      }
    
    }
    mysqli_free_result($db_result);

    // number of timestamps over limit?
    if (count($key_timestamps) > $timestamp_limit) {
      // over limit - end processing of key and go to next
      // fill Passenger object and push it to output array
      $Passenger_key->over_timestamp_limit = 1;
      $Passenger_array[] = $Passenger_key;
      $over_timestamp_limit++;
      continue; // foreach keys
    }
    sort($key_timestamps);

    $key_passages = find_passages($key_timestamps, $threshold);

    // fill Passenger object and push it to output array
    $Passenger_key->passages = $key_passages;
    $Passenger_array[] = $Passenger_key;

  } // end foreach keys

  return $Passenger_array;

}

function fill_chart_arrays($time_from, $time_to, $time_increment, $Passenger_array, &$chart_total, &$chart_unique) {
  foreach ($Passenger_array as $Passenger_key) {
    // fill chart arrays based on passages subarray
    $unique = 1;
    // reset counters
    $i = 0;
    $time_actual = $time_from;
    while (strtotime($time_actual) <= (strtotime($time_to) - $time_increment)) {
      // calculate next time value
      $time_next = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
      foreach ($Passenger_key->passages as $pass_key => $pass_value){
        if ($pass_value[1] == 1) {
          // passage in current time step?
          if ((strtotime($pass_value[0]) > strtotime($time_actual)) && (strtotime($pass_value[0]) <= strtotime($time_next))){
            $chart_total[$i]["y"] += 1;
            if ($unique) {
              $chart_unique[$i]["y"] += 1;
              $unique = 0;
            }
          }  
        }
      }
      // moving to next time step
      $unique = 1;
      // increment counters
      $i += 1;
      $time_actual = $time_next;
    }
  }
}

// Function accepts array of Passenger objects and prints it to HTML.
function print_Passenger_array($type, $Passenger_array, $time_from, $time_to, $time_increment) {

  switch($type){
    case "wifi_global":
      echo "Wi-Fi devices with global MAC address:<br>";
      break;
    case "wifi_local":
      echo "Wi-Fi devices with local MAC address:<br>";
      break;
    case "bt":
      echo "Bluetooth devices:<br>";
      break;
    default:
      die("function print_Passenger_array ERROR: Unknown type: " . $type);
  }

  echo "<table style=\"border-collapse:collapse\">";
  foreach ($Passenger_array as $Passenger_key) {

    switch($type){
      case "wifi_global":
      case "bt":
        $key = $Passenger_key->key;
        break;
      case "wifi_local":
        $key = $Passenger_key->key[0];
        break;
      default:
        die("function print_Passenger_array ERROR: Unknown type: " . $type);
    }

    echo "<tr class=\"info\">";
    echo "<td><tt>" . $key . "&nbsp&nbsp&nbsp&nbsp&nbsp</tt></td>";
    echo "<td>";
      if ($Passenger_key->blacklisted == 1) {

        echo "<tt style=\"color:orangered\">" . 
               "Blacklisted" . 
             "</tt>";

      } elseif ($Passenger_key->over_timestamp_limit == 1) {
        
        echo "<tt style=\"color:orangered\">" . 
               "Number of timestamps over limit" . 
             "</tt>";

      } else {

        if ($Passenger_key->passages == NULL) {
          echo "<tt style=\"color:orangered\">" . 
                 "None" . 
               "</tt>";
        } else {
          echo "<tt>";
          foreach ($Passenger_key->passages as $passages_p => $passages_v) {
            if ($passages_v[1] == 1) {
              echo "<b>" . $passages_v[0] . " | " . "</b>";
            } else {
              echo $passages_v[0] . " | ";
            }
          }
          echo "</tt>";
        }
      }
    echo "<td>";
    echo "</tr>";
  }
  echo "</table>";
  echo "<br>";
}

// Function accepts statistics data and prints it to HTML
function print_statistics_table($show_wlan, $show_bt, $passed_total,
                                $mac_glbl_passed, $mac_glbl_over_timestamp_limit, $mac_glbl_blacklisted,
                                $mac_local_passed, $mac_local_over_timestamp_limit, $mac_local_blacklisted,
                                $bt_passed, $bt_over_timestamp_limit, $bt_blacklisted) {
  if ($show_wlan == "1") {
    echo "<b>Wi-Fi</b><br>";
    echo "<table class=\"textout\">";
    echo "<tr class=\"textout\"><td>" . "Devices with global MAC address:" .
                                        "</td><td>" .
                                        $mac_glbl_passed . " - " .
                                        $mac_glbl_over_timestamp_limit . " - " .
                                        $mac_glbl_blacklisted . " = " .
                                        "<b>" . ($mac_glbl_passed-$mac_glbl_over_timestamp_limit-$mac_glbl_blacklisted) .
                                        "</b></td></tr>";
    echo "<tr class=\"textout\"><td>" . "Devices with local MAC address:" .
                                        "</td><td>" .
                                        $mac_local_passed . " - " .
                                        $mac_local_over_timestamp_limit . " - " .
                                        $mac_local_blacklisted . " = " .
                                        "<b>" . ($mac_local_passed-$mac_local_over_timestamp_limit-$mac_local_blacklisted) .
                                        "</b></td></tr>";
    echo "</table>";
  }
  if ($show_bt == "1") {
    echo "<b>Bluetooth</b><br>";
    echo "<table class=\"textout\">";
    echo "<tr class=\"textout\"><td>" . "Devices:" .
                                        "</td><td>" .
                                        $bt_passed . " - " .
                                        $bt_over_timestamp_limit . " - " .
                                        $bt_blacklisted . " = " .
                                        "<b>" . ($bt_passed-$bt_over_timestamp_limit-$bt_blacklisted) .
                                        "</b></td></tr>";
    echo "</table>";
  }

  echo "<br>" . "<b>Legend:</b> <i>passed - over timestamp limit - blacklisted = <b>processed</b></i>" . "<br><br>";
  
  echo "<b>Total number of devices passed: </b>" . $passed_total . "<br><br>";
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
  echo "<p class=\"warning\">Threshold format Second(s)/Minute(s)/Hour(s) not selected.</p>";
} elseif ($time_step_format_ph == NULL) {
  echo "<p class=\"warning\">Time step format Second(s)/Minute(s)/Hour(s) not selected.</p>";
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

  // prepare variables
  unset($macs);
  unset($fingerprints);
  unset($bd_addrs);
  $mac_glbl_passed = 0;
  $mac_local_passed = 0;
  $bt_passed = 0;
  $mac_glbl_over_timestamp_limit = 0;
  $mac_local_over_timestamp_limit = 0;
  $bt_over_timestamp_limit = 0;
  $mac_glbl_blacklisted = 0;
  $mac_local_blacklisted = 0;
  $bt_blacklisted = 0;

  // prepare chart arrays
  $chart_wifi_unique_ph = prepare_chart_array($time_from_ph, $time_to_ph, $time_increment);
  $chart_wifi_total_ph = prepare_chart_array($time_from_ph, $time_to_ph, $time_increment);
  $chart_bt_unique_ph = prepare_chart_array($time_from_ph, $time_to_ph, $time_increment);
  $chart_bt_total_ph = prepare_chart_array($time_from_ph, $time_to_ph, $time_increment);

  $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $db_source_ph);

  // specific or all keys?
  if ($specific_mac_chk_ph == "1" or $specific_fp_chk_ph == "1" or $specific_bt_chk_ph == "1") {

    // process only specific keys

    if ($show_wlan_ph == "1") {
      if ($specific_mac_chk_ph == "1") {
        // macs array contains only specific global MAC addresses
        foreach (explode(",", $specific_mac_ph) as $specific_key => $specific_value) {
          $macs[] = $specific_value;
        }
      }
      if ($specific_fp_chk_ph == "1") {
        // fingerprints array contains only specific ESSID combination
        get_fingerprints("specific", $db_conn_s, $time_from_ph, $time_to_ph, $db_q_standard, $fingerprints);
      }
    }

    if ($show_bt_ph == "1") {
      if ($specific_bt_chk_ph == "1") {
        // bd_addrs array contains only specific BD_ADDR addresses
        foreach (explode(",", $specific_bt_ph) as $specific_key => $specific_value) {
          $bd_addrs[] = $specific_value;
        }
      }
    }

  } else {

    // process all keys

    if ($show_wlan_ph == "1") {
      // GLOBAL MAC
      // every unique global MAC in time range 
      get_macs($db_conn_s, $time_from_ph, $time_to_ph, $db_q_standard, $macs);
      // LOCAL MAC
      // every local MAC fingerprint
      get_fingerprints("all", $db_conn_s, $time_from_ph, $time_to_ph, $db_q_standard, $fingerprints);
    }
    if ($show_bt_ph == "1") {
      // every unique BD_ADDR in time range
      get_bd_addrs($db_conn_s, $time_from_ph, $time_to_ph, $bd_addrs);
    }
  }
  
  $mac_glbl_passed = count($macs);
  $mac_local_passed = count($fingerprints);
  $bt_passed = count($bd_addrs);
  $passed_total = $mac_glbl_passed + $mac_local_passed + $bt_passed;

  // find passages
  if ($passed_total > 0) {
    $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $db_source_ph);
    if ($show_wlan_ph == "1") {
      if ($mac_glbl_passed > 0) {
        $Passenger_macs = process_keys("wifi_global", $db_q_standard, $macs,
                                       $blacklist_wlan_ph, $db_conn_s, $threshold_seconds,
                                       $timestamp_limit_ph, $time_from_ph, $time_to_ph,
                                       $time_increment, $chart_wifi_unique_ph, $chart_wifi_total_ph,
                                       $mac_glbl_over_timestamp_limit, $mac_glbl_blacklisted);
      }
        if ($mac_local_passed > 0) {
        $Passenger_fingerprints = process_keys("wifi_local", $db_q_standard, $fingerprints,
                                               $blacklist_fp_ph, $db_conn_s, $threshold_seconds,
                                               $timestamp_limit_ph, $time_from_ph, $time_to_ph,
                                               $time_increment, $chart_wifi_unique_ph, $chart_wifi_total_ph,
                                               $mac_local_over_timestamp_limit, $mac_local_blacklisted);
      }
    }
    if ($show_bt_ph == "1") {
      if ($bt_passed > 0) {
        $Passenger_bd_addrs = process_keys("bt", "1", $bd_addrs,
                                           $blacklist_bt_ph, $db_conn_s, $threshold_seconds,
                                           $timestamp_limit_ph, $time_from_ph, $time_to_ph,
                                           $time_increment, $chart_bt_unique_ph, $chart_bt_total_ph,
                                           $bt_over_timestamp_limit, $bt_blacklisted);
      }
    }
  }

  fill_chart_arrays($time_from_ph, $time_to_ph, $time_increment, $Passenger_macs, $chart_wifi_total_ph, $chart_wifi_unique_ph);
  fill_chart_arrays($time_from_ph, $time_to_ph, $time_increment, $Passenger_fingerprints, $chart_wifi_total_ph, $chart_wifi_unique_ph);
  fill_chart_arrays($time_from_ph, $time_to_ph, $time_increment, $Passenger_bd_addrs, $chart_bt_total_ph, $chart_bt_unique_ph);

  // actual text output starts here
  
  echo  "Showing results from " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_from_ph)) . "</b>" .
        " to " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_to_ph)) . "</b>" .
        " with step of " . "<b>" . $time_step_ph . " " . strtolower($time_step_format_ph) . "(s)" . "</b>" .
        "<br><br>";

  print_statistics_table($show_wlan_ph, $show_bt_ph, $passed_total,
                         $mac_glbl_passed, $mac_glbl_over_timestamp_limit, $mac_glbl_blacklisted,
                         $mac_local_passed, $mac_local_over_timestamp_limit, $mac_local_blacklisted,
                         $bt_passed, $bt_over_timestamp_limit, $bt_blacklisted);

  if ($show_wlan_ph == "1") {
    if ($mac_glbl_passed > 0) {
      print_Passenger_array("wifi_global", $Passenger_macs, $time_from_ph, $time_to_ph, $time_increment);
    }
    if ($mac_local_passed > 0) {
      print_Passenger_array("wifi_local", $Passenger_fingerprints, $time_from_ph, $time_to_ph, $time_increment);
    }
  }
  if ($show_bt_ph == "1") {
    if ($bt_passed > 0) {
      print_Passenger_array("bt", $Passenger_bd_addrs, $time_from_ph, $time_to_ph, $time_increment);
    }
  }

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
  $mem_peak = memory_get_peak_usage(true);
  $mem_peak = $mem_peak/1024/1024; // MB
  echo "Algorithm finished in " . ($alg_end - $alg_start) . " seconds.<br>";
  echo "Memory usage peak: " . round($mem_peak, 2) . " MB";
}
?>
