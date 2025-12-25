<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';
require __DIR__ . '/../includes/functions.php';

/**
 * Nexus Administrative Event Controller
 * Features: Soft-Approval, Admin Logging, and Automated Alumni Alerts
 */

// 1. Authorization Guard
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = $_GET['act'] ?? null;
$token  = $_GET['token'] ?? '';

// 2. CSRF Security Verification
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    die("Security Token Mismatch. Action Blocked.");
}

if ($id && in_array($action, ['approve', 'delete'], true)) {
    try {
        $pdo->beginTransaction();

        // Fetch event and host details before action for the notification
        $infoStmt = $pdo->prepare("SELECT title, host_id FROM events WHERE id = ?");
        $infoStmt->execute([$id]);
        $event = $infoStmt->fetch();

        if ($action === 'approve') {
            // Update Event Status
            $stmt = $pdo->prepare("UPDATE events SET is_approved = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            // AUTOMATIC ALERT: Notify the Alumni Host
            $msg = "Success! Your event '{$event['title']}' has been approved and is now live.";
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'event_invite')");
            $notif->execute([$event['host_id'], $msg]);
            
            $logMsg = "Event ID $id approved.";
        } else {
            // Hard Delete Event
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            $logMsg = "Event ID $id deleted/rejected.";
        }

        // 3. Extreme Audit Logging
        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$_SESSION['admin_id'], $action, $logMsg]);

        $pdo->commit();
        $_SESSION['flash_info'] = "Operation successful: " . $logMsg;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($e->getMessage());
        $_SESSION['flash_error'] = "Transaction Failed: System Error.";
    }
}

// Redirect back to the notifications or dashboard
$back = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: " . $back);
exit;
