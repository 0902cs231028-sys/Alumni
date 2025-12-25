<?php
declare(strict_types=1);
session_start();

// 1. SYSTEM INITIALIZATION
require __DIR__ . '/../includes/connection.php';
require __DIR__ . '/../includes/security_helper.php';

/** * SUPREME COMMENT PURGE ENGINE
 * Features: Auto-Method Detection, Transactional Integrity, & Audit Persistence
 */

// 2. SUPREME GUARD
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit("CRITICAL: Unauthorized Access Attempt Logged.");
}

// 3. HYBRID PARAMETER CAPTURE
// This fixes the "Nothing Happens" issue by accepting GET links and POST forms
$comment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$token = $_GET['token'] ?? $_POST['csrf_token'] ?? '';

// 4. SECURITY VERIFICATION
if (!verify_csrf_token($token)) {
    $_SESSION['flash_error'] = 'SECURITY ERROR: Invalid or Expired Token.';
    header("Location: manage_posts.php");
    exit;
}

if (!$comment_id) {
    $_SESSION['flash_error'] = 'ERROR: No Comment ID target specified.';
    header("Location: manage_posts.php");
    exit;
}

try {
    // 5. ATOMIC OPERATION START
    $conn->begin_transaction();

    // Verify existence before attempt
    $check = $conn->prepare("SELECT id FROM comments WHERE id = ? LIMIT 1");
    $check->bind_param("i", $comment_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        throw new Exception("Target comment #$comment_id does not exist in the repository.");
    }

    // 6. EXECUTE HARD PURGE
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    
    if ($stmt->execute()) {
        // 7. EXTREME LOGGING
        $ip = $_SERVER['REMOTE_ADDR'];
        $details = "Admin {$_SESSION['admin_id']} purged Comment ID #$comment_id from the database.";
        $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action_type, details, ip_address) VALUES (?, 'DELETE_COMMENT', ?, ?)");
        $log->bind_param("iss", $_SESSION['admin_id'], $details, $ip);
        $log->execute();

        $conn->commit();
        $_SESSION['flash_success'] = "SUCCESS: Comment #$comment_id has been permanently removed.";
    } else {
        throw new Exception("Database Engine rejected the deletion request.");
    }

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_error'] = "SYSTEM FAILURE: " . $e->getMessage();
}

// 8. FORCED REDIRECTION
header("Location: manage_posts.php");
exit;
