<?php
// DB_DELETE.php
// PHP code to handle MySQL database drop commands (called from information.php) 

// get session variables
session_start();
$db_server          = $_SESSION["db_server"];
$db_user            = $_SESSION["db_user"];
$db_pass            = $_SESSION["db_pass"];

// RECEIVE INFORMATION FORM
// both return name of database to delete from (eg. rpi_mon_node_1)
$db_delete      = $_POST["db_delete"];
$db_delete_all  = $_POST["db_delete_all"];

// drop entries older than # 
//if($db_delete != ""){};

// drop all entries
if($db_delete_all != ""){
  // DB conn with specified source
  $db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $db_delete_all);
  $db_q      = "DELETE FROM AccessPoints;
                DELETE FROM Clients;
                DELETE FROM Bluetooth;";
  mysqli_multi_query($db_conn_s, $db_q);
}

// jump back to visual.php
header("Location: " . $_SERVER['HTTP_REFERER']);
  // HTTP_REFERER might be insecure!
  // consider exchanging it for format:
  // visual.php?timeperiod=15&timeperiod_format=MINUTE...
?>
