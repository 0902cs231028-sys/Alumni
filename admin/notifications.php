<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';

// 1. Authorization Guard
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// 2. CSRF Token Generation for background actions
if (empty($_SESSION['notif_action_token'])) {
    $_SESSION['notif_action_token'] = bin2hex(random_bytes(32));
}

/** * 3. EXTREME DATA FETCH: Joins notifications with alumni data
 * Displays "New" reports first to prioritize admin workflow
 */
$sql = "SELECT n.id, n.type, n.target_id, n.reporter_id, n.seen, n.created_at,
               a.name AS reporter_name
        FROM notifications n
        LEFT JOIN alumni a ON n.reporter_id = a.id
        ORDER BY n.seen ASC, n.created_at DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports & Moderation | Nexus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --accent: #ef4444; --glass: rgba(15, 23, 42, 0.94); }
        body { 
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            color: #f8fafc; font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
        }
        .mod-wrap { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .mod-card { 
            background: var(--glass); border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 24px; backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        .table-nexus { --bs-table-bg: transparent; --bs-table-color: #f8fafc; }
        .row-new { background: rgba(239, 68, 68, 0.05) !important; border-left: 4px solid var(--accent); }
        .badge-type { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
        .btn-action { border-radius: 12px; font-weight: 600; transition: 0.2s; }
        .btn-view { background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); }
        .btn-view:hover { background: #3b82f6; color: white; border-color: #3b82f6; }
    </style>
</head>
<body>

<div class="mod-wrap">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-secondary text-decoration-none small">Admin</a></li>
                    <li class="breadcrumb-item active text-white small" aria-current="page">Reports</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-0">Moderation Queue</h2>
        </div>
        <div class="d-flex gap-2">
            <button id="themeToggle" class="btn btn-sm btn-outline-light rounded-pill px-3">Dark Mode</button>
            <a href="dashboard.php" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">Exit Terminal</a>
        </div>
    </div>

    <?php foreach(['flash_info' => 'success', 'flash_error' => 'danger'] as $key => $type): ?>
        <?php if (!empty($_SESSION[$key])): ?>
            <div class="alert alert-<?= $type ?> border-0 shadow-sm animate-in mb-3">
                <i data-lucide="<?= $type === 'success' ? 'check-circle' : 'alert-circle' ?>" size="18" class="me-2"></i>
                <?= htmlspecialchars($_SESSION[$key]) ?>
            </div>
            <?php unset($_SESSION[$key]); ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="mod-card">
        <div class="table-responsive">
            <table class="table table-nexus table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-secondary small text-uppercase fw-bold">
                        <th class="ps-4">Classification</th>
                        <th>Target Content</th>
                        <th>Reporter</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$result || mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i data-lucide="shield-check" size="48" class="text-muted mb-3"></i>
                            <h5 class="text-secondary">Community is Clean</h5>
                            <p class="text-muted small">No pending reports in the queue.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="<?= $row['seen'] ? '' : 'row-new' ?>">
                            <td class="ps-4">
                                <span class="badge badge-type p-2 <?= $row['type'] === 'post' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                    <?= htmlspecialchars($row['type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold">ID #<?= (int)$row['target_id'] ?></div>
                                <a href="../view_<?= $row['type'] ?>_admin.php?id=<?= (int)$row['target_id'] ?>" 
                                   class="btn btn-sm btn-view mt-1">
                                    <i data-lucide="eye" size="14" class="me-1"></i> Inspect
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-secondary rounded-circle" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:10px;">
                                        <?= strtoupper(substr($row['reporter_name'] ?: 'U', 0, 1)) ?>
                                    </div>
                                    <span class="small fw-medium"><?= htmlspecialchars($row['reporter_name'] ?: ('ID '.$row['reporter_id'])) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($row['seen']): ?>
                                    <span class="badge bg-secondary-subtle text-secondary small">Resolved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger p-2 animate-pulse small">URGENT</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small">
                                <?= date('M j, Y', strtotime($row['created_at'])) ?><br>
                                <span class="opacity-50"><?= date('H:i', strtotime($row['created_at'])) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <?php if (!$row['seen']): ?>
                                    <form method="POST" action="notification_action.php">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <input type="hidden" name="act" value="seen">
                                        <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['notif_action_token']) ?>">
                                        <button class="btn btn-sm btn-action btn-primary">
                                            Mark Resolved
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <i data-lucide="check" class="text-success opacity-50"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
<script src="../js/app.js"></script>
</body>
</html>
