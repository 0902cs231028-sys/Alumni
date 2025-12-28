<?php
declare(strict_types=1);
require __DIR__ . '/includes/connection.php';

// 1. Input Validation
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(404);
    die("Profile not found.");
}

/** * 2. PERFORMANCE: Multi-Query Aggregation
 * We fetch the profile and basic stats in a single pass where possible.
 */
$sql = "SELECT id, name, email, batch, branch, phone, city, linkedin, created_at, profile_pic 
        FROM alumni 
        WHERE id = ? AND approved = 1 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    http_response_code(404);
    die("Profile unavailable or restricted.");
}

// 3. Fetch User Activity (Latest 3 Posts) for the Timeline
$post_stmt = $conn->prepare("SELECT id, title, created_at FROM posts WHERE alumni_id = ? AND approved = 1 ORDER BY created_at DESC LIMIT 3");
$post_stmt->bind_param("i", $id);
$post_stmt->execute();
$latest_posts = $post_stmt->get_result();

// 4. Calculate Contribution Stats
$stat_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM posts WHERE alumni_id = ? AND approved = 1) as post_count,
    (SELECT COUNT(*) FROM comments WHERE alumni_id = ?) as comment_count");
$stat_stmt->bind_param("ii", $id, $id);
$stat_stmt->execute();
$stats = $stat_stmt->get_result()->fetch_assoc();

$joined = $profile['created_at'] ? date('M Y', strtotime($profile['created_at'])) : 'Recently';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($profile['name']) ?> | Nexus Alumni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --p-blue: #3b82f6; --p-indigo: #6366f1; --glass: rgba(15, 23, 42, 0.85); }
        body { 
            background: #020617; color: #f8fafc; font-family: 'Inter', system-ui, sans-serif;
            background-image: radial-gradient(circle at 50% -20%, #1e293b, #020617);
        }
        .profile-header {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(99, 102, 241, 0.1));
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 4rem 0 2rem;
        }
        .glass-card {
            background: var(--glass); backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1.5rem;
        }
        .stat-box { text-align: center; padding: 1rem; border-right: 1px solid rgba(255, 255, 255, 0.05); }
        .stat-box:last-child { border-right: none; }
        .avatar-main {
            width: 120px; height: 120px; border-radius: 2rem;
            background: linear-gradient(135deg, var(--p-blue), var(--p-indigo));
            display: flex; align-items: center; justify-content: center;
            font-size: 3.5rem; font-weight: 800; box-shadow: 0 20px 40px rgba(59, 130, 246, 0.3);
        }
        .activity-item {
            padding: 1rem; border-radius: 1rem; background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent; transition: 0.2s; text-decoration: none; display: block;
        }
        .activity-item:hover { border-color: var(--p-blue); background: rgba(59, 130, 246, 0.05); }
    </style>
</head>
<body>

<div class="profile-header mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-auto mb-4 mb-md-0 text-center">
                <div class="avatar-main mx-auto">
                    <?= mb_strtoupper(mb_substr($profile['name'], 0, 1)) ?>
                </div>
            </div>
            <div class="col-md">
                <div class="d-flex align-items-center gap-3 mb-2 justify-content-center justify-content-md-start">
                    <h1 class="fw-bold mb-0"><?= htmlspecialchars($profile['name']) ?></h1>
                    <span class="badge bg-primary rounded-pill px-3">Verified</span>
                </div>
                <p class="text-secondary text-center text-md-start mb-3">
                    <i data-lucide="graduation-cap" size="18" class="me-1"></i> <?= htmlspecialchars($profile['branch']) ?> • Batch of <?= htmlspecialchars($profile['batch']) ?>
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                    <?php if ($profile['linkedin']): ?>
                        <a href="<?= htmlspecialchars($profile['linkedin']) ?>" target="_blank" class="btn btn-sm btn-primary px-4 rounded-pill">
                            <i data-lucide="linkedin" size="16" class="me-1"></i> LinkedIn
                        </a>
                    <?php endif; ?>
                    <a href="mailto:<?= htmlspecialchars($profile['email']) ?>" class="btn btn-sm btn-outline-light px-4 rounded-pill">
                        <i data-lucide="mail" size="16" class="me-1"></i> Contact
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold mb-4">Network Activity</h5>
                <div class="d-flex justify-content-around">
                    <div class="stat-box">
                        <div class="h3 fw-bold mb-0"><?= $stats['post_count'] ?></div>
                        <div class="small text-secondary">Posts</div>
                    </div>
                    <div class="stat-box">
                        <div class="h3 fw-bold mb-0"><?= $stats['comment_count'] ?></div>
                        <div class="small text-secondary">Comments</div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3">Information</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3 d-flex align-items-center gap-3">
                        <i data-lucide="map-pin" class="text-primary" size="20"></i>
                        <div>
                            <div class="small text-secondary text-uppercase">Current Location</div>
                            <div class="fw-medium"><?= $profile['city'] ?: 'Undisclosed' ?></div>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-center gap-3">
                        <i data-lucide="calendar" class="text-primary" size="20"></i>
                        <div>
                            <div class="small text-secondary text-uppercase">Member Since</div>
                            <div class="fw-medium"><?= $joined ?></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Recent Contributions</h5>
                    <i data-lucide="trending-up" class="text-secondary"></i>
                </div>

                <?php if ($latest_posts->num_rows > 0): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php while($p = $latest_posts->fetch_assoc()): ?>
                            <a href="view_post.php?id=<?= $p['id'] ?>" class="activity-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-white mb-0"><?= htmlspecialchars($p['title']) ?></h6>
                                    <span class="small text-secondary"><?= date('M j', strtotime($p['created_at'])) ?></span>
                                </div>
                                <p class="small text-secondary mb-0">Click to read this alumni post...</p>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i data-lucide="message-square-off" size="40" class="text-muted mb-3"></i>
                        <p class="text-secondary">No public posts shared yet.</p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 pt-3 border-top border-secondary opacity-25">
                    <a href="directory.php" class="text-decoration-none text-primary small fw-bold">
                        <i data-lucide="arrow-left" size="14"></i> Return to Directory
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
 </body>
</html>
</body>
</html>
