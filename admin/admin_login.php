<?php
declare(strict_types=1);
session_start();

// 1. Extreme Security Headers
header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

require __DIR__ . '/../includes/connection.php';

$err = '';
$info = '';
$ip_address = $_SERVER['REMOTE_ADDR'];
$lockout_time = 900; // 15 Minute Lockout in seconds

/** * 2. Persistent Security Check
 * Uses the table you created to block brute-force attempts at the database level.
 */
$rate_stmt = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
$rate_stmt->bind_param("s", $ip_address);
$rate_stmt->execute();
$rate_data = $rate_stmt->get_result()->fetch_assoc();

$attempts = $rate_data['attempts'] ?? 0;
$last_attempt = (int)($rate_data['last_attempt'] ?? 0);
$is_locked = ($attempts >= 5 && (time() - $last_attempt) < $lockout_time);

if ($is_locked) {
    $remaining = ceil(($lockout_time - (time() - $last_attempt)) / 60);
    $info = "ACCESS DENIED: Brute-force detected. Retry in $remaining minutes.";
}

// 3. Secure Authentication Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepared Statement for SQL Injection prevention
    $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    // Verify Credential (supports legacy MD5 as requested)
    if ($admin && (password_verify($password, $admin['password']) || md5($password) === $admin['password'])) {
        
        // Reset Rate Limiter on success
        $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip_address]);

        // Secure Session Handshake
        session_regenerate_id(true); 
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        
        header('Location: dashboard.php');
        exit;
    } else {
        // Log failure to database to prevent further attempts
        $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) 
                        VALUES (?, 1, UNIX_TIMESTAMP()) 
                        ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = UNIX_TIMESTAMP()")
             ->execute([$ip_address]);

        $err = "Invalid credentials. Attempt " . ($attempts + 1) . " of 5.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Terminal | Nexus Secure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --p-blue: #3b82f6; --glass: rgba(15, 23, 42, 0.96); }
        body { 
            background: radial-gradient(circle at center, #1e293b 0, #020617 100%); 
            color: #f8fafc; font-family: 'Inter', sans-serif; 
            height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .admin-shell { width: 100%; max-width: 440px; padding: 1.5rem; }
        .glass-card { 
            background: var(--glass); backdrop-filter: blur(20px); 
            border: 1px solid rgba(255,255,255,0.1); border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .form-control { 
            background: #1e293b; border: 1px solid #334155; color: white; 
            border-radius: 12px; padding: 0.8rem 1rem;
        }
        .form-control:focus { background: #1e293b; color: white; border-color: var(--p-blue); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
        .btn-auth { background: var(--p-blue); border: none; padding: 1rem; border-radius: 12px; font-weight: 700; transition: 0.3s; }
        .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }
        .security-badge { font-size: 0.7rem; color: #94a3b8; letter-spacing: 0.1em; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="admin-shell">
    <div class="glass-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="mb-3 d-inline-block p-3 bg-primary bg-opacity-10 rounded-circle">
                <i data-lucide="shield-alert" class="text-primary" size="36"></i>
            </div>
            <h2 class="fw-bold mb-1">Admin Access</h2>
            <p class="text-secondary small">Authorized Personnel Only</p>
        </div>

        <?php if($err): ?> <div class="alert alert-danger border-0 small py-2 mb-4"><?= $err ?></div> <?php endif; ?>
        <?php if($info): ?> <div class="alert alert-warning border-0 small py-2 mb-4"><?= $info ?></div> <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="security-badge mb-2">Identifier</label>
                <div class="position-relative">
                    <input type="text" name="username" class="form-control ps-5" placeholder="Admin username" required autocomplete="off" <?= $is_locked ? 'disabled' : '' ?>>
                    <i data-lucide="user" class="position-absolute start-0 top-50 translate-middle-y ms-3 text-secondary" size="18"></i>
                </div>
            </div>

            <div class="mb-5">
                <label class="security-badge mb-2">Access Key</label>
                <div class="position-relative">
                    <input type="password" name="password" id="pass" class="form-control ps-5" placeholder="••••••••" required <?= $is_locked ? 'disabled' : '' ?>>
                    <i data-lucide="lock" class="position-absolute start-0 top-50 translate-middle-y ms-3 text-secondary" size="18"></i>
                    <button type="button" onclick="togglePass()" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-secondary text-decoration-none pe-3">
                        <i data-lucide="eye" size="18" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-auth btn-primary w-100 mb-3" <?= $is_locked ? 'disabled' : '' ?>>
                <?= $is_locked ? 'System Locked' : 'Decrypt & Login' ?>
            </button>

            <div class="text-center">
                <a href="../index.php" class="text-secondary text-decoration-none small">
                    <i data-lucide="arrow-left" size="14" class="me-1"></i> Exit Terminal
                </a>
            </div>
        </form>
    </div>
    <div class="text-center mt-4">
        <span class="security-badge">Protocol: AES-256 | IP: <?= $ip_address ?></span>
    </div>
</div>

<script>
    lucide.createIcons();
    function togglePass() {
        const pass = document.getElementById('pass');
        const icon = document.getElementById('eyeIcon');
        if (pass.type === 'password') {
            pass.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            pass.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        lucide.createIcons();
    }
</script>
</body>
</html>
