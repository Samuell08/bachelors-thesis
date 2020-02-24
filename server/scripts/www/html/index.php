<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<!-- INDEX.php -->

  <head>
    
    <meta charset="utf-8">
    <title>RPi monitoring server</title>
    
    <!-- CSS style -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Archivo:500|Open+Sans:300,700">
    <link rel="stylesheet" type="text/css" href="inc/style.css">
  
  </head>
  <body>
    <div class="div_main">
    
      <!-- HEADER -->
      <div class="div_h1">
        <a href="<?php echo $_SERVER['PHP_SELF']?>"><h1>Raspberry Pi - monitoring server visualization interface</h1></a><hr>
      </div>
    
      <!-- LOGIN -->
      <div class="div_login">

        Enter password for MySQL database user 'mon'<br><br>
        <form method=post action="<?php echo $_SERVER['PHP_SELF']?>">
          <input type="password" name="db_pass">
          <input type="submit" value="login">
        </form>

          <?php
            $loginok="0";
            include 'inc/login.php';
            if(!$loginok == "1") {
              echo "<p class=\"p_incl_ERROR\">FATAL ERROR: failed to load login.php - page is not be able to login to database</p>";
            }
          ?>

      </div>

      <!-- FOOTER -->
      <div class="div_foot">
        <hr><p>Samuel Petráš (203317) - Bakalárska práca - VUT FEKT - 2020</p>
      </div>
    </div>
  </body>
</html>
