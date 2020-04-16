<?php
switch ($_SESSION["menu_current"]) {
  case 1:
    $class_1 = "menu_current";
    $class_2 = "menu_other";
    $class_3 = "menu_other";
    break;
  case 2:
    $class_1 = "menu_other";
    $class_2 = "menu_current";
    $class_3 = "menu_other";
    break;
  case 3:
    $class_1 = "menu_other";
    $class_2 = "menu_other";
    $class_3 = "menu_current";
    break;
  default:
    $class_1 = "menu_other";
    $class_2 = "menu_other";
    $class_3 = "menu_other";
    break;
}
echo '<span class=' . $class_1 . '><a href="range_history.php">In Range</a></span> |
      <span class=' . $class_2 . '><a href="move_live.php">Movement</a></span> |
      <span class=' . $class_3 . '><a href="pass_history.php">Passages</a></span>';
?>
