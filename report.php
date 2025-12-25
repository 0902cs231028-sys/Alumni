<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/includes/connection.php';

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok'      => false,
        'message' => 'Only POST allowed'
    ]);
    exit;
}

// Require logged-in alumni
if (empty($_SESSION['alumni_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok'      => false,
        'message' => 'Please log in to report'
    ]);
    exit;
}

$type      = $_POST['type']      ?? '';
$target_id = isset($_POST['target_id']) ? (int) $_POST['target_id'] : 0;
$reporter  = (int) $_SESSION['alumni_id'];

// Basic validation
if (!in_array($type, ['post', 'comment'], true) || $target_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok'      => false,
        'message' => 'Invalid report data'
    ]);
    exit;
}

// Prevent duplicate reports from same user
$checkSql  = "SELECT id FROM notifications WHERE type = ? AND target_id = ? AND reporter_id = ? LIMIT 1";
$insertSql = "INSERT INTO notifications (type, target_id, reporter_id) VALUES (?, ?, ?)";

if ($stmt = mysqli_prepare($conn, $checkSql)) {
    mysqli_stmt_bind_param($stmt, "sii", $type, $target_id, $reporter);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        // Already reported
        echo json_encode([
            'ok'      => true,
            'message' => 'Already reported'
        ]);
        mysqli_stmt_close($stmt);
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Insert new notification (prepared statement to avoid SQL injection)
if ($stmt = mysqli_prepare($conn, $insertSql)) {
    mysqli_stmt_bind_param($stmt, "sii", $type, $target_id, $reporter);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode([
            'ok'      => false,
            'message' => 'Could not save report'
        ]);
        mysqli_stmt_close($stmt);
        exit;
    }

    mysqli_stmt_close($stmt);
    echo json_encode([
        'ok'      => true,
        'message' => 'Report submitted'
    ]);
    exit;
}

http_response_code(500);
echo json_encode([
    'ok'      => false,
    'message' => 'Server error'
]);