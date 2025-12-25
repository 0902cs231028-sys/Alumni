<?php
// includes/functions.php

/**
 * Generate a secure CSRF token if one doesn't exist
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Helper to log admin actions into the new admin_logs table
 */
function log_admin_action($pdo, $admin_id, $action, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$admin_id, $action, $details]);
    } catch (Exception $e) {
        error_log("Logging failed: " . $e->getMessage());
    }
}
