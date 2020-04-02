<?php
session_start();

$session_id = session_id();

$db_server  = $_SESSION["db_server"];
$db_user    = $_SESSION["db_user"];
$db_pass    = $_SESSION["db_pass"];
$hostname   = $_SESSION["hostname"];

$db_conn    = mysqli_connect("p:" . $db_server, $db_user, $db_pass);

if(!$db_conn){
  $_SESSION["warn"] = "Database connection lost! Please login again.";
  header("Location: index.php");
}

// receive form from information.php
$db_delete_all  = $_POST["db_delete_all"];
?>

<!DOCTYPE html>
<html lang="en">

<!-- CONFIRM.php -->

  <head>
    
    <meta charset="utf-8">
    <title><?php echo $hostname ?> monitoring server</title>
    
    <!-- CSS style -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Archivo:600|Roboto:400">
    <link rel="stylesheet" type="text/css" href="inc/style.css">
  
  </head>
  <body onload="updateAll()">
    <div class="div_main">
    
      <!-- HEADER -->
      <div class="div_h1">
        <a href="<?php echo $_SERVER['HTTP_REFERER']?>">
        <h1>Raspberry Pi <?php if (isset($hostname)){ echo "(" . $hostname . ")"; } ?></h1>
        <h3>monitoring server visualization interface</h3></a>
        <hr>
      </div>

      <!-- CONFIRM -->
      <div class="div_confirm">
      <h2>Are you sure you want to delete all data from database <?php echo $db_delete_all ?>?</h2>
        <div class="div_content">
          
        This step might take a while (depending on database size)
        <p class="warning">All records of device movement will be lost!<p>
        
          <div class="div_subcontent">
            <form method="post" action="inc/db_delete.php">
              <br><button type="submit" name="db_delete_all" value="<?php echo $db_delete_all ?>" style="color:red">Confirm</button>
            </form>
          </div>

          <div class="div_subcontent">
            <form action="<?php echo $_SERVER['HTTP_REFERER'] ?>">
              <br><button type="submit">Cancel</button>
            </form>
          </div>

        </div>
      </div>
      
      <!-- FOOTER -->
      <div class="div_foot">
        <hr>
        <p>Samuel Petráš (203317) - Bakalárska práca - VUT FEKT - 2020</p>
      </div>
    </div>
  </body>
</html>
