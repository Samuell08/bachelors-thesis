<?php
// LOGIN.php
// PHP code to be expanded in index.php

$loginok="1";

// variables

// receive login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $db_pass = filter_var($_POST["db_pass"], FILTER_SANITIZE_STRING);

  // create connection with MySQL DB
  $db_server  = "localhost";
  $db_user    = "mon";

  $db_conn = mysqli_connect("p:" . $db_server, $db_user, $db_pass);

  // check DB onnection
  if (!$db_conn) {
    echo "<p class=\"warning\">Database connection failed (password might be incorrect)</p>";
  } else {
    // redirect to visual interface if connection successfull
    $_SESSION["db_server"]  = $db_server;
    $_SESSION["db_user"]    = $db_user;
    $_SESSION["db_pass"]    = $db_pass;
    header("Location: range_history.php");
  }
}
?>
