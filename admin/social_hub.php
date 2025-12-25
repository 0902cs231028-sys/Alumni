<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch Real-time Social Metrics
$pending_ev = mysqli_query($conn, "SELECT COUNT(*) FROM events WHERE is_approved = 0");
$total_pending = ($pending_ev) ? mysqli_fetch_row($pending_ev)[0] : 0;

$active_ev = mysqli_query($conn, "SELECT COUNT(*) FROM events WHERE is_approved = 1 AND event_date >= NOW()");
$total_active = ($active_ev) ? mysqli_fetch_row($active_ev)[0] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Social Command | Nexus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --p-blue: #3b82f6; --glass: rgba(15, 23, 42, 0.96); }
        body { 
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            color: #f8fafc; font-family: 'Inter', sans-serif; min-height: 100vh;
        }
        .glass-card { 
            background: var(--glass); border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 2rem; backdrop-filter: blur(20px);
        }
        .action-card { transition: all 0.3s ease; cursor: pointer; border: 1px solid rgba(255,255,255,0.05); }
        .action-card:hover { transform: translateY(-5px); border-color: var(--p-blue); background: rgba(59, 130, 246, 0.05); }
        .broadcast-zone { 
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1)); 
            border: 1px dashed var(--p-blue); 
        }
    </style>
</head>
<body>

<div class="container py-5">
    <header class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-bold mb-1">Social Command Center</h1>
            <p class="text-secondary mb-0">Manage community engagement and mass broadcasts</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-light rounded-pill px-4">Back to Dashboard</a>
    </header>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <a href="pending_events.php" class="text-decoration-none text-white">
                <div class="glass-card action-card p-4 d-flex align-items-center gap-4">
                    <div class="bg-primary bg-opacity-10 p-4 rounded-4 text-primary">
                        <i data-lucide="calendar-clock" size="32"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1">Event Queue</h4>
                        <p class="text-secondary small mb-0"><?= $total_pending ?> requests awaiting review</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6">
            <a href="../events_hub.php" class="text-decoration-none text-white">
                <div class="glass-card action-card p-4 d-flex align-items-center gap-4">
                    <div class="bg-success bg-opacity-10 p-4 rounded-4 text-success">
                        <i data-lucide="zap" size="32"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1">Live Events</h4>
                        <p class="text-secondary small mb-0"><?= $total_active ?> events currently live for alumni</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="glass-card broadcast-zone p-5">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h3 class="fw-bold mb-3"><i data-lucide="megaphone" class="me-2 text-primary"></i> Global Broadcast</h3>
                <p class="text-secondary mb-4">Send a push notification to **every registered alumni**. This creates a site-wide alert on their next login.</p>
                
                <form action="send_broadcast.php" method="POST">
                    <div class="mb-3">
                        <textarea name="message" class="form-control bg-dark border-secondary text-white" rows="3" placeholder="Write your urgent message..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill">
                        Dispatch Broadcast <i data-lucide="send" size="16" class="ms-2"></i>
                    </button>
                </form>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center opacity-25">
                <i data-lucide="users" size="140"></i>
            </div>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
