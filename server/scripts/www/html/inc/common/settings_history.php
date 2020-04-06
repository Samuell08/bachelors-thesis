<?php
// DYNAMIC PART OF HISTORY SETTINGS FORM
// Select Source Database(s)
echo "<b>Select Source Database(s)</b><br>";
echo "<table class=\"form\">";

if (!$db_conn) {
  echo "<tr><td>" . "<p class=\"error\">Database not connected!</p>" . "</td></tr>";
} else {
  $db_q = "SHOW DATABASES LIKE 'rpi_mon_%';";
  $db_result = mysqli_query($db_conn, $db_q);
  
  if (mysqli_num_rows($db_result) > 0) {
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      // table row checkbox
      echo "<tr><td>" . "<input type=\"checkbox\" name=\"db_source_history[]\" value=\"" . $db_row["Database (rpi_mon_%)"] . "\"";
        // keep checked?
        foreach ($db_source_history as $key => $val) {
          if ($val == $db_row["Database (rpi_mon_%)"]) { echo "checked"; }
        }
      echo ">" . "</td>";
      // table row name
      echo "<td>" . $db_row["Database (rpi_mon_%)"] . "</td></tr>";
    }
  } else {
    echo "<tr><td>" . "0 available databases" . "</td></tr>";
  }
}

echo "</table>";
?>
