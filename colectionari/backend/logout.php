<?php
session_start();
session_destroy();


header('Location: /colectionari/landing.php');
exit;
?>
