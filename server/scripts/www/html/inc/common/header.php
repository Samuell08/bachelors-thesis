<?php
if (isset($_SESSION["hostname"])){ $hostname =  "(" . $_SESSION["hostname"] .")"; };
echo '<a href="' . $_SERVER["PHP_SELF"] . '">
      <h1>Monitoring server processing and visualization interface</h1></a>
      <h3>Raspberry Pi ' . $hostname . '</h3>
      <hr>';
?>
