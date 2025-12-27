<?php
$host     = "localhost";
$user     = "root";
$password = "";
$dbname   = "login_db"; 

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>