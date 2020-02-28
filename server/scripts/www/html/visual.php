<?php
session_start();
$db_server  = $_SESSION["db_server"];
$db_user    = $_SESSION["db_user"];
$db_pass    = $_SESSION["db_pass"];
$hostname   = $_SESSION["hostname"];

$db_source  = $_SESSION["db_source"];
$timeperiod = $_SESSION["timeperiod"];

$db_conn    = mysqli_connect("p:" . $db_server, $db_user, $db_pass);
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

      // update functions
      function updateAll(){
        updateInfo();
        updateTextout();
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
      setInterval(function(){updateTextout()}, 1000); // 1 sec
      
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

          <?php echo "<p class=\"error\">ERROR: this text should not be visible, something went wrong with automatic update of information</p>";?>

        </div>
      </div>

      <!-- SETTINGS -->
      <div class="div_settings">
        <h2>Settings</h2>
        <div class="div_content">

          <form method="get" action="<?php echo $_SERVER['PHP_SELF']?>">

            <?php
              $settingsok="0";
              include 'inc/settings.php';
              if(!$settingsok == "1") {
                echo "<p class=\"error\">ERROR: failed to load settings.php - page will not be able to process Settings form</p>";
              }
            ?>
	  
            <br>Time Period<br>
            <table class="form">
            <tr><td><input type="text" name="timeperiod" value="<?php echo $timeperiod?>"></td></tr>
            <tr><td><input type="radio" name="timeperiod_format" value="MINUTE" <?php if ($timeperiod_format == "MINUTE") {echo "checked";} ?>> Minute(s) </td></tr>
            <tr><td><input type="radio" name="timeperiod_format" value="HOUR"   <?php if ($timeperiod_format == "HOUR")   {echo "checked";} ?>> Hour(s) </td></tr>
            </table>

            <br>Refresh Interval<br>
            <table class="form">
            <tr><td><input type="text" name="refresh" value="<?php echo $refresh?>"></td></tr>
            <tr><td><input type="radio" name="refresh_format" value="sec" <?php if ($refresh_format == "sec") {echo "checked";} ?>> Second(s) </td></tr>
            <tr><td><input type="radio" name="refresh_format" value="min" <?php if ($refresh_format == "min") {echo "checked";} ?>> Minute(s) </td></tr>
            </table>

            <br>Show Data<br>
            <table class="form">
            <tr><td><input type="checkbox" name="showwlan" value="1" <?php if ($showwlan == "1") { echo "checked";} ?>></td><td> Wi-Fi </td></tr>
            <tr><td><input type="checkbox" name="showbt"   value="1" <?php if ($showbt == "1")   { echo "checked";} ?>></td><td> Bluetooth </td></tr>
            </table>
	    
            <input type="submit" value="Submit">

	        </form>
        </div>
      </div>
      
      <!-- GRAPH -->
      <div class="div_graph">
        <h2>Graph</h2>
        <div class="div_content">

          <?php
            $graphok="0";
            include 'inc/graph.php';
            if(!$graphok == "1") {
              echo "<p class=\"error\">ERROR: failed to load graph.php - page will not be able to show visual output of monitoring</p>";
            }
          ?>

        </div>
      </div>

      <!-- TEXT OUTPUT -->
      <div class="div_text">
        <h2>Text output</h2>
        <div class="div_content" id="textout">

          <?php echo "<p class=\"error\">ERROR: this text should not be visible, something went wrong with automatic update of text output</p>";?>

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
