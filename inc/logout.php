<?php
session_start();

$_SESSION = [];
session_unset();
session_destroy();

header("location:../inc/login.php");
exit;
?>
<!-- <meta http-equiv="refresh" content="1; url=../inc/login.php> -->