<?php

$servername = "localhost"; // O ang iyong database server name
$username = "your_db_username"; // Ang iyong database username
$password = "your_db_password"; // Ang iyong database password
$dbname = "your_db_name"; // Ang pangalan ng iyong database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>