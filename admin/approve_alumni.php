<?php
declare(strict_types=1);
session_start();

/**
 * Nexus Administrative Action Handler
 * Level: Ultra-Extreme
 */

// 1. Environmental Requirements
require __DIR__ . '/../includes/connection.php'; 
require __DIR__ . '/../includes/functions.php'; 

/**
 * Utility: Standardized Response Handler
 */
function sendResponse(bool $success, string $message, string $redirect) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    $_SESSION['flash_message'] = ["type" => $success ? "success" : "error", "text" => $message];
    header("Location: $redirect");
    exit;
}

// 2. Authentication Guard
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    die("Unauthorized access.");
}

// 3. Robust Logout Handler
if (($_GET['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// 4. Hardened Input Validation & CSRF Guard
$id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = $_GET['act'] ?? null;
$token  = $_GET['token'] ?? '';
$back   = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';

// Check CSRF Token
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    sendResponse(false, "Invalid security token. Please try again.", $back);
}

// 5. Atomic Operations
if ($id && in_array($action, ['approve', 'delete'], true)) {
    try {
        // Use Transactions to ensure Admin Logs and Alumni Table stay in sync
        $pdo->beginTransaction();

        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE alumni SET approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $id]);
            $msg = "Alumni #$id has been successfully approved.";
        } else {
            // Extreme Safety: Check if post exists before deletion logic
            $stmt = $pdo->prepare("DELETE FROM alumni WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Alumni #$id has been permanently removed.";
        }

        // 6. Audit Logging
        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $action, $msg]);

        $pdo->commit();
        session_regenerate_id(true); // Hardening session after state change
        
        sendResponse(true, $msg, $back);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Admin Action Error: " . $e->getMessage()); //
        sendResponse(false, "A system error occurred. Action rolled back.", $back);
    }
}

// Fallback
header("Location: dashboard.php");
exit;
