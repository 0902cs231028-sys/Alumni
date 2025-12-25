<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

/**
 * 1. DYNAMIC METRICS ENGINE
 * Fetches network stats to create social proof for new visitors.
 */
$stats = ['alumni' => 0, 'posts' => 0];
try {
    $res_a = mysqli_query($conn, "SELECT COUNT(*) FROM alumni WHERE approved = 1");
    $stats['alumni'] = mysqli_fetch_row($res_a)[0] ?? 0;
    
    $res_p = mysqli_query($conn, "SELECT COUNT(*) FROM posts WHERE approved = 1");
    $stats['posts'] = mysqli_fetch_row($res_p)[0] ?? 0;
} catch (Exception $e) {
    // Graceful degradation if tables are empty
}

$isAlumni = isset($_SESSION['alumni_id']);
$isAdmin  = isset($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nexus | Global Alumni Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --accent: #3b82f6; --glass: rgba(15, 23, 42, 0.94); }
        body { 
            min-height: 100vh; display: flex; align-items: center;
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            color: #f8fafc; font-family: 'Inter', system-ui, sans-serif;
        }
        .hero-glass {
            border-radius: 32px; background: var(--glass);
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
        }
        .stat-badge {
            background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px; padding: 1rem; text-align: center;
        }
        .hero-title { font-size: clamp(2.5rem, 5vw, 4rem); line-height: 1.1; font-weight: 800; }
        .btn-xl { padding: 1rem 2rem; border-radius: 16px; font-weight: 700; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .btn-xl:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(59, 130, 246, 0.4); }
        .avatar-kinetic {
            width: 180px; height: 180px; border-radius: 40px;
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.4);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="hero-glass p-4 p-lg-5 animate-in">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div class="d-flex align-items-center gap-2">
                <i data-lucide="layers" class="text-primary"></i>
                <span class="fw-bold tracking-tight text-uppercase small">Nexus Alumni Network</span>
            </div>
            <div class="d-flex gap-2">
                <button id="themeToggle" class="btn btn-sm btn-outline-light rounded-pill px-3">Dark</button>
                <?php if($isAdmin): ?>
                    <a href="admin/dashboard.php" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold">Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <h1 class="hero-title mb-4">
                    Reconnect with your <span class="text-primary">Legacy.</span>
                </h1>
                <p class="text-secondary fs-5 mb-5 opacity-75">
                    The professional bridge between your academic roots and your career future. 
                    Join <span class="text-white fw-bold"><?= $stats['alumni'] ?>+ verified alumni</span> sharing insights and opportunities.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-5">
                    <?php if ($isAlumni): ?>
                        <a href="profile.php" class="btn btn-primary btn-xl d-flex align-items-center gap-2">
                            <i data-lucide="user"></i> Enter Profile
                        </a>
                        <a href="directory.php" class="btn btn-outline-light btn-xl d-flex align-items-center gap-2">
                            <i data-lucide="search"></i> Directory
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-xl px-5">Member Login</a>
                        <a href="register.php" class="btn btn-outline-primary btn-xl px-5">Join Network</a>
                    <?php endif; ?>
                </div>

                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="stat-badge">
                            <div class="h4 fw-bold mb-0"><?= $stats['alumni'] ?></div>
                            <div class="small text-secondary">Verified Members</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="stat-badge">
                            <div class="h4 fw-bold mb-0"><?= $stats['posts'] ?></div>
                            <div class="small text-secondary">Active Posts</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block text-center">
                <div class="avatar-kinetic mx-auto mb-4">
                    <i data-lucide="graduation-cap" color="white" size="80"></i>
                </div>
                <div class="glass p-3 rounded-4 border border-secondary border-opacity-25 inline-block">
                    <p class="small text-secondary mb-0">
                        <i data-lucide="shield-check" size="14" class="text-success"></i> 
                        Secure, Admin-Verified Ecosystem
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-5 pt-5 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="text-muted small">
                © <?= date('Y') ?> Nexus Alumni Portal. Built for high-integrity networking.
            </div>
            <?php if(!$isAdmin): ?>
                <a href="admin/admin_login.php" class="text-secondary text-decoration-none small hover-link">
                    Staff Authentication <i data-lucide="chevron-right" size="14"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    // Entry Animation
    document.querySelector('.animate-in').style.opacity = '0';
    document.querySelector('.animate-in').style.transform = 'translateY(20px)';
    window.onload = () => {
        const el = document.querySelector('.animate-in');
        el.style.transition = 'all 0.8s ease-out';
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    };
</script>
<script src="js/app.js"></script>
</body>
</html>
