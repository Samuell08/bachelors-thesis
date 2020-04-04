<?php
// INFORMATION.php
// PHP code to fill Information div in live.php and history.php via AJAX

// get session variables
session_start();
$db_server  = $_SESSION["db_server"];
$db_user    = $_SESSION["db_user"];
$db_pass    = $_SESSION["db_pass"];

// local DB conn
$db_conn_s = mysqli_connect($db_server, $db_user, $db_pass);

// DYNAMIC INFORMATION PART
// Database Size(s)
echo "Database Sizes(s)<br>";
// form sending 'delete 'buttons to confirmation PHP script
echo "<form method=\"post\" action=\"confirm.php\">";
  echo "<table class=\"info\">";

  if (!$db_conn_s) {
    echo "<tr><td>" . "<p class=\"error\">Database not connected!</p>" . "</td></tr>";
  } else {
    // information about database size in MB
    $db_q      = "SELECT table_schema \"DB_name\",
                  ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) \"DB_size_MB\"
                  FROM information_schema.tables
                  WHERE table_schema LIKE 'rpi_mon_%'
                  GROUP BY table_schema;";
    $db_result = mysqli_query($db_conn_s, $db_q);
  
    if (mysqli_num_rows($db_result) > 0) {

      // table header
      echo "<tr class=\"info\">";
      echo "<th class=\"info\">" . "Database name" . "</th>";
      echo "<th class=\"info\">" . "Database size (MB)" . "</th>";
      echo "<th class=\"info\">" . "Import period" . "</th>";
      echo "<th class=\"info\">" . "Delete all entries" . "</th>";
      echo "</tr>";

      // loop all returned rows
      while ($db_row = mysqli_fetch_assoc($db_result)) {

        // read Import period from var file
        $filename = $db_row["DB_name"] . "_server_import";
        $var_import_period = "unknown";
        if (file_exists("../var/" . $filename)) {
          $f_import_period = fopen("../var/" . $filename, "r");
          $var_import_period = fgets($f_import_period);
          fclose($f_import_period);
        }

        // fill 1 row
        echo "<tr class=\"info\">";
        echo "<td>" . $db_row["DB_name"] . "</td>";
        echo "<td>" . $db_row["DB_size_MB"] . "</td>";
        echo "<td>" . $var_import_period . "</td>";
        echo "<td>" . "<button type=\"submit\" name=\"db_delete_all\" value=\"" . $db_row["DB_name"] . "\">" . "Delete All" . "</button>" . "</td>";
        echo "</tr>";

      }
    } else {
      echo "<tr><td>" . "0 available databases" . "</td></tr>";
    }
  }

  echo "</table>";
echo "</form><br>";

// last time refreshed
echo "Last time refreshed: <i>" . date('G:i:s (j.n.Y)') . "</i><br>";

// update Information div button
echo "<button onclick=\"updateInfo()\">Refresh</button>";

// infobox
echo "<p class=\"info_box\">Database size column takes couple of seconds to update after change in size of database.</p>";
?>
