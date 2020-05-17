<?php
session_start();

// read server hostname from file
$f_hostname = fopen('/etc/hostname', 'r');
$hostname = fgets($f_hostname);
fclose($f_hostname);
$hostname = substr($hostname, 0, -1); // delete trailing white char
$_SESSION["hostname"] = $hostname;
?>
<!DOCTYPE html>
<html lang="en">

<!-- INDEX.php -->

  <head>
    
    <meta charset="utf-8">
    <title><?php echo $hostname ?> monitoring server</title>
    
    <!-- CSS style -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Archivo:500|Roboto:400">
    <link rel="stylesheet" type="text/css" href="inc/common/style.css">
  
  </head>
  <body>
    <div class="div_main">
    
      <!-- HEADER -->
      <div class="div_h1">
        <?php include 'inc/common/header.php' ?>
      </div>
    
      <!-- LOGIN -->
      <div class="div_login">

        Enter password for MySQL database user 'mon'<br><br>
        <form method=post action="<?php echo $_SERVER['PHP_SELF']?>">
          <input type="password" name="db_pass">
          <button type="submit">Login</button>
        </form>

        <?php
          $loginok="0";
          include 'inc/common/login.php';
          if(!$loginok == "1") {
            echo "<p class=\"error\">FATAL ERROR: failed to load login script - page cannot login to database</p>";
          }

          // warning redirect handling
          if (!($_SESSION["warn"] == NULL)){
            echo "<p class=\"warning\">" . $_SESSION["warn"] . "</p>";
          }
          $_SESSION["warn"] = NULL;
        ?>

      </div>

      <!-- FOOTER -->
      <div class="div_foot">
        <?php include 'inc/common/footer.php' ?>
      </div>
    </div>
  </body>
</html>
