<?php
session_start();

$session_id = session_id();

$db_server  = $_SESSION["db_server"];
$db_user    = $_SESSION["db_user"];
$db_pass    = $_SESSION["db_pass"];
$hostname   = $_SESSION["hostname"];

$db_source  = $_SESSION["db_source"];
$timeperiod = $_SESSION["timeperiod"];
$time_since = $_SESSION["time_since"];
$time_until = $_SESSION["time_until"];

$db_conn    = mysqli_connect("p:" . $db_server, $db_user, $db_pass);

if(!$db_conn){
  $_SESSION["warn"] = "Database connection lost! Please login again.";
  header("Location: index.php");
}

// read variables var/...
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

// initialize chart arrays
$_SESSION["chart_wifi_bot"] = array();
$_SESSION["chart_wifi_top"] = array();
$_SESSION["chart_bt"] = array();
?>

<!DOCTYPE html>
<html lang="en">

<!-- HISTORY.php -->

  <head>
    
    <meta charset="utf-8">
    <title><?php echo $hostname ?> monitoring server</title>
    
    <!-- CSS style -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Archivo:500|Open+Sans:300,700">
    <link rel="stylesheet" type="text/css" href="inc/style.css">

    <!-- JavaScript -->
    <script src="inc/js/chart_history.js"></script>
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
        xmlhttp.open("GET", "inc/information.php", true);
        xmlhttp.send();
      }      
      
      function updateTextout(){
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            document.getElementById("textout").innerHTML = this.responseText;
          }
        };
        xmlhttp.open("GET", "inc/textout_history.php", true);
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
    
      <!-- INFORMATION -->
      <div class="div_info">
        <h2>Information</h2>
        <div class="div_content" id="info">
          Loading...
        </div>
      </div>

      <!-- SETTINGS -->
      <div class="div_settings">
        <h2>Settings</h2>
        <div class="div_content">

          <span class="lh_other"><a href="live.php">Live data</a></span> | <span class="lh_current">History</span>

          <form method="get" action="<?php echo $_SERVER['PHP_SELF']?>">

            <div class="div_subcontent">
              <?php
                $settingsok="0";
                include 'inc/settings.php';
                if(!$settingsok == "1") {
                  echo "<p class=\"error\">ERROR: failed to load settings.php - page will not be able to process Settings form</p>";
                }
              ?>
            </div>

            <div class="div_subcontent">
              <b>Time Range</b><br>
              <table class="form">
              <tr><td>From</td></tr>
              <tr><td><input type="text" name="time_since" value="<?php echo $time_since?>" style="width:150px;text-align:center;"></td></tr>
              <tr><td>To</td></tr>
              <tr><td><input type="text" name="time_until" value="<?php echo $time_until?>" style="width:150px;text-align:center;"></td></tr>

              </table>
            </div>

            <div class="div_subcontent">
              <b>Time Period</b><br>
              <table class="form">
              <tr><td><input type="number" name="timeperiod" value="<?php echo $timeperiod?>" min="1" style="width:100px;text-align:center;"></td></tr>
              <tr><td><input type="radio" name="timeperiod_format" value="MINUTE" <?php if ($timeperiod_format == "MINUTE") {echo "checked";} ?>> Minute(s) </td></tr>
              <tr><td><input type="radio" name="timeperiod_format" value="HOUR"   <?php if ($timeperiod_format == "HOUR")   {echo "checked";} ?>> Hour(s) </td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Show Data</b><br>
              <table class="form">
              <tr><td><input type="checkbox" name="showwlan" value="1" <?php if ($showwlan == "1") { echo "checked";} ?>></td><td> Wi-Fi </td></tr>
              <tr><td><input type="checkbox" name="showbt"   value="1" id="chckb_bt_data" onclick="toggle_ib_bt_data()" <?php if ($showbt == "1") { echo "checked";} ?>></td><td> Bluetooth </td></tr>
              </table>
            </div>

            <br><button type="submit">Submit</button>

          </form>

          <p class="info_box">Time range must be entered in this exact format: <b>YYYY-MM-DD HH:MM:SS</b> (eg. 2020-03-20 10:30:00).</p>
          <p class="info_box" id="ib_bt_data" style="display:none"> <?php echo $p_bt_amnesia ?> </p>

        </div>
      </div>
      
      <!-- CHART -->
      <div class="div_chart">
        <h2>Chart</h2>
        <div id="chartContainer" style="height: 370px; width: 100%;">
          Loading...
        </div>
        <script src="inc/js/canvasjs.min.js"></script>
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
