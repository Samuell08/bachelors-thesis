<?php
// TEXTOUT_HISTORY.php
// PHP code to be called from mov_history.php

session_start();

$session_id = session_id();

// infinite execution time
set_time_limit(0);

// get session variables
// database connection
$db_server      = $_SESSION["db_server"];
$db_user        = $_SESSION["db_user"];
$db_pass        = $_SESSION["db_pass"];
$db_source_A_mh = $_SESSION["db_source_A_mh"];
$db_source_B_mh = $_SESSION["db_source_B_mh"];
// settings
$time_from_mh           = $_SESSION["time_from_mh"];
$time_to_mh             = $_SESSION["time_to_mh"];
$time_step_mh           = $_SESSION["time_step_mh"];
$time_step_format_mh    = $_SESSION["time_step_format_mh"];
$threshold_mh           = $_SESSION["threshold_mh"];
$threshold_format_mh    = $_SESSION["threshold_format_mh"];
$timestamp_limit_chk_mh = $_SESSION["timestamp_limit_chk_mh"];
$timestamp_limit_mh     = $_SESSION["timestamp_limit_mh"];
$show_wlan_mh           = $_SESSION["show_wlan_mh"];
$show_bt_mh             = $_SESSION["show_bt_mh"];
$show_wlan_a_mh         = $_SESSION["show_wlan_a_mh"];
$show_wlan_bg_mh        = $_SESSION["show_wlan_bg_mh"];
$blacklist_wlan_mh      = $_SESSION["blacklist_wlan_mh"];
$blacklist_fp_mh        = $_SESSION["blacklist_fp_mh"];
$blacklist_bt_mh        = $_SESSION["blacklist_bt_mh"];
$specific_mac_chk_mh    = $_SESSION["specific_mac_chk_mh"];
$specific_mac_mh        = $_SESSION["specific_mac_mh"];
$specific_fp_chk_mh     = $_SESSION["specific_fp_chk_mh"];
$specific_bt_chk_mh     = $_SESSION["specific_bt_chk_mh"];
$specific_bt_mh         = $_SESSION["specific_bt_mh"];

$_SESSION["debug_main"] = false;
$_SESSION["debug_process_timestamps_output"] = false;
$_SESSION["debug_chart_arrays"] = false;

function is_anagram($string1, $string2) {
  if (count_chars($string1, 1) == count_chars($string2, 1))
    return 1;
  else
    return 0;
}

// return array of values that are in both arrays
function in_both($type, $A, $B){
  foreach ($A as $A_p => $A_v){
    foreach ($B as $B_p => $B_v){
      switch($type) {
        case "wifi_global": //fallthrough
        case "bluetooth":
          if ($A_v == $B_v){
            $result[] = $A_v;
          }
          break;
        case "wifi_local":
          if (is_anagram($A_v[0], $B_v[0])) {
            if (count($A_v) > count($B_v)) {
              $result[] = $A_v;
            } else {
              $result[] = $B_v;
            }
          }
          break;
        default:
          die("function in_both ERROR: Unknown type: " . $type);
      }
    }
  }
  return $result;
}

