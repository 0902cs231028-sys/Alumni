<?php
require __DIR__ . '/includes/connection.php';

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[] = '(name LIKE CONCAT("%", ?, "%") OR email LIKE CONCAT("%", ?, "%"))';
    $params[] = $search;
    $params[] = $search;
    $types   .= 'ss';
}
if ($status === 'approved') {
    $where[] = 'approved = 1';
} elseif ($status === 'pending') {
    $where[] = 'approved = 0';
}

$sql = "SELECT id, name, email, approved, batch, branch 
        FROM alumni";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';

$stmt = mysqli_prepare($conn, $sql);
if ($stmt && $types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = false;
} // [web:299][web:501]
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Alumni · Admin View</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            color: #e5e7eb;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        .wrap {
            max-width: 1024px;
            margin: 32px auto;
        }
        .glass {
            border-radius: 18px;
            background: rgba(15, 23, 42, 0.94);
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.9),
                0 0 0 1px rgba(148, 163, 184, 0.22);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }
        .table-dark > :not(caption) > * > * {
            border-bottom-color: rgba(148, 163, 184, 0.25);
        }
        .badge-pill {
            border-radius: 999px;
        }
        .search-input {
            max-width: 260px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <small class="text-uppercase text-secondary fw-semibold">Admin</small>
            <h2 class="mb-0">Alumni Directory (raw)</h2>
            <p class="text-muted small mb-0">Search and inspect all alumni records, including approval status.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="admin/dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
            <button id="themeToggle" class="btn btn-sm btn-outline-light">Dark</button>
        </div>
    </div>

    <div class="glass p-3 p-md-4">
        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-5">
                <input type="text" name="q"
                       class="form-control search-input"
                       placeholder="Search by name or email"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All statuses</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved only</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending only</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-fill">Filter</button>
                <a href="list_alumini.php" class="btn btn-outline-secondary flex-fill">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name &amp; Email</th>
                    <th>Batch / Branch</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$res || mysqli_num_rows($res) === 0): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No alumni found for this filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($r = mysqli_fetch_assoc($res)): ?>
                        <tr>
                            <td>#<?= (int)$r['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($r['name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($r['email']) ?></div>
                            </td>
                            <td>
                                <div class="small text-muted">
                                    Batch: <?= htmlspecialchars($r['batch'] ?? '-') ?> |
                                    <?= htmlspecialchars($r['branch'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($r['approved']): ?>
                                    <span class="badge badge-pill bg-success bg-opacity-75">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-pill bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="view_alumini.php?id=<?= (int)$r['id'] ?>"
                                   class="btn btn-sm btn-outline-light">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    <script src="js/app.js"></script>
</body>
</html>

<script src="js/app.js"></script>
</body>
</html>