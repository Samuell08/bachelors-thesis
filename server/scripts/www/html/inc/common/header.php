<?php
if (isset($_SESSION["hostname"])){ $hostname =  "(" . $_SESSION["hostname"] .")"; };
echo '<a href="' . $_SERVER["PHP_SELF"] . '">
      <h1>Raspberry Pi ' . $hostname . '</h1>
      <h3>monitoring server visualization interface</h3></a>
      <hr>';
?>
