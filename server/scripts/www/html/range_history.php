<?php
session_start();

$session_id = session_id();

$db_server  = $_SESSION["db_server"];
$db_user    = $_SESSION["db_user"];
$db_pass    = $_SESSION["db_pass"];
$hostname   = $_SESSION["hostname"];

$db_conn    = mysqli_connect("p:" . $db_server, $db_user, $db_pass);

if(!$db_conn){
  $_SESSION["warn"] = "Database connection lost! Please login again.";
  header("Location: index.php");
}

// read variables var/...
// bt_amnesia
if (file_exists("var/bt_amnesia")) {
  $f_bt_amnesia = fopen("var/bt_amnesia", "r");
  $var_bt_amnesia = fgets($f_bt_amnesia);
  fclose($f_bt_amnesia);
  $p_bt_amnesia= "Bluetooth monitoring is running with amnesia mode <b>enabled</b>. 
                  Time Period <b>must</b> be set to same time to display correct results
                  of Bluetooth monitoring data.<br>Amnesia is set to <b>"
                  . $var_bt_amnesia . " minutes</b>.";
} else {
  $p_bt_amnesia= "Bluetooth monitoring is running <b>without</b> amnesia mode enabled. 
                  Bluetooth monitoring data will show only <b>newly discovered</b> Bluetooth
                  devices within set Time Period.";
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
    <script src="inc/range/js/chart_history.js"></script>
    <script>

      function toggle_ib_bt_data(){
        if (document.getElementById("chckb_bt_data").checked == true) {
          document.getElementById("ib_bt_data").style.display = "block";
        } else {
          document.getElementById("ib_bt_data").style.display = "none";
        }
      }

      // update functions
      function updateAll(){
        updateInfo();
        updateTextout();
        toggle_ib_bt_data();
        buildChart();
      }

      function updateInfo(){
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            document.getElementById("info").innerHTML = this.responseText;
          }
        };
        xmlhttp.open("GET", "inc/common/information.php", true);
        xmlhttp.send();
      }      
      
      function updateTextout(){
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            document.getElementById("textout").innerHTML = this.responseText;
          }
        };
        xmlhttp.open("GET", "inc/range/textout_history.php", true);
        xmlhttp.send();
      }      
      
    </script>
  
  </head>
  <body onload="updateAll()">
    <div class="div_main">
    
      <!-- HEADER -->
      <div class="div_h1">
        <a href="<?php echo $_SERVER['PHP_SELF']?>">
        <h1>Raspberry Pi <?php if (isset($hostname)){ echo "(" . $hostname . ")"; } ?></h1>
        <h3>monitoring server visualization interface</h3></a>
        <hr>
      </div>

      <!-- MENU -->
      <div class="div_menu">
        <div class="div_content">
          
          <!-- Range | Movement | Passages --> 
          <span class="menu_current">In Range</span> |
          <span class="menu_other"><a href="move_live.php">Movement</a></span> |
          <span class="menu_other"><a href="pass_live.php">Passages</a></span>

        </div>
      </div>
    
      <!-- INFORMATION -->
      <div class="div_info">
        <h2>Information</h2>
        <div class="div_content">
          <p><b>In Range</b> mode displays monitoring data as number of devices detected in range of selected source device(s) over given period of time or live.</p>
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
          <span class="lh_other"><a href="range_live.php">Live data</a></span> | <span class="lh_current">History</span>

          <br><img src="inc/range/img/history.svg" style="width:90%;margin:70px auto 20px auto;display:block;">

          <form method="get" action="<?php echo $_SERVER['PHP_SELF']?>">

            <div class="div_subcontent">
              <?php
                $settingsok="0";
                include 'inc/range/settings_history.php';
                // variables for common settings PHP script
                $_SESSION["common_settings_input_name"] = "db_source_rh[]";
                $_SESSION["common_settings_db_source"] = json_encode($_SESSION["db_source_rh"]);
                include 'inc/common/settings.php';
                if(!$settingsok == "1") {
                  echo "<p class=\"error\">ERROR: failed to load settings script - page will not be able to process Settings form</p>";
                }
              ?>
            </div>

            <div class="div_subcontent">
              <b>Time Range</b><br>
              <table class="form">
              <tr><td>From</td></tr>
              <tr><td><input type="text" name="time_from_rh" value="<?php echo $time_from_rh?>" style="width:150px;text-align:center;"></td></tr>
              <tr><td>To</td></tr>
              <tr><td><input type="text" name="time_to_rh" value="<?php echo $time_to_rh?>" style="width:150px;text-align:center;"></td></tr>

              </table>
            </div>

            <div class="div_subcontent">
              <b>Time Step</b><br>
              <table class="form">
              <tr><td><input type="number" name="time_step_rh" value="<?php echo $time_step_rh?>" min="1" style="width:100px;text-align:center;"></td></tr>
              <tr><td><input type="radio" name="time_step_format_rh" value="SECOND" <?php if ($time_step_format_rh == "SECOND") {echo "checked";} ?>> Second(s) </td></tr>
              <tr><td><input type="radio" name="time_step_format_rh" value="MINUTE" <?php if ($time_step_format_rh == "MINUTE") {echo "checked";} ?>> Minute(s) </td></tr>
              <tr><td><input type="radio" name="time_step_format_rh" value="HOUR"   <?php if ($time_step_format_rh == "HOUR")   {echo "checked";} ?>> Hour(s) </td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Time Period</b><br>
              <table class="form">
              <tr><td><input type="number" name="time_period_rh" value="<?php echo $time_period_rh?>" min="1" style="width:100px;text-align:center;"></td></tr>
              <tr><td><input type="radio" name="time_period_format_rh" value="MINUTE" <?php if ($time_period_format_rh == "MINUTE") {echo "checked";} ?>> Minute(s) </td></tr>
              <tr><td><input type="radio" name="time_period_format_rh" value="HOUR"   <?php if ($time_period_format_rh == "HOUR")   {echo "checked";} ?>> Hour(s) </td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Show Data</b><br>
              <table class="form">
              <tr><td><input type="checkbox" name="show_wlan_rh" value="1" <?php if ($show_wlan_rh == "1") { echo "checked";} ?>></td><td> Wi-Fi </td></tr>
              <tr><td><input type="checkbox" name="show_bt_rh"   value="1" id="chckb_bt_data" onclick="toggle_ib_bt_data()" <?php if ($show_bt_rh == "1") { echo "checked";} ?>></td><td> Bluetooth </td></tr>
              </table>
            </div>

            <br><button type="submit">Submit</button>

          </form>

          <p class="info_box">Time range <b>must</b> be entered in this exact format: <b>YYYY-MM-DD HH:MM:SS</b> (eg. 2020-03-20 10:30:00).</p>
          <p class="info_box">Time Step setting should <b>not</b> be smaller than server import period for given source to display meaningful results.</p>
          <p class="info_box" id="ib_bt_data" style="display:none"> <?php echo $p_bt_amnesia ?> </p>

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
          <p class="info_box">Chart needs to be updated manually <b>after</b> Text output is loaded.</p>
        </div>
      </div>

      <!-- TEXT OUTPUT -->
      <div class="div_text">
        <h2>Text output</h2>
        <div class="div_content" id="textout">
          Loading... This might take a while... \_(ツ)_/
        </div>
      </div>
      
      <!-- FOOTER -->
      <div class="div_foot">
        <hr>
        <p>Samuel Petráš (203317) - Bakalárska práca - VUT FEKT - 2020</p>
      </div>
    </div>
  </body>
</html>
