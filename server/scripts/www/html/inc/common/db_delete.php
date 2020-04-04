<?php
// DB_DELETE.php
// PHP code to handle MySQL database delete buttons (called from information.php) 

// get session variables
session_start();
$db_server  = $_SESSION["db_server"];
$db_user    = $_SESSION["db_user"];
$db_pass    = $_SESSION["db_pass"];

// RECEIVE DELETE FORM
// contains name of database to delete data from (eg. rpi_mon_node_1)
$db_delete_all  = $_POST["db_delete_all"];

// drop all entries
// DB conn with database specified
$db_conn_s = mysqli_connect($db_server, $db_user, $db_pass, $db_delete_all);
$db_q      = "DELETE FROM AccessPoints;
              DELETE FROM Clients;
              DELETE FROM Bluetooth;";
mysqli_multi_query($db_conn_s, $db_q);

// jump back to caller website
echo "<script> window.history.go(-2); </script>"
?>
