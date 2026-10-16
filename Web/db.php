<?php
// db.php
$hostname = "127.0.0.1";
$username = "root";
$pwd      = "";
$db       = "teambox";

$conn = mysqli_connect($hostname, $username, $pwd, $db)
or die("Database connection failed: " . mysqli_connect_error());

mysqli_set_charset($conn, "utf8mb4");
?>