function prepare_chart_array($time_from, $time_to, $time_increment) {
  // prepare chart arrays
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
function get_fingerprints($mode, $db_conn, $time_from, $time_to, $db_q_standard, &$fingerprints) {

  if ($mode == "specific") {
    $specific_mode_fp_mh   = $_SESSION["specific_mode_fp_mh"];
    $specific_fp_mh        = $_SESSION["specific_fp_mh"];
  }

  $db_q = "SELECT SUBSTRING(probed_ESSIDs,19,1000) FROM Clients WHERE
          (LENGTH(probed_ESSIDs) > 18) AND
          (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "') AND NOT
          (station_MAC LIKE '_0:__:__:__:__:__' OR
           station_MAC LIKE '_4:__:__:__:__:__' OR
           station_MAC LIKE '_8:__:__:__:__:__' OR
           station_MAC LIKE '_C:__:__:__:__:__') AND " . $db_q_standard .
           "GROUP BY SUBSTRING(probed_ESSIDs,19,1000);";
  $db_result = mysqli_query($db_conn, $db_q);
  // append result to fingerprints
  if (mysqli_num_rows($db_result) > 0) {
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      $probed_essids = $db_row["SUBSTRING(probed_ESSIDs,19,1000)"];
      switch ($mode) {
      
        case "all":
          $fingerprints[][0] = $probed_essids;
          break;

        case "specific": 
          switch ($specific_mode_fp_mh) {
            case "EXACT":
              // fingerprint must be an anagram of specific ESSID list
              if (is_anagram($probed_essids, $specific_fp_mh)){
                $fingerprints[][0] = $probed_essids;
	            }
              break;
	          case "ATLEAST":
              // fingerprint must contain all specified ESSIDs or more
              $essids = explode(",", $probed_essids);
              $specific_exploded = explode(",", $specific_fp_mh);
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
              die("function get_fingerprints ERROR: Unknown specific ESSID mode: " . $specific_mode_fp_mh);
          }
          break;
        default:
          die("function get_fingerprints ERROR: Unknown mode: " . $mode);
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

function get_timestamps($db_conn, $db_q){
  unset($ts);
  if (!$db_conn){
    die("function get_timestamps ERROR: Database connection failed");
  } else {
    if (is_scalar($db_q)) {
      $db_result = mysqli_query($db_conn, $db_q);
      while ($db_row = mysqli_fetch_assoc($db_result)){
        $ts[] = $db_row["last_time_seen"];
      }
    } else {
      // fingerprint has one or more anagrams
      foreach ($db_q as $db_q_p => $db_q_v) {
        $db_result = mysqli_query($db_conn, $db_q_v);
        while ($db_row = mysqli_fetch_assoc($db_result)){
          $ts[] = $db_row["last_time_seen"];
        }
      }
    }
  }
  return $ts;
}

// function accepts two arrays of timestamps and finds minimum time
// of movement from first to second in seconds
function timestamps_find_minimum($tsA, $tsB){
  $min_AB = PHP_INT_MAX;
  foreach ($tsA as $tsA_p => $tsA_v){
    foreach ($tsB as $tsB_p => $tsB_v){
      if (strtotime($tsB_v) > strtotime($tsA_v)){
        $diff_AB[] = (strtotime($tsB_v)-strtotime($tsA_v));
      }
    }
    if ($diff_AB != NULL){
      $min_AB = min($diff_AB);
    }
  }
  return $min_AB;
}

// function accepts two arrays of timestamps and unsets timestamps
// that are too close together according to total minimum passage time
//
// first array is considered as time of departure therefore the highest times are kept
// second array is considered as time of arrival therefore the lowest times are kept
function timestamps_unset_irrelevant(&$tsA, &$tsB, $total_min){
  unset($unset);
  // A
  for ($i = 1; $i < count($tsA); $i++){
    if (strtotime($tsA[$i])-strtotime($tsA[$i-1]) < ($total_min)){
      $unset[] = $i-1;
    }
  }
  if($unset != NULL){
    foreach ($unset as $unset_p => $unset_v){
      unset($tsA[$unset_v]);
    }
  }
  unset($unset);
  // B
  for ($i = 1; $i < count($tsB); $i++){
    if (strtotime($tsB[$i])-strtotime($tsB[$i-1]) < ($total_min)){
      $unset[] = $i;
    }
  }
  if($unset != NULL){
    foreach ($unset as $unset_p => $unset_v){
      unset($tsB[$unset_v]);
    }
  }
}

// functions accepts two arrays of timestamps and threshold and returns
// 2D array of movement as first | second | diff
// values are added to array only when time difference is lower that threshold
// timestamps need to be sanitized before calling this function - as seen in
// function process_timestamps
function timestamps_find_movement($tsA, $tsB, $threshold, &$movement){
  foreach ($tsA as $tsA_i => $tsA_v){
    foreach ($tsB as $tsB_i => $tsB_v){
      if (strtotime($tsB_v) > strtotime($tsA_v)){
        $diff = (strtotime($tsB_v) - strtotime($tsA_v));
        if ($diff <= $threshold){
          $movement[] = array($tsA_v, $tsB_v, $diff);
        }
        break;
      }
    }
  }        
}

// function accepts two raw arrays of timestamps from database query
// and threshold and returns 2D array of movement as first | second | diff
function process_timestamps($tsA, $tsB, $threshold, &$AB_movement,  &$BA_movement){
    // local copies of fingerprints that will be modified
    $AB_tsA = $tsA;
    $AB_tsB = $tsB;
    $BA_tsA = $tsA;
    $BA_tsB = $tsB;
    $AB_min = timestamps_find_minimum($tsA, $tsB);
    $BA_min = timestamps_find_minimum($tsB, $tsA);
    $total_min = min($AB_min, $BA_min);

    if($_SESSION["debug_process_timestamps_output"]) {
      echo "<br>AB_min: ".$AB_min."<br>";
      echo "<br>BA_min: ".$BA_min."<br>";
      echo "<br>total_min: ".$total_min."<br>";
    }

    timestamps_unset_irrelevant($AB_tsA, $AB_tsB, $total_min);
    timestamps_unset_irrelevant($BA_tsB, $BA_tsA, $total_min);
    timestamps_find_movement($AB_tsA, $AB_tsB, $threshold, $AB_movement);
    timestamps_find_movement($BA_tsB, $BA_tsA, $threshold, $BA_movement);
}

// function accepts list of keys (eg. MAC addresses) and compares
// it to blacklist (echoing 'Blacklisted' instead of timestamps)
// returns:  0 - key not blacklisted
//           1 - key blacklisted
//          -1 - unknown type
function blacklisted($type, $key, $blacklist) {
  
  $blacklist_wlan_chk_mh  = $_SESSION["blacklist_wlan_chk_mh"];
  $blacklist_fp_chk_mh    = $_SESSION["blacklist_fp_chk_mh"];
  $blacklist_mode_fp_mh   = $_SESSION["blacklist_mode_fp_mh"];
  $blacklist_bt_chk_mh    = $_SESSION["blacklist_bt_chk_mh"];

  $blacklist_exploded = explode(",", $blacklist);
  switch ($type) {
    case "wifi_global":
      if ($blacklist_wlan_chk_mh == "1") {
        foreach ($blacklist_exploded as $blacklist_key => $blacklist_value) {
          if ($key == $blacklist_value) {
            // found key in blacklist
            return 1;
          }
        }
      }
      break;
    
    case "wifi_local":
      if ($blacklist_fp_chk_mh == "1") {
        $essids = explode(",", $key);
        $essids_blacklisted = 0;
        foreach ($blacklist_exploded as $blacklist_key => $blacklist_value) {
          foreach ($essids as $essid_key => $essid_value) {
            if ($essid_value == $blacklist_value) {
              $essids_blacklisted++;
            }
          }
        }
        switch ($blacklist_mode_fp_mh) {
          case "ALL":
            // fingerprint is made of blacklisted essids only
            if ($essids_blacklisted == count($essids)) { return 1; }
            break;
          case "ONE":
            // fingerprint contains at least one blacklisted essid
            if ($essids_blacklisted > 0) { return 1; }
            break;
          default:
            die("function blacklisted ERROR: Unknown local MAC mode: " . $blacklist_mode_fp_mh);
        }
      }
      break;

    case "bt":
      if ($blacklist_bt_chk_mh == "1") {
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

class Movement {
  // properties
  public $key = NULL; // MAC, fingerprint or BD_ADDR
  public $blacklisted = 0; // if 1, key is blacklisted
  public $AB; // 2D array A | B | diff
  public $BA; // 2D array B | A | diff
}

// function accepts list of MAC/BD_ADDR addresses or 2D array of
// probed ESSIDs fingerprints and returns array of Movement classes
function process_keys($type, $db_q_standard, $keys,
                      $blacklist, $threshold, $db_conn_A, $db_conn_B,
                      $timestamp_limit, $time_from, $time_to,
                      $time_increment, &$ignored, &$moved_total_AB, &$moved_total_BA,
                      &$blacklisted) {
  
  foreach ($keys as $keys_key => $keys_value) {
    
    // new movement object for every key
    $Movement_key = new Movement();
    
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
        // fill Movement object and push it to output array
        $Movement_key->key = $keys_value;
        $Movement_key->blacklisted = 1;
        $Movement_array[] = $Movement_key;
        $blacklisted++;
        continue 2; // foreach keys
        break;
      default:
        exit("function process_keys ERROR: error while processing blacklist");
    }

    // customize algorithm to specific keys type
    switch ($type) {
      case "wifi_global":
        $db_q = "SELECT last_time_seen FROM Clients WHERE
                (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                 AND " . $db_q_standard . " AND (station_MAC = '" . $keys_value . "');";
        break;
      case "wifi_local":
        unset($db_q);
        foreach ($keys_value as $anagram_p => $anagram_v) {
          $db_q[] = "SELECT last_time_seen FROM Clients WHERE
                    (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                     AND " . $db_q_standard . " AND (SUBSTRING(probed_ESSIDs,19,1000) = '" . $anagram_v . "');";
        }
        break;
      case "bt":
        $db_q = "SELECT last_time_seen FROM Bluetooth WHERE
                (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                 AND (BD_ADDR = '" . $keys_value . "');";
        break;
      default:
        die("function process_keys ERROR: Unknown type: " . $type);
    }

    if($_SESSION["debug_process_timestamps_output"]) {
      echo "<br> key:<br>";
      if(is_scalar($keys_value)){
        echo $keys_value . "<br>";
      } else {
        echo $keys_value[0] . "<br>";
      }
    }
    
    // get timestamps for key from database A and B
    $timestampsA = get_timestamps($db_conn_A, $db_q);
    $timestampsB = get_timestamps($db_conn_B, $db_q);

    unset($AB_movement);
    unset($BA_movement);

    process_timestamps($timestampsA, $timestampsB, $threshold, $AB_movement, $BA_movement);

    if($_SESSION["debug_process_timestamps_output"]) {
      echo "<br> timestampsA:<br>";
      var_dump($timestampsA);
      echo "<br> timestampsB:<br>";
      var_dump($timestampsB);
      echo "<br> AB_movement:<br>";
      var_dump($AB_movement);
      echo "<br> BA_movement:<br>";
      var_dump($BA_movement);
      echo "<br><br>";
    }

    $moved_total_AB += count($AB_movement);
    $moved_total_BA += count($BA_movement);

    // fill Movement object and push it to output array
    $Movement_key->key = $keys_value;
    $Movement_key->AB = $AB_movement;
    $Movement_key->BA = $BA_movement;
    $Movement_array[] = $Movement_key;

  } // foreach key end

  return $Movement_array;
    
}

// function accepts array of Movement objects and prints it to html based on
// direction and type parameters
function print_Movement_array($direction, $type, $Movement_array) {

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
      die("function print_Movement_array ERROR: Unknown type: " . $type);
  }

  echo "<table style=\"border-collapse:collapse\">";
  foreach ($Movement_array as $Movement_key) {

    switch($type){
      case "wifi_global":
      case "bt":
        $key = $Movement_key->key;
        break;
      case "wifi_local":
        $key = $Movement_key->key[0];
        break;
      default:
        die("function print_Movement_array ERROR: Unknown type: " . $type);
    }

    echo "<tr class=\"info\">";
    echo "<td><tt>" . $key . "&nbsp&nbsp&nbsp&nbsp&nbsp</tt></td>";
    echo "<td>";
      echo "<table>";
      if ($Movement_key->blacklisted == 1) {

        echo "<tr style=\"color:orangered\"><td><tt>" . 
               "Blacklisted" . 
             "</tt></td></tr>";
      
      } else {

        switch($direction){
          case "AB":
            if ($Movement_key->AB == NULL) {
              echo   "<tr style=\"color:orangered\"><td><tt>" . 
                     "None" . 
                   "</tt></td></tr>";
            } else {
              foreach ($Movement_key->AB as $AB_array_p => $AB_array_v) {
                echo "<tr>";
                echo "<td><tt>" . 
                       $AB_array_v[0] . "<b> => </b>" . 
                       $AB_array_v[1] . 
                       "<b>(" . 
                         round($AB_array_v[2], 2)  . " sec / " . 
                         round($AB_array_v[2]/60, 2) . " min / " . 
                         round($AB_array_v[2]/3600, 2) . " hod" .
                       ")</b>" .
                     "</tt></td>";
                echo "</tr>";
              }
            }
            break;
          case "BA":
            if ($Movement_key->BA == NULL) {
              echo "<tr style=\"color:orangered\"><td><tt>" . 
                     "None" . 
                   "</tt></td></tr>";
            } else {
              foreach ($Movement_key->BA as $BA_array_p => $BA_array_v) {
                echo "<tr>";
                echo "<td><tt>" . 
                       $BA_array_v[0] . "<b> => </b>" . 
                       $BA_array_v[1] . 
                       "<b>(" . 
                         round($BA_array_v[2], 2)  . " sec / " . 
                         round($BA_array_v[2]/60, 2) . " min / " . 
                         round($BA_array_v[2]/3600, 2) . " hod" .
                       ")</b>" .
                     "</tt></td>";
                echo "</tr>";
              }
            }
            break;
          default:
            die("function print_Movement_array ERROR: Unknown direction: " . $direction);
        } // switch direction end
      } // if blacklisted end
      echo "</table>";
    echo "<td>";
    echo "</tr>";
  }
  echo "</table>";
  echo "<br>";
}

function print_statistics_table($show_wlan, $show_bt,
                                $moved_total_AB, $moved_total_BA,
                                $mac_glbl_moved, $mac_glbl_ignored, $mac_glbl_blacklisted,
                                $mac_local_moved, $mac_local_ignored, $mac_local_blacklisted,
                                $bt_moved, $bt_ignored, $bt_blacklisted) {
  if ($show_wlan == "1") {
    echo "<b>Wi-Fi</b><br>";
    echo "<table class=\"textout\">";
    echo "<tr class=\"textout\"><td>" . "Devices with global MAC address:" .
                                        "</td><td>" .
                                        $mac_glbl_moved . " - " .
                                        $mac_glbl_ignored . " - " .
                                        $mac_glbl_blacklisted . " = " .
                                        "<b>" . ($mac_glbl_moved-$mac_glbl_ignored-$mac_glbl_blacklisted) .
                                        "</b></td></tr>";
    echo "<tr class=\"textout\"><td>" . "Devices with local MAC address:" .
                                        "</td><td>" . 
                                        $mac_local_moved . " - " .
                                        $mac_local_ignored . " - " .
                                        $mac_local_blacklisted . " = " .
                                        "<b>" . ($mac_local_moved-$mac_local_ignored-$mac_local_blacklisted) .
                                        "</b></td></tr>";
    echo "</table>";
  }
  if ($show_bt == "1") {
    echo "<b>Bluetooth</b><br>";
    echo "<table class=\"textout\">";
    echo "<tr class=\"textout\"><td>" . "Devices:" .
                                        "</td><td>" .
                                        $bt_moved . " - " .
                                        $bt_ignored . " - " .
                                        $bt_blacklisted . " = " .
                                        "<b>" . ($bt_moved-$bt_ignored-$bt_blacklisted) .
                                        "</b></td></tr>";
    echo "</table>";
  }
  
  echo "<br><b>Legend:</b> <i>moved - over limit - blacklisted = <b>processed</b></i>" . "<br><br>";

  $moved_total = $moved_total_AB + $moved_total_BA;
  echo "<b>Total number of processed movements A->B: </b>" . $moved_total_AB . "<br>";
  echo "<b>Total number of processed movements B->A: </b>" . $moved_total_BA . "<br>";
  echo "<b>Total number of processed movements combined: </b>" . $moved_total . "<br><br>";
}

function fill_chart_arrays($format, $accumulator_AB, $accumulator_BA, &$chart_AB, &$chart_BA){
  
  $chart_AB_size = count($chart_AB);
  $chart_BA_size = count($chart_BA);
  if ($chart_AB_size != $chart_BA_size){
    die("function fill_chart_arrays ERROR: AB and BA chart array sizes do not match");
  } else {
    $chart_array_size = $chart_AB_size;
  }
  
  switch($format) {
    case "s": $divisor = 1; break;
    case "m": $divisor = 60; break;
    case "h": $divisor = 3600; break;
    default:
      die("function fill_chart_arrays ERROR: Unknown format: ". $format);
  }

  
  for ($i = 0; $i < $chart_array_size; $i++){
    
    if (is_null($accumulator_AB[$i])) {
      $chart_AB[$i]["y"] = null;
    } else {
      $chart_AB[$i]["y"] = (array_sum($accumulator_AB[$i])/count($accumulator_AB[$i]))/$divisor;
    }
    
    if (is_null($accumulator_BA[$i])) {
      $chart_BA[$i]["y"] = null;
    } else {
      $chart_BA[$i]["y"] = (array_sum($accumulator_BA[$i])/count($accumulator_BA[$i]))/$divisor;
    }

  }

  if ($_SESSION["debug_chart_arrays"]) {
    echo "<hr><hr>";
    echo "function fill_chart_arrays done:<br>";
    echo "<br>chart AB:<br>";
    var_dump($chart_AB);
    echo "<br><br>";
    echo "chart BA:<br>";
    var_dump($chart_BA);
    echo "<br>";
  }
}

function accumulate_chart_arrays($time_from, $time_to, $time_increment, 
                                 $Movement_array, &$accumulator_AB, &$accumulator_BA) {

  if ($_SESSION["debug_chart_arrays"]) {
    echo "<hr>";
    echo "function accumulate_chart_arrays start:<br>";
  }

  foreach ($Movement_array as $Movement_key) {
    // reset counters
    $i = 0;
    $time_actual = $time_from;
    while (strtotime($time_actual) <= (strtotime($time_to) - $time_increment)) {
      // calculate next time value
      $time_next = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
      // AB movement in current time step?
      foreach ($Movement_key->AB as $AB_p => $AB_v){
        if ((strtotime($AB_v[1]) > strtotime($time_actual)) && (strtotime($AB_v[1]) <= strtotime($time_next))){
          if ($_SESSION["debug_chart_arrays"]) {
            if (is_scalar($Movement_key->key)) {
              echo "AB - Key: " . $Movement_key->key . " ... Appending " . $AB_v[2] . " to timestamp " . $time_next . "<br>";
            } else {
              echo "AB - Key: " . $Movement_key->key[0] . " ... Appending " . $AB_v[2] . " to timestamp " . $time_next . "<br>";
            }
          }
          $accumulator_AB[$i][] = $AB_v[2]; // diff
        }
      }
      // BA movement in current time step?
      foreach ($Movement_key->BA as $BA_p => $BA_v){
        if ((strtotime($BA_v[1]) > strtotime($time_actual)) && (strtotime($BA_v[1]) <= strtotime($time_next))){
          if ($_SESSION["debug_chart_arrays"]) {
            if (is_scalar($Movement_key->key)) {
              echo "BA - Key: " . $Movement_key->key . " ... Appending " . $BA_v[2] . " to timestamp " . $time_next . "<br>";
            } else {
              echo "BA - Key: " . $Movement_key->key[0] . " ... Appending " . $BA_v[2] . " to timestamp " . $time_next . "<br>";
            }
          }
          $accumulator_BA[$i][] = $BA_v[2]; // diff
        }
      }
      // moving to next time step
      // increment counters
      $i += 1;
      $time_actual = $time_next;
    }
  }

  if ($_SESSION["debug_chart_arrays"]) {
    echo "<br>function accumulate_chart_arrays done:<br>";
    echo "acc AB:<br>";
    var_dump($accumulator_AB);
    echo "<br><br>";
    echo "acc BA:<br>";
    var_dump($accumulator_BA);
  }
}

// check if user input is correct
if ($db_source_A_mh == NULL or $db_source_B_mh == NULL) {
  echo "<p class=\"warning\">Source database for point A and/or B not selected.</p>";
} elseif ($db_source_A_mh == $db_source_B_mh) {
  echo "<p class=\"warning\">Source database for point B cannot be the same as for point A.</p>";
} elseif ((!($show_wlan_mh == "1")) and (!($show_bt_mh == "1"))) {
  echo "<p class=\"warning\">No data selected to show.</p>";
} elseif (strtotime($time_from_mh) > strtotime($time_to_mh)) {
  echo "<p class=\"warning\">Time range \"From\" is later in time than \"To\".</p>";
} elseif (strtotime($time_to_mh) > time()) {
  echo "<p class=\"warning\">Time range \"To\" is in the future.</p>";
} elseif ($threshold_mh == NULL) {
  echo "<p class=\"warning\">Invalid threshold.</p>";
} elseif ($threshold_format_mh == NULL) {
  echo "<p class=\"warning\">Threshold format Minute(s)/Hour(s) not selected.</p>";
} elseif ($show_wlan_mh == "1" and $show_wlan_a_mh != "1" and $show_wlan_bg_mh != "1") {
  echo "<p class=\"warning\">Wi-Fi Standard not selected.</p>";
} else {

  // algorithm execution start
  $alg_start = time();

  // calculate time increment
  switch ($time_step_format_mh) {
  case "SECOND":
    $time_increment = $time_step_mh;
    break;
  case "MINUTE":
    $time_increment = $time_step_mh*60;
    break;
  case "HOUR":
    $time_increment = $time_step_mh*3600;
    break;
  }

  // calculate threshold seconds
  switch ($threshold_format_mh) {
  case "SECOND":
    $threshold_seconds = $threshold_mh;
    break;
  case "MINUTE":
    $threshold_seconds = $threshold_mh*60;
    break;
  case "HOUR":
    $threshold_seconds = $threshold_mh*3600;
    break;
  }

  // "disable" limit
  if ($timestamp_limit_chk_mh != "1"){
    $timestamp_limit_mh = PHP_INT_MAX;
  }

  // wifi standard
  if ($show_wlan_mh == "1") {
    if ($show_wlan_a_mh == "1" and $show_wlan_bg_mh == "1") {
      $db_q_standard = "(standard = 'a' OR standard = 'bg')";
    } else {
      if ($show_wlan_a_mh == "1") {
        $db_q_standard = "(standard = 'a')";
      }
      if ($show_wlan_bg_mh == "1") {
        $db_q_standard = "(standard = 'bg')";
      }
    }
  }

  // prepare variables
  unset($A_macs);
  unset($A_fingerprints);
  unset($A_bd_addrs);
  unset($B_macs);
  unset($B_fingerprints);
  unset($B_bd_addrs);
  $moved_total_AB = 0;
  $moved_total_BA = 0;
  $mac_glbl_blacklisted = 0;
  $mac_local_blacklisted = 0;
  $bt_blacklisted = 0;
  
  // prepare chart arrays
  $chart_AB_mh = prepare_chart_array($time_from_mh, $time_to_mh, $time_increment);
  $chart_BA_mh = prepare_chart_array($time_from_mh, $time_to_mh, $time_increment);

  // prepare database connections
  $db_conn_A = mysqli_connect($db_server, $db_user, $db_pass, $db_source_A_mh);
  $db_conn_B = mysqli_connect($db_server, $db_user, $db_pass, $db_source_B_mh);

  // fill key arrays
  // point A
  if ($show_wlan_mh == "1") {
    // GLOBAL MAC
    // every unique global MAC in time range 
    get_macs($db_conn_A, $time_from_mh, $time_to_mh, $db_q_standard, $A_macs);
    // LOCAL MAC
    // every local MAC fingerprint in time range
    get_fingerprints("all", $db_conn_A, $time_from_mh, $time_to_mh, $db_q_standard, $A_fingerprints);
  }
  if ($show_bt_mh == "1") {
    // every unique BD_ADDR in time range
    get_bd_addrs($db_conn_A, $time_from_mh, $time_to_mh, $A_bd_addrs);
  }

  // point B
  if ($show_wlan_mh == "1") {
    // GLOBAL MAC
    // every unique global MAC in time range 
    get_macs($db_conn_B, $time_from_mh, $time_to_mh, $db_q_standard, $B_macs);
    // LOCAL MAC
    // every local MAC fingerprint in time range
    get_fingerprints("all", $db_conn_B, $time_from_mh, $time_to_mh, $db_q_standard, $B_fingerprints);
  }
  if ($show_bt_mh == "1") {
    // every unique BD_ADDR in time range
    get_bd_addrs($db_conn_B, $time_from_mh, $time_to_mh, $B_bd_addrs);
  }

  // keep only keys that are in both databases
  $macs         = in_both("wifi_global", $A_macs, $B_macs);
  $fingerprints = in_both("wifi_local", $A_fingerprints, $B_fingerprints);
  $bd_addrs     = in_both("bluetooth", $A_bd_addrs, $B_bd_addrs);

  // find movement for each key
  $Movement_macs = process_keys("wifi_global", $db_q_standard, $macs,
                                $blacklist_wlan_mh, $threshold_seconds, $db_conn_A, $db_conn_B,
                                $timestamp_limit_mh, $time_from_mh, $time_to_mh,
                                $time_increment, $ignored, $moved_total_AB, $moved_total_BA,
                                $mac_glbl_blacklisted);

  $Movement_fingerprints = process_keys("wifi_local", $db_q_standard, $fingerprints,
                                        $blacklist_fp_mh, $threshold_seconds, $db_conn_A, $db_conn_B,
                                        $timestamp_limit_mh, $time_from_mh, $time_to_mh,
                                        $time_increment, $ignored, $moved_total_AB, $moved_total_BA,
                                        $mac_local_blacklisted);

  $Movement_bd_addrs = process_keys("bt", $db_q_standard, $bd_addrs,
                                    $blacklist_bt_mh, $threshold_seconds, $db_conn_A, $db_conn_B,
                                    $timestamp_limit_mh, $time_from_mh, $time_to_mh,
                                    $time_increment, $ignored, $moved_total_AB, $moved_total_BA,
                                    $bt_blacklisted);

  $mac_glbl_moved = count($Movement_macs);
  $mac_local_moved = count($Movement_fingerprints);
  $bt_moved = count($Movement_bd_addrs);
  
  // fill chart arrays
  unset($accumulator_AB);
  unset($accumulator_BA);
  accumulate_chart_arrays($time_from_mh, $time_to_mh, $time_increment, $Movement_macs, $accumulator_AB, $accumulator_BA);
  accumulate_chart_arrays($time_from_mh, $time_to_mh, $time_increment, $Movement_fingerprints, $accumulator_AB, $accumulator_BA);
  accumulate_chart_arrays($time_from_mh, $time_to_mh, $time_increment, $Movement_bd_addrs, $accumulator_AB, $accumulator_BA);
  fill_chart_arrays("m", $accumulator_AB, $accumulator_BA, $chart_AB_mh, $chart_BA_mh);

  // actual text output starts here

  echo  "Showing results from " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_from_mh)) . "</b>" .
        " to " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_to_mh)) . "</b>" .
        " with step of " . "<b>" . $time_step_mh . " " . strtolower($time_step_format_mh) . "(s)" . "</b>" .
        "<br><br>";

  echo "<b>Point A:</b> " . $db_source_A_mh . "<br>";
  echo "<b>Point B:</b> " . $db_source_B_mh . "<br><br>";

  // statistics table
  print_statistics_table($show_wlan_mh, $show_bt_mh,
                         $moved_total_AB, $moved_total_BA,
                         $mac_glbl_moved, 0, $mac_glbl_blacklisted,
                         $mac_local_moved, 0, $mac_local_blacklisted,
                         $bt_moved, 0, $bt_blacklisted);

  echo "<b>Movement A->B:</b><br>";
  if ($show_wlan_mh == "1") {
    print_Movement_array("AB", "wifi_global", $Movement_macs);
    print_Movement_array("AB", "wifi_local", $Movement_fingerprints);
  }
  if ($show_bt_mh == "1") {
    print_Movement_array("AB", "bt", $Movement_bd_addrs);
  }

  echo "<b>Movement B->A:</b><br>";
  if ($show_wlan_mh == "1") {
    print_Movement_array("BA", "wifi_global", $Movement_macs);
    print_Movement_array("BA", "wifi_local", $Movement_fingerprints);
  }
  if ($show_bt_mh == "1") {
    print_Movement_array("BA", "bt", $Movement_bd_addrs);
  }

  // --------------------------------------------------------------------------- debug output

  if ($_SESSION["debug_main"]) {
    echo "point A<br>";
    echo "<br>macs A:<br>";
    var_dump($A_macs);
    echo "<br><br>fingerprints A:<br>";
    var_dump($A_fingerprints);
    echo "<br><br>bd_addrs A:<br>";
    var_dump($A_bd_addrs);

    echo "<br><br>point B<br>";
    echo "<br>macs B:<br>";
    var_dump($B_macs);
    echo "<br><br>fingerprints B:<br>";
    var_dump($B_fingerprints);
    echo "<br><br>bd_addrs B:<br>";
    var_dump($B_bd_addrs);

    echo "<br><br><br>in both:<br>";
    echo "<br>macs:<br>";
    var_dump($macs);
    echo "<br><br>fingerprints:<br>";
    var_dump($fingerprints);
    echo "<br><br>bd_addrs:<br>";
    var_dump($bd_addrs);
    echo "<br><br>";

    echo "<br>Movement_macs:<br><br>";
    var_dump($Movement_macs);
    echo "<br><br>";

    echo "<br>Movement_bd_addrs:<br><br>";
    var_dump($Movement_bd_addrs);
    echo "<br><br>";
  }
  
  // --------------------------------------------------------------------------- debug end

  if (!(json_encode($chart_AB_mh))) {
    die ("JSON encoding of AB chart array ERROR: " . json_last_error_msg());
  }
  if (!(json_encode($chart_BA_mh))) {
    die ("JSON encoding of BA chart array ERROR: " . json_last_error_msg());
  }

  // write completed chart arrays to json files
  $json_dir = "../../json";
  if (!file_exists($json_dir)){ mkdir($json_dir); }
  $f_AB_mh = fopen($json_dir . "/chart_AB_mh_" . $session_id, "w");
  $f_BA_mh = fopen($json_dir . "/chart_BA_mh_" . $session_id, "w");
  fwrite($f_AB_mh, json_encode($chart_AB_mh));
  fwrite($f_BA_mh, json_encode($chart_BA_mh));
  fclose($f_AB_mh);
  fclose($f_BA_mh);

  // algorithm execution end
  $alg_end = time();
  $mem_peak = memory_get_peak_usage();
  $mem_peak = $mem_peak / 1000000; // MB
  echo "Algorithm finished in " . ($alg_end - $alg_start) . " seconds.<br>";
  echo "Memory usage peak: " . round($mem_peak, 2) . " MB";
}
?>
