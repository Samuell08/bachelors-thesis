<?php
session_start();

$session_id = session_id();

$db_server  = $_SESSION["db_server"];
$db_user    = $_SESSION["db_user"];
$db_pass    = $_SESSION["db_pass"];
$hostname   = $_SESSION["hostname"];

$db_source  = $_SESSION["db_source"];
$timeperiod = $_SESSION["timeperiod"];

$db_conn    = mysqli_connect("p:" . $db_server, $db_user, $db_pass);

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

// update interval setting
$_SESSION["updateInterval"] = 30000;
?>
<!DOCTYPE html>
<html lang="en">

<!-- VISUAL.php -->

  <head>
    
    <meta charset="utf-8">
    <title><?php echo $hostname ?> monitoring server</title>
    
    <!-- CSS style -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Archivo:500|Open+Sans:300,700">
    <link rel="stylesheet" type="text/css" href="inc/style.css">

    <!-- JavaScript -->
    <script>

      var updateInterval = <?php echo $_SESSION["updateInterval"]; ?>; 

      function buildChart() {
 
        var colorWifi = "#1b81e5";
        var colorWifiLocal = "#78bcff";
        var colorBluetooth = "#061c33";

        var chart = new CanvasJS.Chart("chartContainer", {
          theme: "light2",
          zoomEnabled: true,
          title: {
            text: "Monitoring results"
          },
          subtitles: [{
            text: "updated every " + updateInterval/1000 + " seconds"
          }],
          axisX: {
            title: "Timestamp",
            valueFormatString: "D.M H:mm",
            gridThickness: 1,
            gridDashType: "dash",
            labelAngle: -45
          },
          axisY: {
            title: "Wi-Fi devices",
            titleFontColor: colorWifi,
            labelFontColor: colorWifi,
            gridDashType: "dash",
            tickThickness: 0
          },
          axisY2: {
            title: "Bluetooth devices",
            titleFontColor: colorBluetooth,
            labelFontColor: colorBluetooth,
            gridDashType: "dash",
            tickThickness: 0
          },
          toolTip: {
            shared: true,
            cornerRadius: 15
          },
          legend: {
            cursor: "pointer",
            itemclick: toggleDataSeries
          },
          data: [{
            type: "stackedArea",
            name: "Global MAC",
            color: colorWifi,
            showInLegend: true,
            toolTipContent: "{x} <hr> <span style=\"color:" + colorWifi + "\"><b>{name}: </b></span> {y}",
            xValueType: "dateTime",
            xValueFormatString: "D.M H:mm:ss",
            yValueFormatString: "#",
            dataPoints: [{"x":1000,"y":0}]
          },{
            type: "stackedArea",
            name: "Local MAC unique",
            color: colorWifiLocal,
            showInLegend: true,
            toolTipContent: "<span style=\"color:" + colorWifiLocal + "\"><b>{name}: </b></span> {y} <br> <b>Total estimated: #total</b>",
            xValueType: "dateTime",
            yValueFormatString: "#",
            dataPoints: [{"x":1000,"y":0}]
          },{
            type: "line",
            axisYType: "secondary",
            name: "Bluetooth",
            color: colorBluetooth,
            markerType: "square",
            showInLegend: true,
            toolTipContent: "<hr> <span style=\"color:" + colorBluetooth + "\"><b>{name}: </b></span> {y}",
            xValueType: "dateTime",
            yValueFormatString: "#",
            dataPoints: [{"x":1000,"y":0}]
          }]
        });

        chart.render();
        updateChart();

        function readTextFile(file) {
          var rawFile = new XMLHttpRequest();
          // when file does not exists yet, return default value
          var retVal = '[{"x":1000,"y":0}]';
          rawFile.open("GET", file, false);
          rawFile.onreadystatechange = function () {
            if(rawFile.readyState === 4) {
              if(rawFile.status === 200 || rawFile.status == 0) {
                var allText = rawFile.responseText;
                retVal = String(allText);
              }
            }
          }
          rawFile.send(null);
          return retVal;
        }

        function toggleDataSeries(e){
          if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
            e.dataSeries.visible = false;
          } else {
            e.dataSeries.visible = true;
          }
          chart.render();
        }

        function updateChart() {
          var session_id = "<?php echo $session_id; ?>";
          var dps_wifi_bot = readTextFile("json/chart_wifi_bot_" + session_id);
          var dps_wifi_top = readTextFile("json/chart_wifi_top_" + session_id);
          var dps_bt = readTextFile("json/chart_bt_" + session_id);
          chart.options.data[0].dataPoints = JSON.parse(dps_wifi_bot); 
          chart.options.data[1].dataPoints = JSON.parse(dps_wifi_top);
          chart.options.data[2].dataPoints = JSON.parse(dps_bt);
          chart.render();
        };
        
        setInterval(function () { updateChart() }, updateInterval);
      }

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
        xmlhttp.open("GET", "inc/textout.php", true);
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
              <b>Time Period</b><br>
              <table class="form">
              <tr><td><input type="number" name="timeperiod" value="<?php echo $timeperiod?>" min="1" style="width:100px;"></td></tr>
              <tr><td><input type="radio" name="timeperiod_format" value="MINUTE" <?php if ($timeperiod_format == "MINUTE") {echo "checked";} ?>> Minute(s) </td></tr>
              <tr><td><input type="radio" name="timeperiod_format" value="HOUR"   <?php if ($timeperiod_format == "HOUR")   {echo "checked";} ?>> Hour(s) </td></tr>
              </table>
            </div>

            <!-- not used
            <div class="div_subcontent">
              <b>Refresh Interval</b><br>
              <table class="form">
              <tr><td><input type="number" name="refresh" value="<?php echo $refresh?>" min="1" style="width:100px;"></td></tr>
              <tr><td><input type="radio" name="refresh_format" value="sec" <?php if ($refresh_format == "sec") {echo "checked";} ?>> Second(s) </td></tr>
              <tr><td><input type="radio" name="refresh_format" value="min" <?php if ($refresh_format == "min") {echo "checked";} ?>> Minute(s) </td></tr>
              </table>
            </div>
            -->

            <div class="div_subcontent">
              <b>Show Data</b><br>
              <table class="form">
              <tr><td><input type="checkbox" name="showwlan" value="1" <?php if ($showwlan == "1") { echo "checked";} ?>></td><td> Wi-Fi </td></tr>
              <tr><td><input type="checkbox" name="showbt"   value="1" id="chckb_bt_data" onclick="toggle_ib_bt_data()" <?php if ($showbt == "1")   { echo "checked";} ?>></td><td> Bluetooth </td></tr>
              </table>
            </div>

            <br><button type="submit">Submit</button>

          </form>

          <p id="ib_bt_data" class="info_box" style="display:none"> <?php echo $p_bt_amnesia ?> </p>

        </div>
      </div>
      
      <!-- CHART -->
      <div class="div_chart">
        <h2>Chart</h2>
        <div id="chartContainer" style="height: 370px; width: 100%;">
          Loading...
        </div>
        <script src="inc/canvasjs.min.js"></script>
      </div>

      <!-- TEXT OUTPUT -->
      <div class="div_text">
        <h2>Text output</h2>
        <div class="div_content" id="textout">
          Loading...
        </div>
      </div>
      
      <!-- DEBUG
      <div class="div_debug">
        <hr><h2>Debug</h2>
        <div class="div_content">
          This file: <?php echo $_SERVER['PHP_SELF']?><br><br>

          MySQL connection information: <?php echo mysqli_get_host_info($db_conn)?><br>
          MySQL connection error:       <?php echo mysqli_connect_error()?><br><br>

          <i>Settings form:</i><br>
          DB Source:                <?php echo var_dump($db_source)?><br>
          DB Password:              <?php echo $db_pass?><br>
          Time Period:              <?php echo $timeperiod?><br>
          Time Period Format:       <?php echo $timeperiod_format?><br>
          Refresh Interval:         <?php echo $refresh?><br>
          Refresh Interval Format:  <?php echo $refresh_format ?><br>
          Show Data Wi-Fi:          <?php echo $showwlan?><br>
          Show Data Bluetooth:      <?php echo $showbt?>
        </div>
      </div>
      -->

      <!-- FOOTER -->
      <div class="div_foot">
        <hr>
        <p>Samuel Petráš (203317) - Bakalárska práca - VUT FEKT - 2020</p>
      </div>
    </div>
  </body>
</html>
