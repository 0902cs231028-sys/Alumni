<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';

// 1. Authorization Guard
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// 2. Generate CSRF Token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Optimized Query (Prepared Statements or refined selection)
$query = "SELECT id, name, email, batch, branch, created_at FROM alumni WHERE approved = 0 ORDER BY created_at DESC";
$res = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pending Alumni | Admin Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.03); transition: 0.3s; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .empty-state { padding: 4rem; text-align: center; color: #6c757d; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Pending Approvals</h2>
            <p class="text-muted">Review and manage new alumni registrations</p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i data-lucide="arrow-left" size="18"></i> Back
            </a>
            <button id="themeToggle" class="btn btn-dark d-flex align-items-center gap-2">
                <i data-lucide="moon" size="18"></i> Dark Mode
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_info'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <?= htmlspecialchars($_SESSION['flash_info']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['flash_info']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-white py-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by name, email, or batch...">
        </div>
        
        <div class="table-responsive">
            <?php if(!$res || mysqli_num_rows($res) == 0): ?>
                <div class="empty-state">
                    <i data-lucide="users" size="48" class="mb-3"></i>
                    <h4>All caught up!</h4>
                    <p>There are no pending registrations to review.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Alumnus Information</th>
                            <th>Academic Details</th>
                            <th>Applied Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="alumniTable">
                        <?php while($r = mysqli_fetch_assoc($res)): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($r['name']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($r['email']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($r['batch']) ?></span>
                                <div class="small mt-1"><?= htmlspecialchars($r['branch']) ?></div>
                            </td>
                            <td>
                                <div class="small text-muted">
                                    <?= date('M j, Y', strtotime($r['created_at'])) ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <form action="approve_alumni.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="act" value="approve">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success px-3">Approve</button>
                                </form>

                                <form action="approve_alumni.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="act" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Reject and delete this registration?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize Icons
    lucide.createIcons();

    // Live Search Logic
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#alumniTable tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
</script>
    </body>
</html>
</body>
</html>
