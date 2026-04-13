<<<<<<< HEAD
<?php
$host = "localhost";
$dbname = "indiclex_db_d";
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
=======
<?php
$host = "localhost";
$dbname = "indiclex_db_d";
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
>>>>>>> 50c55f8a008be9bcda28bc86fc01a2fe49e49c16
