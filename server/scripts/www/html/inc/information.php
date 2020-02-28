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
    // table row DB name
    echo "<tr><th>" . "Database Name" . "</th>";
    // table row DB size in MB
    echo "<th>" . "Database Size (MB)" . "</th></tr>";
    
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      // table row DB name
      echo "<tr><td>" . $db_row["DB_name"] . "</td>";
      // table row DB size in MB
      echo "<td>" . $db_row["DB_size_MB"] . "</td></tr>";
    }
  } else {
    echo "<tr><td>" . "0 available databases" . "</td></tr>";
  }
}

echo "</table>";
?>
