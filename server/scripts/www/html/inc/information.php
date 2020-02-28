<?php
// INFORMATION.php
// PHP code to be called from visual.php

// get session variables
session_start();
$db_server          = $_SESSION["db_server"];
$db_user            = $_SESSION["db_user"];
$db_pass            = $_SESSION["db_pass"];

// DB conn with specified source
$db_conn_s = mysqli_connect($db_server, $db_user, $db_pass);

// DYNAMIC INFORMATION PART
// Database Size(s)
echo "<b>Database Sizes(s)</b><br>";
// form sending buttons to PHP script db_delete.php
echo "<form method=\"post\" action=\"inc/db_delete.php\">";
  echo "<table class=\"info\">";

  if (!$db_conn_s) {
    echo "<tr><td>" . "<p style=\"color:OrangeRed;font-weight:bold\">Database not connected!</p>" . "</td></tr>";
  } else {
    // information about database size in MB
    $db_q      = "SELECT table_schema \"DB_name\",
                  ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) \"DB_size_MB\"
                  FROM information_schema.tables
                  WHERE table_schema LIKE 'rpi_mon_%'
                  GROUP BY table_schema;";
    $db_result = mysqli_query($db_conn_s, $db_q);
  
    if (mysqli_num_rows($db_result) > 0) {

      // table header row
      echo "<tr>";
      echo "<th class=\"info\">" . "Database name" . "</th>";
      echo "<th class=\"info\">" . "Database size (MB)" . "</th>";
      echo "<th class=\"info\">" . "Delete entries older than #" . "</th>";
      echo "<th class=\"info\">" . "Delete all entries" . "</th>";
      echo "</tr>";
    
      while ($db_row = mysqli_fetch_assoc($db_result)) {
        // table row
        echo "<tr>";
        // table column DB name
        echo "<td>" . $db_row["DB_name"] . "</td>";
        // table column DB size in MB
        echo "<td>" . $db_row["DB_size_MB"] . "</td>";
        // delete older than # button
        echo "<td>" . "<button type=\"submit\" name=\"db_delete\" value=\"" . $db_row["DB_name"] . "\">" . "Delete Older" . "</button>" . "</td>";
        // delete all button
        echo "<td>" . "<button type=\"submit\" name=\"db_delete_all\" value=\"" . $db_row["DB_name"] . "\">" . "Delete All" . "</button>" . "</td>";
        echo "</tr>";
      }
    } else {
      echo "<tr><td>" . "0 available databases" . "</td></tr>";
    }
  }

  echo "</table>";
echo "</form>";

// update Information div button
echo "Database size column takes couple of seconds to update, you need to refresh manually!<br>";
echo "<button onclick=\"updateInfo()\">Refresh</button>";
?>
