<?php

require_once "../config/security.php";

$_SESSION = [];

session_destroy();

header("Location: login.php");
exit;

?>