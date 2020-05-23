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

function is_anagram($string1, $string2) {
  if (count_chars($string1, 1) == count_chars($string2, 1))
    return 1;
  else
    return 0;
}

function get_macs($db_conn_s, $time_from_mh, $time_to_mh, $db_q_standard, &$macs) {
  $db_q = "SELECT station_MAC FROM Clients WHERE
          (last_time_seen BETWEEN '" . $time_from_mh . "' AND '" . $time_to_mh . "') AND
          (station_MAC LIKE '_0:__:__:__:__:__' OR
           station_MAC LIKE '_4:__:__:__:__:__' OR
           station_MAC LIKE '_8:__:__:__:__:__' OR
           station_MAC LIKE '_C:__:__:__:__:__') AND " . $db_q_standard .
          "GROUP BY station_MAC;";
  $db_result = mysqli_query($db_conn_s, $db_q);
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
function get_fingerprints($mode, $db_conn_s, $time_from_mh, $time_to_mh, $db_q_standard, &$fingerprints) {

  if ($mode == "specific") {
    $specific_mode_fp_mh   = $_SESSION["specific_mode_fp_mh"];
    $specific_fp_mh        = $_SESSION["specific_fp_mh"];
  }

  $db_q = "SELECT SUBSTRING(probed_ESSIDs,19,1000) FROM Clients WHERE
          (LENGTH(probed_ESSIDs) > 18) AND
          (last_time_seen BETWEEN '" . $time_from_mh . "' AND '" . $time_to_mh . "') AND NOT
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
              exit("function get_fingerprints ERROR: Unknown specific ESSID mode: " . $specific_mode_fp_mh);
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

function get_bd_addrs($db_conn_s, $time_from_mh, $time_to_mh, &$bd_addrs) {
  $db_q = "SELECT BD_ADDR FROM Bluetooth WHERE
          (last_time_seen BETWEEN '" . $time_from_mh . "' AND '" . $time_to_mh . "')
           GROUP BY BD_ADDR;";
  $db_result = mysqli_query($db_conn_s, $db_q);
  // append result to bd_addrs
  if (mysqli_num_rows($db_result) > 0) {
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      $bd_addrs[] = $db_row["BD_ADDR"];
    }
  }
  mysqli_free_result($db_result);
}

function get_timestamps($db_conn, $db_q){
  unset($timestamps);
  if (!$db_conn){
    die("function get_timestamps ERROR: Database connection failed");
  } else {
    $db_result = mysqli_query($db_conn, $db_q);
    while ($db_row = mysqli_fetch_assoc($db_result)){
      $timestamps[] = $db_row["last_time_seen"];
    }
  }
  return $timestamps;
}

function in_both($A, $B){
  foreach ($A as $A_p => $A_v){
    foreach ($B as $B_p => $B_v){
      if ($A_v == $B_v){
        $result[] = $A_v;
      }
    }
  }
  return $result;
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
            exit("function blacklisted ERROR: Unknown local MAC mode: " . $blacklist_mode_fp_mh);
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
              //          A | B | diff
  public $BA; // 2D array B | A | diff
              //          B | A | diff
}

// function accepts list of MAC/BD_ADDR addresses or 2D array of
// probed ESSIDs fingerprints and returns array of Movement classes
function process_keys($type, $db_q_standard, $keys,
                      $blacklist, $threshold, $db_conn_A, $db_conn_B,
                      $timestamp_limit, $time_from, $time_to,
                      $time_increment, &$chart_unique, &$chart_total,
                      &$ignored, &$blacklisted) {
  
  foreach ($keys as $keys_key => $keys_value) {
    
    // customize algorithm to specific keys type
    switch ($type) {
      case "wifi_global":
        $db_q = "SELECT last_time_seen FROM Clients WHERE
                (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                 AND " . $db_q_standard . " AND (station_MAC = '" . $keys_value . "');";
        break;
      case "wifi_local": 
        $db_q = "SELECT last_time_seen FROM Clients WHERE
                (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                 AND " . $db_q_standard . " AND (SUBSTRING(probed_ESSIDs,19,1000) = '" . $keys_value . "');";
        break;
      case "bt":
        $db_q = "SELECT last_time_seen FROM Bluetooth WHERE
                (last_time_seen BETWEEN '" . $time_from . "' AND '" . $time_to . "')
                 AND (BD_ADDR = '" . $keys_value . "');";
        break;
      default:
        die("function process_keys ERROR: Unknown type: " . $type);
    }

    // get timestamps for key from database A and B
    $timestampsA = get_timestamps($db_conn_A, $db_q);
    $timestampsB = get_timestamps($db_conn_B, $db_q);

    echo "key:" . $keys_value . "<br>";
    echo "tsA:";
    var_dump($timestampsA);
    echo "<br>";
    echo "tsB:";
    var_dump($timestampsB);
    echo "<br>";
    echo "<br><br>";


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
  
  // text output
  echo  "Showing results from " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_from_mh)) . "</b>" .
        " to " . "<b>" . date('G:i:s (j.n.Y)', strtotime($time_to_mh)) . "</b>" .
        " with step of " . "<b>" . $time_step_mh . " " . strtolower($time_step_format_mh) . "(s)" . "</b>" .
        "<br><br>";

  // prepare chart arrays
  if ($show_wlan_mh == "1") {
    $i = 0;
    $time_actual = date('Y-m-d H:i:s', (strtotime($time_from_mh) + $time_increment));
    while (strtotime($time_actual) <= strtotime($time_to_mh)) {
      $chart_wifi_unique_mh[$i]["x"] = strtotime($time_actual)*1000;
      $chart_wifi_unique_mh[$i]["y"] = 0;
      $chart_wifi_total_mh[$i]["x"] = strtotime($time_actual)*1000;
      $chart_wifi_total_mh[$i]["y"] = 0;
      $i += 1;
      $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
    }
  }
  if ($show_bt_mh == "1") { 
    $i = 0;
    $time_actual = date('Y-m-d H:i:s', (strtotime($time_from_mh) + $time_increment));
    while (strtotime($time_actual) <= strtotime($time_to_mh)) {
      $chart_bt_unique_mh[$i]["x"] = strtotime($time_actual)*1000;
      $chart_bt_unique_mh[$i]["y"] = 0;
      $chart_bt_total_mh[$i]["x"] = strtotime($time_actual)*1000;
      $chart_bt_total_mh[$i]["y"] = 0;
      $i += 1;
      $time_actual = date('Y-m-d H:i:s', (strtotime($time_actual) + $time_increment));
    }
  }

  $mac_glbl_moved = 0;
  $mac_local_moved = 0;
  $bt_moved = 0;
  $db_conn_A = mysqli_connect($db_server, $db_user, $db_pass, $db_source_A_mh);
  $db_conn_B = mysqli_connect($db_server, $db_user, $db_pass, $db_source_B_mh);

  // fill key arrays
  // point A
  unset($A_macs);
  unset($A_fingerprints);
  unset($A_bd_addrs);
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
    get_bd_addrs($db_conn_B, $time_from_mh, $time_to_mh, $A_bd_addrs);
  }

  // point B
  unset($B_macs);
  unset($B_fingerprints);
  unset($B_bd_addrs);
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

  unset($macs);
  unset($fingerprints);
  unset($bd_addrs);
  // keep only keys that are in both databases
  $macs         = in_both($A_macs, $B_macs);
  $fingerprints = in_both($A_fingerprints, $B_fingerprints);
  $bd_addrs     = in_both($A_bd_addrs, $B_bd_addrs);

  // find movement for each key
  process_keys("wifi_global", $db_q_standard, $macs,
               $blacklist_wlan_mh, $threshold_seconds, $db_conn_A, $db_conn_B,
               $timestamp_limit_mh, $time_from_mh, $time_to_mh,
               $time_increment, $chart_unique, $chart_total,
               $ignored, $blacklisted);

  // --------------------------------------------------------------------------- debug output

  echo "<br>point A<br>";
  echo "<br><br>macs A:<br>";
  var_dump($A_macs);
  echo "<br><br>fingerprints A:<br>";
  var_dump($A_fingerprints);
  echo "<br><br>bd_addrs A:<br>";
  var_dump($A_bd_addrs);

  echo "<br><br><br>point B<br>";
  echo "<br><br>macs B:<br>";
  var_dump($B_macs);
  echo "<br><br>fingerprints B:<br>";
  var_dump($B_fingerprints);
  echo "<br><br>bd_addrs B:<br>";
  var_dump($B_bd_addrs);

  echo "<br><br><br>in both:<br>";
  echo "<br><br>macs:<br>";
  var_dump($macs);
  echo "<br><br>fingerprints:<br>";
  var_dump($fingerprints);
  echo "<br><br>bd_addrs:<br>";
  var_dump($bd_addrs);
  echo "<br><br>";

  die("debugging end");

  // --------------------------------------------------------------------------- debug end



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
    foreach ($db_source_mh as $key => $value) {
      echo "<b>Database: " . $value . "</b><br>";
      $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $value);
      if ($show_wlan_mh == "1") {
        if ($mac_glbl_passed > 0) {
          echo "Wi-Fi devices with global MAC address:<br>";
          process_keys("wifi_global", $db_q_standard, $macs,
                       $blacklist_wlan_mh, $db_conn_s, $threshold_seconds,
                       $timestamp_limit_mh, $time_from_mh, $time_to_mh,
                       $time_increment, $chart_wifi_unique_mh, $chart_wifi_total_mh,
                       $mac_glbl_ignored, $mac_glbl_blacklisted);
        }
          if ($mac_local_passed > 0) {
          echo "Wi-Fi devices with local MAC address:<br>";
          process_keys("wifi_local", $db_q_standard, $fingerprints,
                       $blacklist_fp_mh, $db_conn_s, $threshold_seconds,
                       $timestamp_limit_mh, $time_from_mh, $time_to_mh,
                       $time_increment, $chart_wifi_unique_mh, $chart_wifi_total_mh,
                       $mac_local_ignored, $mac_local_blacklisted);
        }
      }
      if ($show_bt_mh == "1") {
        if ($bt_passed > 0) {
          echo "Bluetooth devices:<br>";        
          process_keys("bt", "1", $bd_addrs,
                       $blacklist_bt_mh, $db_conn_s, $threshold_seconds,
                       $timestamp_limit_mh, $time_from_mh, $time_to_mh,
                       $time_increment, $chart_bt_unique_mh, $chart_bt_total_mh,
                       $bt_ignored, $bt_blacklisted);
        }
      }
    }
  }
  
  // text output table
  if ($show_wlan_mh == "1") {
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
  if ($show_bt_mh == "1") {
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
  $f_wifi_unique_mh = fopen($json_dir . "/chart_wifi_unique_mh_" . $session_id, "w");
  $f_wifi_total_mh  = fopen($json_dir . "/chart_wifi_total_mh_" . $session_id, "w");
  $f_bt_unique_mh   = fopen($json_dir . "/chart_bt_unique_mh_" . $session_id, "w");
  $f_bt_total_mh    = fopen($json_dir . "/chart_bt_total_mh_" . $session_id, "w");
  fwrite($f_wifi_unique_mh, json_encode($chart_wifi_unique_mh));
  fwrite($f_wifi_total_mh,  json_encode($chart_wifi_total_mh));
  fwrite($f_bt_unique_mh,   json_encode($chart_bt_unique_mh));
  fwrite($f_bt_total_mh,    json_encode($chart_bt_total_mh));
  fclose($f_wifi_unique_mh);
  fclose($f_wifi_total_mh);
  fclose($f_bt_unique_mh);
  fclose($f_bt_total_mh);

  // algorithm execution end
  $alg_end = time();
  $mem_peak = memory_get_peak_usage();
  $mem_peak = $mem_peak / 1000000; // MB
  echo "Algorithm finished in " . ($alg_end - $alg_start) . " seconds.<br>";
  echo "Memory usage peak: " . round($mem_peak, 2) . " MB";
}
?>
