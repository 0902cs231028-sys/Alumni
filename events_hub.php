<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

// 1. Authorization Guard
if (empty($_SESSION['alumni_id'])) {
    header("Location: login.php");
    exit;
}

$alumni_id = (int)$_SESSION['alumni_id'];

/**
 * 2. PERFORMANCE QUERY: Fetching Approved Events
 * Joins with alumni to show the host's identity
 */
$sql = "SELECT e.*, a.name as host_name, a.profile_pic as host_pic 
        FROM events e 
        LEFT JOIN alumni a ON e.host_id = a.id 
        WHERE e.is_approved = 1 
        ORDER BY e.event_date ASC";
$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Community Events | Alumni Nexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --accent: #3b82f6; --glass: rgba(15, 23, 42, 0.94); }
        body { 
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            color: #f8fafc; font-family: 'Inter', system-ui, sans-serif; min-height: 100vh;
        }
        .hub-shell { max-width: 1200px; margin: 3rem auto; padding: 0 1rem; }
        .event-card { 
            background: var(--glass); border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 2rem; overflow: hidden; transition: all 0.3s ease;
        }
        .event-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        .host-avatar { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid var(--accent); }
        .date-badge { background: rgba(59, 130, 246, 0.1); color: var(--accent); padding: 0.5rem 1rem; border-radius: 12px; font-weight: 700; }
    </style>
</head>
<body>

<div class="hub-shell">
    <header class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">Global Events Hub</h2>
            <p class="text-secondary mb-0">Discover reunions, workshops, and meetups hosted by your batch.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="profile.php" class="btn btn-outline-light rounded-pill px-4">My Profile</a>
            <button class="btn btn-primary rounded-pill px-4" onclick="window.history.back()">Back</button>
        </div>
    </header>

    <div class="row g-4">
        <?php if ($res && mysqli_num_rows($res) > 0): ?>
            <?php while($ev = mysqli_fetch_assoc($res)): ?>
                <div class="col-lg-6">
                    <div class="event-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div class="date-badge">
                                <i data-lucide="calendar" size="14" class="me-1"></i>
                                <?= date('M d, Y', strtotime($ev['event_date'])) ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-secondary">Hosted by</span>
                                <img src="<?= $ev['host_pic'] ? $ev['host_pic'] : 'https://ui-avatars.com/api/?name='.urlencode($ev['host_name']) ?>" class="host-avatar">
                                <span class="fw-bold small"><?= htmlspecialchars($ev['host_name']) ?></span>
                            </div>
                        </div>

                        <h3 class="fw-bold mb-2"><?= htmlspecialchars($ev['title']) ?></h3>
                        <p class="text-secondary mb-4 opacity-75" style="min-height: 60px;">
                            <?= nl2br(htmlspecialchars(substr($ev['description'], 0, 150))) ?>...
                        </p>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top border-secondary border-opacity-25">
                            <div class="d-flex gap-3">
                                <div class="small text-secondary"><i data-lucide="users" size="14"></i> <?= $ev['rsvp_count'] ?> Attending</div>
                            </div>
                            <button class="btn btn-primary rounded-pill px-4 fw-bold">RSVP to Event</button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 glass-card rounded-5">
                <i data-lucide="calendar-off" size="48" class="text-muted mb-3"></i>
                <h4 class="text-secondary">No active events found.</h4>
                <p class="text-muted">Why not host the first one from your profile?</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
