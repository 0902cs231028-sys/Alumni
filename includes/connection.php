<?php
// includes/connection.php

$host = "local"; 
$user = "Im";            
$pass = "sample";             
$db   = "MariaDb";     

// 1. Original MySQLi Connection (Maintains existing scripts)
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// 2. New PDO Connection (Required for Extreme Level scripts)
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // On InfinityFree, we hide details to prevent security leaks
    die("Internal Server Error: Database sub-system failure.");
}
?>
