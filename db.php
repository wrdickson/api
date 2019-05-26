<?php

$servername = "198.71.236.68 ";
$username = "lizrms";
$password = "l1zard";
$dbname = "lizrms";

$conn = new mysqli($servername, $username, $password, $dbname);

if($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

?>
