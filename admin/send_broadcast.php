<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['admin_id'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Mass-Insert into the notifications table
    $sql = "INSERT INTO notifications (user_id, message, type) 
            SELECT id, '$msg', 'admin_broadcast' FROM alumni WHERE approved = 1";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: social_hub.php?status=broadcast_sent");
    } else {
        die("Broadcast failure: " . mysqli_error($conn));
    }
    exit;
}
