<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';
require __DIR__ . '/../includes/security_helper.php'; // For CSRF tokens

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$token = generate_csrf_token(); // Ensure tokens are ready for actions

// High-Performance Query with Author Meta
$sql = "SELECT p.*, a.name, a.email, a.batch,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND deleted_at IS NULL) as comment_count
        FROM posts p 
        LEFT JOIN alumni a ON p.alumni_id = a.id
        WHERE p.deleted_at IS NULL
        ORDER BY p.approved ASC, p.created_at DESC";

$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nexus Supreme | Content Moderator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --p-blue: #3b82f6; --glass: rgba(15, 23, 42, 0.8); }
        body { 
            background: #020617; color: #f8fafc; font-family: 'Inter', sans-serif;
            background-image: radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.1) 0, transparent 50%);
            min-height: 100vh;
        }
        .supreme-card { 
            background: var(--glass); border: 1px solid rgba(255,255,255,0.08); 
            border-radius: 2rem; backdrop-filter: blur(20px); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .supreme-card:hover { transform: translateY(-5px); border-color: var(--p-blue); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .comment-item { background: rgba(255, 255, 255, 0.02); border-left: 3px solid var(--p-blue); border-radius: 12px; transition: 0.3s; }
        .comment-item:hover { background: rgba(59, 130, 246, 0.05); }
        .search-bar { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; color: white; }
        .status-badge { font-size: 0.65rem; padding: 0.4rem 1rem; border-radius: 50px; text-transform: uppercase; }
        .nav-pills .nav-link { color: #94a3b8; border-radius: 50px; }
        .nav-pills .nav-link.active { background: var(--p-blue); color: white; }
    </style>
</head>
<body>

<div class="container py-5">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 gap-4">
        <div>
            <h1 class="fw-bold mb-1 d-flex align-items-center gap-3">
                <i data-lucide="shield-check" class="text-primary" size="40"></i> Moderation Terminal
            </h1>
            <p class="text-secondary mb-0">Live Command: Reviewing <span class="text-white"><?= mysqli_num_rows($res) ?> active entries</span></p>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <input type="text" id="contentSearch" class="form-control search-bar px-4" placeholder="Filter posts...">
            <a href="dashboard.php" class="btn btn-outline-light rounded-pill px-4">Exit</a>
        </div>
    </header>

    <ul class="nav nav-pills mb-5 gap-2 justify-content-center" id="modTabs">
        <li class="nav-item"><button class="nav-link active px-4" onclick="filterPosts('all')">All Feed</button></li>
        <li class="nav-item"><button class="nav-link px-4" onclick="filterPosts('pending')">Awaiting Review</button></li>
        <li class="nav-item"><button class="nav-link px-4" onclick="filterPosts('live')">Live Content</button></li>
    </ul>

    <div class="row g-4" id="postsGrid">
        <?php while($p = mysqli_fetch_assoc($res)): ?>
            <div class="col-12 post-entry" data-status="<?= $p['approved'] ? 'live' : 'pending' ?>">
                <div class="supreme-card p-4 p-md-5 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="d-flex gap-3">
                            <div class="p-3 rounded-4 bg-primary bg-opacity-10 text-primary">
                                <i data-lucide="<?= $p['approved'] ? 'globe' : 'eye-off' ?>"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-1 post-title"><?= htmlspecialchars($p['title']) ?></h3>
                                <div class="d-flex gap-2 align-items-center text-secondary small">
                                    <span class="text-white fw-medium"><?= htmlspecialchars($p['name'] ?? 'Guest') ?></span>
                                    <span>•</span>
                                    <span>Batch <?= htmlspecialchars($p['batch'] ?? 'N/A') ?></span>
                                    <span>•</span>
                                    <span><?= date('M j, Y', strtotime($p['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <span class="status-badge <?= $p['approved'] ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-warning bg-opacity-10 text-warning border border-warning' ?>">
                            <?= $p['approved'] ? 'Public Live' : 'Pending Verification' ?>
                        </span>
                    </div>

                    <div class="p-4 bg-white bg-opacity-5 rounded-4 mb-5 border border-white border-opacity-5">
                        <p class="fs-5 mb-0 opacity-90 post-body" style="white-space: pre-wrap;"><?= htmlspecialchars($p['body']) ?></p>
                    </div>

                    <div class="comment-section">
                        <button class="btn btn-link text-white text-decoration-none p-0 mb-3 fw-bold d-flex align-items-center gap-2" 
                                type="button" data-bs-toggle="collapse" data-bs-target="#comments-<?= $p['id'] ?>">
                            <i data-lucide="message-square" size="18"></i> 
                            Threads (<?= $p['comment_count'] ?>)
                            <i data-lucide="chevron-down" size="14"></i>
                        </button>

                        <div class="collapse" id="comments-<?= $p['id'] ?>">
                            <?php
                            $pid = (int)$p['id'];
                            $c_sql = "SELECT c.*, a.name FROM comments c LEFT JOIN alumni a ON c.alumni_id = a.id WHERE c.post_id = $pid AND c.deleted_at IS NULL";
                            $c_res = mysqli_query($conn, $c_sql);
                            while($com = mysqli_fetch_assoc($c_res)): ?>
                                <div class="comment-item p-3 mb-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fw-bold small"><?= htmlspecialchars($com['name'] ?? 'Alumni') ?></span>
                                            <span class="opacity-25 small">•</span>
                                            <span class="opacity-50 small"><?= date('H:i', strtotime($com['created_at'])) ?></span>
                                        </div>
                                        <p class="small mb-0 opacity-75"><?= htmlspecialchars($com['content']) ?></p>
                                    </div>
                                    <a href="delete_comment.php?id=<?= $com['id'] ?>&token=<?= $token ?>" 
                                       class="btn btn-sm btn-outline-danger border-0 rounded-circle" 
                                       onclick="return confirm('Purge this comment permanently?')">
                                        <i data-lucide="trash-2" size="16"></i>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-top border-white border-opacity-5 d-flex flex-wrap gap-3">
                        <?php if(!$p['approved']): ?>
                            <a href="approve_post.php?id=<?= $p['id'] ?>&act=approve&token=<?= $token ?>" 
                               class="btn btn-primary fw-bold px-5 rounded-pill shadow-lg">Authorize Post</a>
                        <?php endif; ?>
                        
                        <a href="approve_post.php?id=<?= $p['id'] ?>&act=delete&token=<?= $token ?>" 
                           class="btn btn-outline-danger px-5 rounded-pill fw-medium" 
                           onclick="return confirm('Warning: This will permanently redact this content. Proceed?')">
                            Destroy Entry
                        </a>
                        
                        <button class="btn btn-outline-secondary px-4 rounded-pill ms-auto" onclick="window.print()">
                            <i data-lucide="printer" size="18"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    // SUPREME FILTER LOGIC
    function filterPosts(status) {
        document.querySelectorAll('.post-entry').forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        // Update active tab UI
        document.querySelectorAll('.nav-link').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
    }

    // REAL-TIME SEARCH LOGIC
    document.getElementById('contentSearch').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.post-entry').forEach(card => {
            const text = card.querySelector('.post-title').innerText.toLowerCase() + 
                         card.querySelector('.post-body').innerText.toLowerCase();
            card.style.display = text.includes(term) ? 'block' : 'none';
        });
    });
</script>
</body>
</html>
