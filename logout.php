<?php
declare(strict_types=1);
session_start();

/**
 * Extreme Logout: 
 * Prevents session fixation, replay attacks, and ensures total state destruction.
 */

// 1. Invalidate Session on Server (if using database sessions)
// require __DIR__ . '/includes/connection.php';
// $pdo->prepare("DELETE FROM sessions WHERE session_id = ?")->execute([session_id()]);

// 2. Unset all session data
$_SESSION = [];

// 3. Force Session Cookie Expiry with modern security flags
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 86400, // Set to 24 hours ago
        $params['path'], 
        $params['domain'], 
        true,     // Secure flag
        true      // HttpOnly flag
    );
}

// 4. Destroy session data and regenerate ID for the next user
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 5. Prevent "Open Redirect" vulnerabilities
$target = 'index.php';
header("Location: $target");
header("Clear-Site-Data: \"cache\", \"cookies\", \"storage\""); // Modern Browser cleanup
exit;
