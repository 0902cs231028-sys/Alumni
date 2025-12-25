<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/../includes/connection.php';
require __DIR__ . '/../includes/security_helper.php';

/**
 * NEXUS SUPREME POST MODERATOR v3.0
 * Features: Author ID Verification, Ghost Approval Protection, & IP Audit
 */

if (empty($_SESSION['admin_id'])) {
    exit("ACCESS DENIED: Administrative session required.");
}

// HYBRID INPUT DETECTION
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$act = $_GET['act'] ?? $_POST['act'] ?? '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$reason = $_POST['reason'] ?? 'Content rejected due to community policy.';

// CSRF TOKEN SYNC
if (!verify_csrf_token($token)) {
    $_SESSION['flash_error'] = "SECURITY ALERT: Token Validation Failed.";
    header("Location: pending_posts.php");
    exit;
}

try {
    $conn->begin_transaction();

    // FETCH TARGET DATA WITH ROW LOCKING
    $fetch = $conn->prepare("SELECT p.user_id, p.title, p.approved, a.email FROM posts p JOIN alumni a ON p.user_id = a.id WHERE p.id = ? FOR UPDATE");
    $fetch->bind_param("i", $id);
    $fetch->execute();
    $post = $fetch->get_result()->fetch_assoc();

    if (!$post) throw new Exception("Post record #$id not found.");

    if ($act === 'approve') {
        if ($post['approved'] == 1) throw new Exception("Post is already synchronized as 'Active'.");

        // 1. UPDATE STATUS
        $upd = $conn->prepare("UPDATE posts SET approved = 1, approved_by = ? WHERE id = ?");
        $upd->bind_param("ii", $_SESSION['admin_id'], $id);
        $upd->execute();

        // 2. DISPATCH NOTIFICATION
        $msg = "ALERT: Your post '" . htmlspecialchars($post['title']) . "' has been approved and is now live!";
        $notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'post_approval')");
        $notif->bind_param("is", $post['user_id'], $msg);
        $notif->execute();

        $logType = 'POST_APPROVAL';
        $flash = "Post #$id approved successfully.";

    } elseif ($act === 'delete') {
        // 1. EXECUTE PURGE
        $del = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();

        // 2. DISPATCH REJECTION ALERT
        $msg = "NOTICE: Your post '" . htmlspecialchars($post['title']) . "' was rejected. Reason: $reason";
        $notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'report')");
        $notif->bind_param("is", $post['user_id'], $msg);
        $notif->execute();

        $logType = 'POST_DELETION';
        $flash = "Post #$id has been purged and author notified.";
    }

    // 3. LOG TO AUDIT TRAIL
    $ip = $_SERVER['REMOTE_ADDR'];
    $details = "Action: $act | Target: Post #$id | Admin: {$_SESSION['admin_id']}";
    $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action_type, details, ip_address) VALUES (?, ?, ?, ?)");
    $log->bind_param("isss", $_SESSION['admin_id'], $logType, $details, $ip);
    $log->execute();

    $conn->commit();
    $_SESSION['flash_info'] = $flash;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_error'] = "FATAL ENGINE ERROR: " . $e->getMessage();
}

header("Location: pending_posts.php");
exit;
