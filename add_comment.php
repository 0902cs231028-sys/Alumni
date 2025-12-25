<?php
declare(strict_types=1);
session_start();

// 1. Environmental Setup & Headers
header('Content-Type: application/json');
require __DIR__ . '/includes/connection.php';

/**
 * Utility for standardized JSON exits
 */
function send_json(bool $success, array $data = [], int $status = 200) {
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $success], $data));
    exit;
}

// 2. Authentication Guard
if (!isset($_SESSION['alumni_id'])) {
    send_json(false, ['error' => 'session_expired', 'message' => 'Please log in to comment.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, ['error' => 'invalid_method'], 405);
}

// 3. Rate Limiting (Extreme Protection)
$user_id = (int)$_SESSION['alumni_id'];
$last_comment_key = "last_comment_time_" . $user_id;

if (isset($_SESSION[$last_comment_key]) && (time() - $_SESSION[$last_comment_key]) < 5) {
    send_json(false, ['error' => 'rate_limit', 'message' => 'Slow down! You can comment again in 5 seconds.'], 429);
}

// 4. Input Validation & Sanitization
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$content = trim($_POST['content'] ?? '');

if (!$post_id || $content === '') {
    send_json(false, ['error' => 'missing_fields', 'message' => 'Comment content cannot be empty.'], 400);
}

if (mb_strlen($content) > 1000) {
    send_json(false, ['error' => 'content_too_long', 'message' => 'Comments must be under 1000 characters.'], 400);
}

try {
    // 5. Atomic Transaction
    mysqli_begin_transaction($conn);

    // A. Insert Comment using Prepared Statement
    $stmt = $conn->prepare("INSERT INTO comments (post_id, alumni_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $post_id, $user_id, $content);
    $stmt->execute();
    $comment_id = $conn->insert_id;
    $stmt->close();

    // B. Automatic Notification (Notify Post Author)
    $auth_stmt = $conn->prepare("SELECT alumni_id, title FROM posts WHERE id = ? LIMIT 1");
    $auth_stmt->bind_param("i", $post_id);
    $auth_stmt->execute();
    $post_data = $auth_stmt->get_result()->fetch_assoc();
    $auth_stmt->close();

    if ($post_data && (int)$post_data['alumni_id'] !== $user_id) {
        $notif_msg = "{$_SESSION['admin_username']} commented on your post: " . mb_substr($post_data['title'], 0, 30) . "...";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'new_comment', NOW())");
        $notif_stmt->bind_param("is", $post_data['alumni_id'], $notif_msg);
        $notif_stmt->execute();
        $notif_stmt->close();
    }

    // C. Fetch Fresh Data for UI update
    $fetch_stmt = $conn->prepare("
        SELECT c.id, c.content, c.created_at, a.name 
        FROM comments c 
        INNER JOIN alumni a ON c.alumni_id = a.id 
        WHERE c.id = ? LIMIT 1
    ");
    $fetch_stmt->bind_param("i", $comment_id);
    $fetch_stmt->execute();
    $new_comment = $fetch_stmt->get_result()->fetch_assoc();
    $fetch_stmt->close();

    mysqli_commit($conn);
    
    // Update rate limit session
    $_SESSION[$last_comment_key] = time();

    send_json(true, ['comment' => $new_comment]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Comment Error: " . $e->getMessage());
    send_json(false, ['error' => 'db_error', 'message' => 'Failed to save comment.'], 500);
}comment.'], 500);
}
