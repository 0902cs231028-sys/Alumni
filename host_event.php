<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['alumni_id'])) {
    $host_id = (int)$_SESSION['alumni_id'];
    $title = mysqli_real_escape_with_safe($conn, $_POST['title']);
    $desc  = mysqli_real_escape_with_safe($conn, $_POST['description']);
    $date  = $_POST['event_date'];

    $sql = "INSERT INTO events (host_id, title, description, event_date) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $host_id, $title, $desc, $date);
    
    if (mysqli_stmt_execute($stmt)) {
        // Create an Admin Notification automatically
        $msg = "New event hosting request: $title";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, type) VALUES ($host_id, '$msg', 'report')");
        header("Location: profile.php?success=1");
    }
}
