<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

// 1. Authorization Guard
if (!isset($_SESSION['alumni_id'])) {
    header("Location: login.php");
    exit;
}

$alumni_id = (int)$_SESSION['alumni_id'];

// 2. Generate CSRF Token for Secure Deletion
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Filter Logic (Tab Management)
$filter = $_GET['tab'] ?? 'all';
$where_clause = "p.alumni_id = ?";

if ($filter === 'pending') {
    $where_clause .= " AND p.approved = 0";
} elseif ($filter === 'live') {
    $where_clause .= " AND p.approved = 1";
}

// 4. Optimized Query: Fetch Posts + Engagement Metrics
$sql = "SELECT p.id, p.title, p.body, p.approved, p.created_at, 
               COUNT(c.id) as comment_count 
        FROM posts p 
        LEFT JOIN comments c ON p.id = c.post_id 
        WHERE $where_clause 
        GROUP BY p.id 
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumni_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Content | Alumni Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --glass: rgba(255, 255, 255, 0.9); }
        body { background: #f4f7fe; font-family: 'Inter', sans-serif; color: #1e293b; }
        .dashboard-header { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: white; border-radius: 0 0 2rem 2rem; padding: 3rem 0; margin-bottom: -2rem; }
        .content-card { border: none; border-radius: 1.25rem; background: var(--glass); backdrop-filter: blur(10px); transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .content-card:hover { transform: translateY(-3px); }
        .nav-pills .nav-link { color: #64748b; font-weight: 500; border-radius: 0.75rem; padding: 0.6rem 1.2rem; }
        .nav-pills .nav-link.active { background: white; color: #6366f1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.7rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; }
        .stat-item { font-size: 0.85rem; color: #64748b; display: flex; align-items: center; gap: 0.4rem; }
    </style>
</head>
<body>

<div class="dashboard-header mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1">My Posts</h1>
                <p class="opacity-75 mb-0">Manage your contributions and track engagement</p>
            </div>
            <div class="d-flex gap-2">
                <a href="create_post.php" class="btn btn-white bg-white text-primary fw-bold rounded-pill px-4">
                    <i data-lucide="plus-circle" size="18" class="me-1"></i> New Post
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <nav class="nav nav-pills gap-2">
            <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="?tab=all">All Posts</a>
            <a class="nav-link <?= $filter === 'live' ? 'active' : '' ?>" href="?tab=live">Live</a>
            <a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>" href="?tab=pending">Pending</a>
        </nav>
        <a href="profile.php" class="btn btn-link text-decoration-none text-secondary">
            <i data-lucide="arrow-left" size="16"></i> Back to Profile
        </a>
    </div>

    <div class="row g-4">
        <?php if ($res->num_rows === 0): ?>
            <div class="col-12 text-center py-5">
                <div class="bg-white p-5 rounded-4 shadow-sm border">
                    <i data-lucide="file-question" size="48" class="text-muted mb-3"></i>
                    <h4 class="fw-bold">No posts found</h4>
                    <p class="text-secondary">You haven't shared anything in this category yet.</p>
                    <a href="create_post.php" class="btn btn-primary mt-2">Create Your First Post</a>
                </div>
            </div>
        <?php else: ?>
            <?php while($p = $res->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card content-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge status-badge p-2 <?= $p['approved'] == 1 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                                <?= $p['approved'] == 1 ? 'Live' : 'Under Review' ?>
                            </span>
                            <div class="stat-item">
                                <i data-lucide="message-square" size="16"></i> <?= $p['comment_count'] ?>
                            </div>
                        </div>
                        
                        <h5 class="fw-bold mb-2 text-truncate"><?= htmlspecialchars($p['title']) ?></h5>
                        <p class="text-secondary small mb-4" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= htmlspecialchars(strip_tags($p['body'])) ?>
                        </p>

                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><?= date('M j, Y', strtotime($p['created_at'])) ?></span>
                            
                            <div class="btn-group">
                                <?php if($p['approved'] == 1): ?>
                                    <a href="view_post.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-light border">View</a>
                                <?php endif; ?>

                                <form action="delete_post.php" method="POST" onsubmit="return confirm('Permanently remove this post?');" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger ms-1">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    lucide.createIcons();
    </script>
</body>
</html>
</script>
</body>
</html>