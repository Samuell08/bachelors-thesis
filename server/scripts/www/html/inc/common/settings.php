<?php

$input_name = $_SESSION["common_settings_input_name"];
$input_type = $_SESSION["common_settings_input_type"];
$db_source = json_decode($_SESSION["common_settings_db_source"]);

// DYNAMIC PART OF HISTORY SETTINGS FORM
echo "<table class=\"form\">";

if (!$db_conn) {
  echo "<tr><td>" . "<p class=\"error\">Database not connected!</p>" . "</td></tr>";
} else {
  $db_q = "SHOW DATABASES LIKE 'rpi_mon_%';";
  $db_result = mysqli_query($db_conn, $db_q);
  
  if (mysqli_num_rows($db_result) > 0) {
    while ($db_row = mysqli_fetch_assoc($db_result)) {
      // table row checkbox
      echo "<tr><td>" . "<input type=\"" . $input_type ."\" name=$input_name value=\"" . $db_row["Database (rpi_mon_%)"] . "\"";
        // keep checked?
        if (is_scalar($db_source)) {
          if ($db_source == $db_row["Database (rpi_mon_%)"]) { echo "checked"; }
        } else {
          foreach ($db_source as $key => $val) {
            if ($val == $db_row["Database (rpi_mon_%)"]) { echo "checked"; }
          }
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
