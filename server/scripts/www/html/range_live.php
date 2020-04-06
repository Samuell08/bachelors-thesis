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

// reset and initialize live chart arrays
$_SESSION["chart_wifi_bot"] = array();
$_SESSION["chart_wifi_top"] = array();
$_SESSION["chart_bt"] = array();

$_SESSION["updateInterval"] = 30000;
?>

<!DOCTYPE html>
<html lang="en">

<!-- LIVE.php -->

  <head>
    
    <meta charset="utf-8">
    <title><?php echo $hostname ?> monitoring server</title>
    
    <!-- CSS style -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Archivo:600|Roboto:400">
    <link rel="stylesheet" type="text/css" href="inc/common/style.css">

    <!-- JavaScript -->
    <script src="inc/range/js/chart_live.js"></script>
    <script>
      
      var updateInterval = <?php echo $_SESSION["updateInterval"]?>; 

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
        xmlhttp.open("GET", "inc/range/textout_live.php", true);
        xmlhttp.send();
      }      
      
      // timers
      setInterval(function(){updateTextout();}, updateInterval);
      
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
          $_SESSION["menu_current"] = 1;
          include 'inc/common/menu.php';
        ?>
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
          <span class="lh_current">Live data</span> | <span class="lh_other"><a href="range_history.php" >History</a></span>

          <form method="get" action="<?php echo $_SERVER['PHP_SELF']?>">

            <div class="div_subcontent">
              <?php
                $settingsok="0";
                include 'inc/range/settings_live.php';
                $_SESSION["common_settings_input_name"] = "db_source_rl[]";
                $_SESSION["common_settings_db_source"] = json_encode($_SESSION["db_source_rl"]);
                include 'inc/common/settings.php';
                if(!$settingsok == "1") {
                  echo "<p class=\"error\">ERROR: failed to load settings script - page will not be able to process Settings form</p>";
                }
              ?>
            </div>
	  
            <div class="div_subcontent">
              <b>Time Period</b><br>
              <table class="form">
              <tr><td><input type="number" name="time_period_rl" value="<?php echo $time_period_rl?>" min="1" style="width:100px;text-align:center;"></td></tr>
              <tr><td><input type="radio" name="time_period_format_rl" value="MINUTE" <?php if ($time_period_format_rl == "MINUTE") {echo "checked";} ?>> Minute(s) </td></tr>
              <tr><td><input type="radio" name="time_period_format_rl" value="HOUR"   <?php if ($time_period_format_rl == "HOUR")   {echo "checked";} ?>> Hour(s) </td></tr>
              </table>
            </div>

            <div class="div_subcontent">
              <b>Show Data</b><br>
              <table class="form">
              <tr><td><input type="checkbox" name="show_wlan_rl" value="1" <?php if ($show_wlan_rl == "1") { echo "checked";} ?>></td><td> Wi-Fi </td></tr>
              <tr><td><input type="checkbox" name="show_bt_rl"   value="1" id="chckb_bt_data" onclick="toggle_ib_bt_data()" <?php if ($show_bt_rl == "1") { echo "checked";} ?>></td><td> Bluetooth </td></tr>
              </table>
            </div>

            <br><button type="submit">Submit</button>

          </form>

          <p class="info_box" id="ib_bt_data" style="display:none"> Bluetooth monitoring data meaning based on amnesia mode:<br>
                                                                    <b>&nbsp;&nbsp;&nbsp;enabled - </b>total number of devices in range within Time Period
                                                                    (Time Period <b>must</b> be set to same time as amnesia)<br>
                                                                    <b>&nbsp;&nbsp;&nbsp;disabled - </b>number of newly discovered devices within Time Period
                                                                    </p>

        </div>
      </div>
      
      <!-- CHART -->
      <div class="div_chart">
        <h2>Chart</h2>
        <div id="chartContainer" style="height: 370px; width: 100%;">
          Loading...
        </div>
        <script src="inc/common/js/canvasjs.min.js"></script>
      </div>

      <!-- TEXT OUTPUT -->
      <div class="div_text">
        <h2>Text output</h2>
        <div class="div_content" id="textout">
          Loading...
        </div>
      </div>
      
      <!-- FOOTER -->
      <div class="div_foot">
        <?php include 'inc/common/footer.php' ?>
      </div>
    </div>
  </body>
</html>
