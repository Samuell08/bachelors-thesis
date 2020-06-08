<?php
session_start();
$session_id = session_id();

// connect to MySQL database
$db_server  = $_SESSION["db_server"];
$db_user    = $_SESSION["db_user"];
$db_pass    = $_SESSION["db_pass"];
$hostname   = $_SESSION["hostname"];
$db_conn    = mysqli_connect("p:" . $db_server, $db_user, $db_pass);
if(!$db_conn){
  $_SESSION["warn"] = "Database connection lost! Please login again.";
  header("Location: index.php");
}
?>

<!DOCTYPE html>
<html lang="en">

<!-- HISTORY.php -->
  <head>
    
    <meta charset="utf-8">
    <title><?php echo $hostname ?> monitoring server</title>
    
    <!-- CSS style -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Archivo:600|Roboto:400">
    <link rel="stylesheet" type="text/css" href="inc/common/style.css">

    <!-- JavaScript -->
    <script src="inc/common/js/scripts.js"></script>
    <script src="inc/move/js/scripts_history.js"></script>
    <script src="inc/move/js/chart.js"></script>
    <script src="inc/move/js/chart_history.js"></script>
    <script>
      function updateAll(){
        updateInfo();
        updateTextout();
        buildChart();
        customizeChart();
      }
    </script>
  
  </head>
  <body onload="updateAll()">
    <div class="div_main">
    
      <!-- HEADER -->
      <div class="div_h1">
        <?php include 'inc/common/header.php' ?>
      </div>

      <!-- MENU -->
      <div class="div_menu">
        <?php
          $_SESSION["menu_current"] = 2;
          include 'inc/common/menu.php';
        ?>
      </div>
    
      <!-- INFORMATION -->
      <div class="div_info">
        <h2>Information</h2>
        <div class="div_content">
          <p>
            <b>Movement</b> mode displays monitoring data as device transitions from point A
            to point B (and back) in given time range.
          </p>
        </div>
        <div class="div_content" id="info">
          Loading...
        </div>
      </div>

      <!-- SETTINGS -->
      <div class="div_settings">
        <h2>Settings</h2>
        <div class="div_content">

          <!-- Live data | History --> 
          <span class="lh_current">History</span>

          <form method="get" action="<?php echo $_SERVER['PHP_SELF']?>">

            <div class="div_subcontent">
              <?php
                $settingsok="0";
                include 'inc/move/settings_history.php';
                // variables for common settings PHP script
                $_SESSION["common_settings_input_name"] = "db_source_A_mh";
                $_SESSION["common_settings_input_type"] = "radio";
                $_SESSION["common_settings_db_source"] = json_encode($_SESSION["db_source_A_mh"]);
                echo "<b>Source Database as point A</b><br>";
                include 'inc/common/settings.php';
                if(!$settingsok == "1") {
                  echo "<p class=\"error\">ERROR: failed to load settings script - page will not be able to process Settings form</p>";
                }
              ?>
            </div>

            <div class="div_subcontent">
              <?php
                // variables for common settings PHP script
                $_SESSION["common_settings_input_name"] = "db_source_B_mh";
                $_SESSION["common_settings_input_type"] = "radio";
                $_SESSION["common_settings_db_source"] = json_encode($_SESSION["db_source_B_mh"]);
                echo "<b>Source Database as point B</b><br>";
                include 'inc/common/settings.php';
              ?>
            </div>

            <div class="div_subcontent">
              <b>Time Range</b><br>
              <table class="form">
                <tr><td>From</td></tr>
                <tr><td><input type="text" name="time_from_mh" value="<?php echo $time_from_mh?>" style="width:150px;text-align:center;"></td></tr>
                <tr><td>To</td></tr>
                <tr><td><input type="text" name="time_to_mh" value="<?php echo $time_to_mh?>" style="width:150px;text-align:center;"></td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Time Step</b><br>
              <table class="form">
                <tr><td><input type="number" name="time_step_mh" value="<?php echo $time_step_mh?>" min="1" style="width:100px;text-align:center;"></td></tr>
                <tr><td><input type="radio" name="time_step_format_mh" value="SECOND" <?php if ($time_step_format_mh == "SECOND") {echo "checked";} ?>> Second(s) </td></tr>
                <tr><td><input type="radio" name="time_step_format_mh" value="MINUTE" <?php if ($time_step_format_mh == "MINUTE") {echo "checked";} ?>> Minute(s) </td></tr>
                <tr><td><input type="radio" name="time_step_format_mh" value="HOUR"   <?php if ($time_step_format_mh == "HOUR")   {echo "checked";} ?>> Hour(s) </td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Threshold</b><br>
              <table class="form">
                <tr><td>Number of shortest movement<br>times to average</td></tr>
                <tr><td><input type="number" name="threshold_num_mh" value="<?php echo $threshold_num_mh?>" min="1" style="width:100px;text-align:center;"></td></tr>
                <tr><td>Multiplier of the average</td></tr>
                <tr><td><input type="number" name="threshold_mult_mh" value="<?php echo $threshold_mult_mh?>" min="0" step="0.01" style="width:100px;text-align:center;"></td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Absolute Maximum Threshold</b><br>
              <table class="form">
                <tr><td><input type="checkbox" name="threshold_max_chk_mh" value="1" <?php if ($threshold_max_chk_mh == "1") { echo "checked";} ?>> Use absolute maximum<br>threshold </td></tr>
                <tr><td><input type="number" name="threshold_max_mh" value="<?php echo $threshold_max_mh?>" min="1" step="1" style="width:100px;text-align:center;"></td></tr>
                <tr><td><input type="radio" name="threshold_max_format_mh" value="SECOND" <?php if ($threshold_max_format_mh == "SECOND") {echo "checked";} ?>> Second(s) </td></tr>
                <tr><td><input type="radio" name="threshold_max_format_mh" value="MINUTE" <?php if ($threshold_max_format_mh == "MINUTE") {echo "checked";} ?>> Minute(s) </td></tr>
                <tr><td><input type="radio" name="threshold_max_format_mh" value="HOUR" <?php if ($threshold_max_format_mh == "HOUR") {echo "checked";} ?>> Hour(s) </td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Power Limit</b><br>
              <table class="form">
                <tr><td><input type="checkbox" name="power_limit_chk_mh" value="1" <?php if ($power_limit_chk_mh == "1") { echo "checked";} ?>> Ignore timestamps<br>with lower dBm </td></tr>
                <tr><td><input type="number" name="power_limit_mh" value="<?php echo $power_limit_mh?>" min="-100" max="-10" style="width:100px;text-align:center;"></td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Timestamp Limit</b><br>
              <table class="form">
                <tr><td><input type="checkbox" name="timestamp_limit_chk_mh" value="1" <?php if ($timestamp_limit_chk_mh == "1") { echo "checked";} ?>> Ignore more than<br>this limit </td></tr>
                <tr><td><input type="number" name="timestamp_limit_mh" value="<?php echo $timestamp_limit_mh?>" min="1" style="width:100px;text-align:center;"></td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Show Data</b><br>
              <table class="form">
                <tr><td><input type="checkbox" name="show_wlan_mh" value="1" <?php if ($show_wlan_mh == "1") { echo "checked";} ?>></td><td> Wi-Fi </td></tr>
                <tr><td><input type="checkbox" name="show_bt_mh" value="1" <?php if ($show_bt_mh == "1") { echo "checked";} ?>></td><td> Bluetooth </td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Wi-Fi Standards</b><br>
              <table class="form">
                <tr><td><input type="checkbox" name="show_wlan_a_mh" value="1" <?php if ($show_wlan_a_mh == "1") { echo "checked";} ?>></td><td> 5GHz (802.11a) </td></tr>
                <tr><td><input type="checkbox" name="show_wlan_bg_mh" value="1" <?php if ($show_wlan_bg_mh == "1") { echo "checked";} ?>></td><td> 2.4GHz (802.11b/g) </td></tr>
              </table>
            </div>

            <br>
            
            <div class="div_subcontent">
              <b>Blacklisted Keys</b><br>
              <table class="form">
                <tr><td><input type="checkbox" name="blacklist_wlan_chk_mh" value="1" <?php if ($blacklist_wlan_chk_mh == "1") { echo "checked";} ?>> Ignore these MAC addresses </td></tr>
                <tr><td><textarea name="blacklist_wlan_mh" cols="60" rows="1"><?php echo $blacklist_wlan_mh?></textarea></td></tr>
                <tr><td><input type="checkbox" name="blacklist_fp_chk_mh" value="1" <?php if ($blacklist_fp_chk_mh == "1") { echo "checked";} ?>> Ignore local MAC addresses when </td></tr>
                <tr><td><input type="radio" name="blacklist_mode_fp_mh" value="ALL" <?php if ($blacklist_mode_fp_mh == "ALL") {echo "checked";} ?>> All probed ESSIDs are blacklisted </td></tr>
                <tr><td><input type="radio" name="blacklist_mode_fp_mh" value="ONE" <?php if ($blacklist_mode_fp_mh == "ONE") {echo "checked";} ?>> At least one probed ESSID is blacklisted </td></tr>
                <tr><td><textarea name="blacklist_fp_mh" cols="60" rows="1"><?php echo $blacklist_fp_mh?></textarea></td></tr>
                <tr><td><input type="checkbox" name="blacklist_bt_chk_mh" value="1" <?php if ($blacklist_bt_chk_mh == "1") { echo "checked";} ?>> Ignore these BD_ADDR addresses </td></tr>
                <tr><td><textarea name="blacklist_bt_mh" cols="60" rows="1"><?php echo $blacklist_bt_mh?></textarea></td></tr>
              </table>
            </div>
            
            <div class="div_subcontent">
              <b>Specific Keys</b><br>
              <table class="form">
                <tr><td><input type="checkbox" name="specific_mac_chk_mh" value="1" <?php if ($specific_mac_chk_mh == "1") { echo "checked";} ?>> Process only these global MAC addresses</td></tr>
                <tr><td><textarea name="specific_mac_mh" cols="60" rows="1"><?php echo $specific_mac_mh?></textarea></td></tr>
                <tr><td><input type="checkbox" name="specific_fp_chk_mh" value="1" <?php if ($specific_fp_chk_mh == "1") { echo "checked";} ?>> Process local MAC only when probed ESSIDs </td></tr>
                <tr><td><input type="radio" name="specific_mode_fp_mh" value="EXACT" <?php if ($specific_mode_fp_mh == "EXACT") {echo "checked";} ?>> Exactly match (anagrams also count)</td></tr>
                <tr><td><input type="radio" name="specific_mode_fp_mh" value="ATLEAST" <?php if ($specific_mode_fp_mh == "ATLEAST") {echo "checked";} ?>> Contain this list (or more) </td></tr>
                <tr><td><textarea name="specific_fp_mh" cols="60" rows="1"><?php echo $specific_fp_mh?></textarea></td></tr>
                <tr><td><input type="checkbox" name="specific_bt_chk_mh" value="1" <?php if ($specific_bt_chk_mh == "1") { echo "checked";} ?>> Process only these BD_ADDR addresses</td></tr>
                <tr><td><textarea name="specific_bt_mh" cols="60" rows="1"><?php echo $specific_bt_mh?></textarea></td></tr>
              </table>
            </div>

            <br><button type="submit">Submit</button>

          </form>

          <p class="info_box">Time Range <b>must</b> be entered in this exact format: <b>YYYY-MM-DD HH:MM:SS</b> (eg. 2020-03-20 10:30:00).</p>
          <p class="info_box">Blacklisted Keys and Specific Keys settings <b>must</b> be entered as comma (,) separated list and values <b>cannot</b> repeat.</p>
          <p class="info_box">Time Step should <b>not</b> be smaller than server import period and Bluelog amnesia mode (when enabled) to display meaningful results.</p>
          <p class="info_box">Threshold specifies number of shortest times of movement to calculate average from and multiplier of this average to filter
                              movements that take longer time. Threshold is calculated for every time step separately and affects every movement that
                              ends within given time step.</p>

        </div>
      </div>
      
      <!-- CHART -->
      <div class="div_chart">
        <h2>Chart</h2>
      
        <div id="chartContainer" style="height: 370px; width: 100%;">
          Loading...
        </div>
        <script src="inc/common/js/canvasjs.min.js"></script>

        <div class="div_content">
          <br><button onclick="updateChart()">Update Chart</button><br>
          <p class="info_box">Chart needs to be updated manually <b>after</b> Text Output is loaded.</p>
        </div>

      </div>

      <!-- TEXT OUTPUT -->
      <div class="div_text">
        <h2>Text Output</h2>
        <div class="div_content" id="textout">
          Loading... This might take a while...
        </div>
      </div>
      
      <!-- FOOTER -->
      <div class="div_foot">
        <?php include 'inc/common/footer.php' ?>
      </div>
    </div>
  </body>
</html>
