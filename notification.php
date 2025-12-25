<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';

// 1. Administrative Guard
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

/** * 2. EXTREME DATA AGGREGATION
 * Joins notifications with alumni (reporters) to show exactly who is flagging content.
 */
$sql = "SELECT n.*, a.name AS reporter_name 
        FROM notifications n 
        LEFT JOIN alumni a ON n.user_id = a.id 
        WHERE n.seen = 0 
        ORDER BY n.created_at DESC";

$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Security Center | Nexus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --danger: #ef4444; --glass: rgba(15, 23, 42, 0.95); }
        body { 
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            color: #f8fafc; font-family: 'Inter', sans-serif; min-height: 100vh;
        }
        .report-card {
            background: var(--glass); border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem; transition: all 0.3s ease;
            backdrop-filter: blur(12px);
        }
        .report-card:hover { border-color: var(--danger); transform: scale(1.01); }
        .type-badge { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 0.3rem 0.6rem; border-radius: 6px; }
        .badge-post { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .badge-comment { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
        .btn-action { border-radius: 0.75rem; font-weight: 600; padding: 0.5rem 1rem; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h2 class="fw-bold mb-1">Security Center</h2>
            <p class="text-secondary small mb-0">Reviewing reported community content</p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-light rounded-pill px-4">Dashboard</a>
            <button onclick="location.reload()" class="btn btn-primary rounded-pill"><i data-lucide="refresh-cw" size="18"></i></button>
        </div>
    </div>

    <div class="report-queue">
        <?php if (!$res || mysqli_num_rows($res) == 0): ?>
            <div class="text-center py-5 glass rounded-4 border border-secondary border-opacity-25">
                <i data-lucide="shield-check" size="48" class="text-success mb-3"></i>
                <h4 class="fw-bold">Queue Clear</h4>
                <p class="text-secondary">No pending community reports found.</p>
            </div>
        <?php else: ?>
            <?php while ($n = mysqli_fetch_assoc($res)): ?>
                <div class="report-card p-4 mb-3" id="report-<?= $n['id'] ?>">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <span class="type-badge badge-<?= $n['type'] ?>"><?= htmlspecialchars($n['type']) ?></span>
                                <small class="text-muted"><i data-lucide="clock" size="12"></i> <?= date('M j, g:i a', strtotime($n['created_at'])) ?></small>
                            </div>
                            <h5 class="fw-bold mb-1">Reported by <?= htmlspecialchars($n['reporter_name'] ?? 'Anonymous') ?></h5>
                            <p class="text-secondary small mb-0">Violation flagged on Target ID: <span class="text-white">#<?= intval($n['user_id']) ?></span></p>
                        </div>
                        <div class="col-md-5 text-md-end mt-3 mt-md-0">
                            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                                <?php if ($n['type'] == 'post'): ?>
                                    <a href="../view_post_admin.php?id=<?= intval($n['user_id']) ?>" class="btn btn-sm btn-action btn-outline-primary">
                                        <i data-lucide="eye" size="14"></i> View
                                    </a>
                                    <a href="approve_post.php?id=<?= intval($n['user_id']) ?>&act=delete" class="btn btn-sm btn-action btn-danger">
                                        <i data-lucide="trash-2" size="14"></i> Remove
                                    </a>
                                <?php else: ?>
                                    <a href="../view_comment_admin.php?id=<?= intval($n['user_id']) ?>" class="btn btn-sm btn-action btn-outline-primary">
                                        <i data-lucide="eye" size="14"></i> View
                                    </a>
                                    <a href="../delete_comment.php?id=<?= intval($n['user_id']) ?>" class="btn btn-sm btn-action btn-danger">
                                        <i data-lucide="trash-2" size="14"></i> Remove
                                    </a>
                                <?php endif; ?>
                                
                                <button onclick="markSeen(<?= $n['id'] ?>)" class="btn btn-sm btn-action btn-secondary">
                                    <i data-lucide="check-circle" size="14"></i> Clear
                                </button>
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

    /** * AJAX: Mark Notification as Seen
     * Prevents page refresh for better admin flow.
     */
    async function markSeen(id) {
        try {
            const response = await fetch(`mark_seen.php?id=${id}`);
            if (response.ok) {
                const card = document.getElementById(`report-${id}`);
                card.style.opacity = '0';
                card.style.transform = 'translateX(20px)';
                setTimeout(() => card.remove(), 300);
            }
        } catch (error) {
            console.error('Failed to clear report:', error);
        }
    }
</script>
</body>
</html>
