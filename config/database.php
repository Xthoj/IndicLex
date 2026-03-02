<?php
$host = "localhost";
$dbname = "indiclex_db";
$username = "root";   // new user
$password = "";     // password you just set

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
                    $username, 
                    $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>