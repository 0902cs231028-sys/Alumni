<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

// 1. Environmental Security
$ip_address = $_SERVER['REMOTE_ADDR'];
$errorMsg = '';
$email = '';

// 2. Persistent Rate Limiting Logic
$lockout_time = 15 * 60; // 15 Minute Lockout
$check_rate = mysqli_prepare($conn, "SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
mysqli_stmt_bind_param($check_rate, "s", $ip_address);
mysqli_stmt_execute($check_rate);
$rate_res = mysqli_stmt_get_result($check_rate);
$rate_data = mysqli_fetch_assoc($rate_res);

$attempts = $rate_data['attempts'] ?? 0;
$last_attempt = $rate_data['last_attempt'] ?? 0;
$locked = ($attempts >= 5 && (time() - $last_attempt) < $lockout_time);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    // 3. CSRF Verification
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("Security token mismatch. Action denied.");
    }

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $errorMsg = 'Credentials required for access.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Provided email format is invalid.';
    } else {
        // 4. Secure Authentication
        $sql  = "SELECT id, email, password FROM alumni WHERE email = ? AND approved = 1 LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($res)) {
                if (password_verify($pass, $row['password'])) { // Modern hashing check
                    
                    // Reset rate limiting on success
                    mysqli_query($conn, "DELETE FROM login_attempts WHERE ip_address = '$ip_address'");

                    // Extreme Session Security
                    session_regenerate_id(true); 
                    $_SESSION['alumni_id'] = (int)$row['id'];
                    header("Location: profile.php");
                    exit;
                }
            }
            
            // 5. Handle Failed Attempt (Update DB)
            mysqli_query($conn, "INSERT INTO login_attempts (ip_address, attempts, last_attempt) 
                                VALUES ('$ip_address', 1, " . time() . ") 
                                ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = " . time());
            
            $errorMsg = 'Authentication failed. Please check credentials or approval status.';
        }
    }
}

// Generate CSRF Token for next request
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alumni Portal | Secure Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --p-blue: #3b82f6; --glass: rgba(15, 23, 42, 0.96); }
        body { 
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            font-family: 'Inter', sans-serif; color: #f8fafc;
        }
        .login-glass {
            border-radius: 24px; background: var(--glass);
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            width: 100%; max-width: 440px;
        }
        .form-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; color: #9ca3af; font-weight: 700; }
        .form-control { 
            background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(148, 163, 184, 0.2); 
            color: white; border-radius: 12px; padding: 0.8rem 1rem;
        }
        .form-control:focus { 
            background: rgba(255, 255, 255, 0.08); border-color: var(--p-blue); 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2); color: white;
        }
        .btn-primary { background: var(--p-blue); border: none; padding: 0.9rem; border-radius: 12px; font-weight: 700; }
        .btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-primary:disabled { background: #475569; transform: none; }
    </style>
</head>
<body>

<div class="login-glass p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="mb-3 d-inline-block p-3 bg-primary bg-opacity-10 rounded-circle">
            <i data-lucide="shield-check" class="text-primary" size="32"></i>
        </div>
        <h2 class="fw-bold mb-1">Alumni Access</h2>
        <p class="text-secondary small">Authenticate to enter the professional network</p>
    </div>

    <?php if ($errorMsg): ?>
        <div class="alert alert-danger border-0 small py-2 mb-4"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <?php if ($locked): ?>
        <div class="alert alert-warning border-0 small py-2 mb-4">
            Security Lock: Too many failed attempts. Try again in <?= ceil(($lockout_time - (time() - $last_attempt)) / 60) ?> minutes.
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="mb-4">
            <label class="form-label">IDENTITY EMAIL</label>
            <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($email) ?>" 
                   placeholder="you@university.edu" required <?= $locked ? 'disabled' : '' ?>>
        </div>

        <div class="mb-3">
            <label class="form-label">SECURITY KEY</label>
            <div class="position-relative">
                <input class="form-control" type="password" name="password" id="passInput" 
                       placeholder="Enter password" required <?= $locked ? 'disabled' : '' ?>>
                <button type="button" id="togglePass" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-secondary text-decoration-none pe-3">
                    <i data-lucide="eye" size="18"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="rememberMe">
                <label class="form-check-label small text-secondary" for="rememberMe">Keep me logged in</label>
            </div>
            <a href="register.php" class="small text-primary text-decoration-none fw-bold">Join Network</a>
        </div>

        <button class="btn btn-primary w-100 mb-3" type="submit" <?= $locked ? 'disabled' : '' ?>>
            Secure Login
        </button>
        
        <div class="text-center">
            <a href="index.php" class="text-secondary text-decoration-none small">← Return to Terminal</a>
        </div>
    </form>
</div>

<script>
    lucide.createIcons();
    const togglePass = document.getElementById('togglePass');
    const passInput = document.getElementById('passInput');
    
    togglePass.addEventListener('click', () => {
        const isPass = passInput.type === 'password';
        passInput.type = isPass ? 'text' : 'password';
        togglePass.innerHTML = `<i data-lucide="${isPass ? 'eye-off' : 'eye'}" size="18"></i>`;
        lucide.createIcons();
    });
</script>
</body>
</html>
