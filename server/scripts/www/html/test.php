<?php
// get session variables
session_start();
$db_server          = $_SESSION["db_server"];
$db_user            = $_SESSION["db_user"];
$db_pass            = $_SESSION["db_pass"];
$db_source          = $_SESSION["db_source"];
$timeperiod         = $_SESSION["timeperiod"];
$timeperiod_format  = $_SESSION["timeperiod_format"];
$hostname           = $_SESSION["hostname"];
?>
<!DOCTYPE html>
<html lang="en">

<!-- INDEX.php -->

  <head>
    
    <meta charset="utf-8">
    <title><?php echo $hostname ?> monitoring server</title>
    
    <!-- CSS style -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Archivo:500|Open+Sans:300,700">
    <link rel="stylesheet" type="text/css" href="inc/style.css">
  
  </head>
  <body>
    <div class="div_main">
    
      <!-- HEADER -->
      <div class="div_h1">
        <a href="<?php echo $_SERVER['PHP_SELF']?>">
        <h1>Raspberry Pi <?php if (isset($hostname)){ echo "(" . $hostname . ")"; } ?></h1>
        <h3>monitoring server visualization interface</h3></a>
        <hr>
      </div>
    
      <!-- LOGIN -->
      <div class="div_login">
      
      <?php

        function is_anagram($string1, $string2){
          if(count_chars($string1, 1) == count_chars($string2, 1))
            return 1;
          else
            return 0;
        }

        // DB conn with specified source
        $db_conn_s = mysqli_connect('localhost', 'mon', 'vutbrno2019', 'rpi_mon_node_1');

        // local MAC unique probe request fingerprints assoc array
        $db_q      = "SELECT SUBSTRING(probed_ESSIDs,19,1000) FROM Clients WHERE 
                     (LENGTH(probed_ESSIDs) > 18) AND
                     (last_time_seen >= (DATE_SUB(CURRENT_TIMESTAMP, INTERVAL " . "248" . " " . "HOUR" . "))) AND NOT
                     (station_MAC LIKE '_0:__:__:__:__:__' OR
                      station_MAC LIKE '_4:__:__:__:__:__' OR
                      station_MAC LIKE '_8:__:__:__:__:__' OR
                      station_MAC LIKE '_C:__:__:__:__:__')
                      GROUP BY SUBSTRING(probed_ESSIDs,19,1000);";
        $db_result = mysqli_query($db_conn_s, $db_q);

        // fill fingerprints array with db query result
        if (mysqli_num_rows($db_result) > 0) {
          while ($db_row = mysqli_fetch_assoc($db_result)) {
            // push rows to array
            $fingerprints[] = $db_row["SUBSTRING(probed_ESSIDs,19,1000)"];
          }
        } 

        echo "<table style=\"text-align:left;border-collapse:collapse\">";
        foreach ($fingerprints as $master_key => &$master_value) {
          echo "<tr class=\"info\">";
            // print master index & fingerprint
            echo "<td>" . "[" . $master_key . "]" . "</td><td>" . $master_value . "</td>";
            // check all fingerprints for anagrams
            foreach ($fingerprints as $search_key => &$search_value) {
              $anagram = is_anagram($master_value, $search_value);
              // if anagram (self anagram does not count)
              if (($anagram == 1) and ($master_key != $search_key)) {
                // print anagram index & fingerprint
                echo "<td>" . "anagram of [" . $search_key . "]" . "</td><td>" . $search_value . "</td>";
                // delete anagram from fingerprints array
                unset($fingerprints[$search_key]);
              }
            }
          echo "</tr>";
        }
        echo "</table>";

        print_r($fingerprints);

        

      ?>

      </div>

      <!-- FOOTER -->
      <div class="div_foot">
        <hr>
        <p>Samuel Petráš (203317) - Bakalárska práca - VUT FEKT - 2020</p>
      </div>
    </div>
  </body>
</html>
