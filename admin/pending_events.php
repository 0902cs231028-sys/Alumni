<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch all unapproved events with host names
$sql = "SELECT e.*, a.name as host_name FROM events e JOIN alumni a ON e.host_id = a.id WHERE e.is_approved = 0 ORDER BY e.created_at DESC";
$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pending Events | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: #020617; color: #f8fafc; font-family: 'Inter', sans-serif; padding: 2rem; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border-radius: 1rem; border: 1px solid rgba(255,255,255,0.1); }
        .table { --bs-table-bg: transparent; --bs-table-color: #f8fafc; }
    </style>
</head>
<body>
    <div class="container glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Pending Event Requests</h2>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back</a>
        </div>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Event Title</th>
                    <th>Host</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['host_name']) ?></td>
                    <td><?= date('M j, Y', strtotime($row['event_date'])) ?></td>
                    <td class="text-end">
                        <a href="view_event_admin.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Review</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
