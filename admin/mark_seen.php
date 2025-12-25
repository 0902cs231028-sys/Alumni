<?php
declare(strict_types=1);
session_start();

// Set JSON header immediately for API-style responses
header('Content-Type: application/json');

require __DIR__ . '/../includes/connection.php';

/**
 * Helper to return standardized responses
 */
function sendResponse(bool $success, string $message, int $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// 1. Strict Security Guard
if (empty($_SESSION['admin_id'])) {
    sendResponse(false, 'Unauthorized session.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method Not Allowed.', 405);
}

// 2. Hardened CSRF Verification
$token = $_POST['token'] ?? '';
if (empty($token) || !hash_equals($_SESSION['notif_action_token'] ?? '', $token)) {
    sendResponse(false, 'Security token mismatch. Refresh page.', 403);
}

// 3. Input Normalization (Supports single ID or Array for bulk actions)
$rawIds = $_POST['id'] ?? null;
$ids    = is_array($rawIds) ? array_map('intval', $rawIds) : [ (int)$rawIds ];
$ids    = array_filter($ids); // Remove zeroes

$action = $_POST['act'] ?? 'seen';

if (empty($ids)) {
    sendResponse(false, 'No valid notification IDs provided.', 400);
}

try {
    // 4. Database Transaction for Atomic Updates
    mysqli_begin_transaction($conn);

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));

    switch ($action) {
        case 'seen':
            $sql = "UPDATE notifications SET seen = 1, seen_at = NOW() WHERE id IN ($placeholders)";
            $statusMsg = count($ids) > 1 ? "Notifications updated." : "Report marked as seen.";
            break;

        case 'delete':
            $sql = "DELETE FROM notifications WHERE id IN ($placeholders)";
            $statusMsg = "Notifications removed.";
            break;

        default:
            throw new Exception("Unsupported action: $action");
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception("Statement preparation failed.");

    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    // 5. Intelligent Success Handling
    if (isset($_POST['ajax'])) {
        sendResponse(true, $statusMsg);
    } else {
        $_SESSION['flash_info'] = $statusMsg;
        header('Location: notifications.php');
        exit;
    }

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Notification Error: " . $e->getMessage());
    sendResponse(false, "System error occurred during update.", 500);
}
    sendResponse(false, "System error occurred during update.", 500);
}
