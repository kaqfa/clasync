<?php
$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "claroline";

$dbConn = mysql_connect($dbhost,$dbuser,$dbpass);
$db = mysql_select_db($dbname, $dbConn) or die(mysql_error());

