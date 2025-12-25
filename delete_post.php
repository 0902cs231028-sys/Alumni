<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/includes/connection.php';

/**
 * Helper to return JSON if requested via AJAX
 */
function exit_response(bool $success, string $message, string $redirect) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    $_SESSION[$success ? 'flash_success' : 'flash_error'] = $message;
    header("Location: $redirect");
    exit;
}

// 1. Extreme Security Guard: Enforce POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: posts.php');
    exit;
}

// 2. CSRF Security Check
$token = $_POST['csrf_token'] ?? '';
if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    exit_response(false, "Security token mismatch. Action denied.", 'posts.php');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$fallback = 'posts.php';

if (!$id) {
    exit_response(false, "Invalid post identifier.", $fallback);
}

try {
    // 3. Authorization Logic (Multi-Role Check)
    $stmt = $conn->prepare("SELECT alumni_id, title FROM posts WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();

    if (!$post) {
        exit_response(false, "Post not found or already removed.", $fallback);
    }

    $is_admin = isset($_SESSION['admin_id']);
    $is_owner = isset($_SESSION['alumni_id']) && (int)$_SESSION['alumni_id'] === (int)$post['alumni_id'];

    if (!$is_admin && !$is_owner) {
        exit_response(false, "Unauthorized: You do not have permission to delete this post.", $fallback);
    }

    // 4. Atomic Transaction: Soft Delete & Logging
    mysqli_begin_transaction($conn);

    // Soft delete: Mark as deleted rather than hard-purging
    // Note: Requires a 'deleted_at' DATETIME column in your 'posts' table
    $del_stmt = $conn->prepare("UPDATE posts SET deleted_at = NOW(), deleted_by_id = ?, deleted_by_role = ? WHERE id = ?");
    
    $actor_id   = $is_admin ? $_SESSION['admin_id'] : $_SESSION['alumni_id'];
    $actor_role = $is_admin ? 'ADMIN' : 'ALUMNI';
    
    $del_stmt->bind_param("isi", $actor_id, $actor_role, $id);
    $del_stmt->execute();

    // 5. Activity Logging (Audit Trail)
    $log_msg = "$actor_role (ID: $actor_id) deleted post '$post[title]' (ID: $id)";
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, 'DELETE_POST', ?, NOW())");
    $log_stmt->bind_param("is", $actor_id, $log_msg);
    $log_stmt->execute();

    mysqli_commit($conn);

    exit_response(true, "Post successfully removed.", $fallback);

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Deletion Error: " . $e->getMessage());
    exit_response(false, "A system error occurred during the deletion process.", $fallback);
}
    processprocess.", $fallback);
}
