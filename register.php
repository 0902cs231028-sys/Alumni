<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

$name = $email = $batch = $branch = '';
$status = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $batch  = trim($_POST['batch'] ?? '');
    $branch = trim($_POST['branch'] ?? '');

    // 1. Extreme Server-Side Validation
    if (empty($name) || empty($email) || empty($pass)) {
        $status = ['type' => 'danger', 'msg' => 'Core fields (Name, Email, Password) are mandatory.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status = ['type' => 'danger', 'msg' => 'The provided email format is invalid.'];
    } elseif (strlen($pass) < 6) {
        $status = ['type' => 'warning', 'msg' => 'Security policy: Password must be at least 6 characters.'];
    } else {
        // 2. Prevent Duplicate Registration
        $check = mysqli_prepare($conn, "SELECT id FROM alumni WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        
        if (mysqli_stmt_num_rows($check) > 0) {
            $status = ['type' => 'warning', 'msg' => 'This email is already associated with an account.'];
            mysqli_stmt_close($check);
        } else {
            mysqli_stmt_close($check);
            
            // 3. Secure Hashing & Insertion
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "INSERT INTO alumni (name, email, password, batch, branch, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hash, $batch, $branch);
                if (mysqli_stmt_execute($stmt)) {
                    $status = ['type' => 'success', 'msg' => 'Account created! Awaiting administrative approval.'];
                    $name = $email = $batch = $branch = ''; // Clear form on success
                } else {
                    $status = ['type' => 'danger', 'msg' => 'Database commit failed. Please try again.'];
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nexus Alumni | Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --accent: #3b82f6; --glass: rgba(15, 23, 42, 0.96); }
        body { 
            min-height: 100vh; display: flex; align-items: center;
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #e5e7eb;
        }
        .glass-card {
            border-radius: 24px; background: var(--glass);
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
        }
        .form-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; color: #9ca3af; font-weight: 700; }
        .form-control { 
            background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(148, 163, 184, 0.2); 
            color: white; border-radius: 12px; padding: 0.75rem 1rem;
        }
        .form-control:focus { 
            background: rgba(255, 255, 255, 0.08); border-color: var(--accent); 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2); color: white;
        }
        .strength-meter { height: 4px; border-radius: 2px; background: #334155; margin-top: 8px; overflow: hidden; }
        .strength-bar { height: 100%; width: 0; transition: all 0.3s ease; }
        .btn-primary { 
            background: var(--accent); border: none; padding: 0.8rem; border-radius: 12px; 
            font-weight: 700; transition: transform 0.2s;
        }
        .btn-primary:hover { transform: translateY(-2px); filter: brightness(1.1); }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center py-5">
    <div class="glass-card p-4 p-md-5 w-100" style="max-width: 500px;">
        <header class="text-center mb-4">
            <div class="d-flex justify-content-center mb-2">
                <i data-lucide="user-plus" class="text-primary" size="40"></i>
            </div>
            <h2 class="fw-bold mb-1">Create Account</h2>
            <p class="text-secondary small">Join the exclusive professional alumni circle</p>
        </header>

        <?php if ($status['msg']): ?>
            <div class="alert alert-<?= $status['type'] ?> border-0 small py-2 mb-4 animate-in">
                <?= htmlspecialchars($status['msg']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">Full Legal Name</label>
                <input class="form-control" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="e.g. John Doe" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Professional Email</label>
                <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="name@example.com" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Secure Password</label>
                <input class="form-control" type="password" name="password" id="passInput" placeholder="Minimum 6 characters" required>
                <div class="strength-meter"><div id="strengthBar" class="strength-bar"></div></div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="form-label">Batch Year</label>
                    <input class="form-control" name="batch" value="<?= htmlspecialchars($batch) ?>" placeholder="e.g. 2024">
                </div>
                <div class="col-6">
                    <label class="form-label">Branch/Major</label>
                    <input class="form-control" name="branch" value="<?= htmlspecialchars($branch) ?>" placeholder="e.g. CSE">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3 fw-bold">Initialize Registration</button>
            <p class="text-center text-secondary small mb-0">
                Already registered? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign In</a>
            </p>
        </header>
    </div>
</div>

<script>
    lucide.createIcons();
    
    // Kinetic Password Strength Logic
    const passInput = document.getElementById('passInput');
    const strengthBar = document.getElementById('strengthBar');

    passInput.addEventListener('input', () => {
        const val = passInput.value;
        let score = 0;
        
        if (val.length >= 6) score += 25;
        if (/[A-Z]/.test(val)) score += 25;
        if (/[0-9]/.test(val)) score += 25;
        if (/[^A-Za-z0-9]/.test(val)) score += 25;

        strengthBar.style.width = score + '%';
        if (score <= 25) strengthBar.style.backgroundColor = '#ef4444';
        else if (score <= 50) strengthBar.style.backgroundColor = '#f59e0b';
        else if (score <= 75) strengthBar.style.backgroundColor = '#3b82f6';
        else strengthBar.style.backgroundColor = '#10b981';
    });
</script>
</body>
</html>
