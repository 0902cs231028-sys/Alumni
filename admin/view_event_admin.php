<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { die("Invalid Event ID."); }

// Fetch event details with host information
$stmt = $conn->prepare("SELECT e.*, a.name as host_name, a.email as host_email 
                        FROM events e 
                        JOIN alumni a ON e.host_id = a.id 
                        WHERE e.id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) { die("Event not found."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Review Event | Nexus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: #020617; color: #f8fafc; font-family: 'Inter', sans-serif; padding: 40px 0; }
        .review-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body>
<div class="container">
    <div class="review-card p-5 shadow-lg">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Review Event Submission</h2>
            <a href="dashboard.php" class="btn btn-outline-light rounded-pill">Back</a>
        </div>

        <div class="mb-4">
            <label class="text-secondary small text-uppercase fw-bold">Event Title</label>
            <h3 class="text-primary fw-bold"><?= htmlspecialchars($event['title']) ?></h3>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="text-secondary small text-uppercase fw-bold">Host Identity</label>
                <p class="mb-0 fw-medium"><?= htmlspecialchars($event['host_name']) ?> (<?= htmlspecialchars($event['host_email']) ?>)</p>
            </div>
            <div class="col-md-6">
                <label class="text-secondary small text-uppercase fw-bold">Scheduled Date</label>
                <p class="mb-0 fw-medium"><?= date('F j, Y @ H:i', strtotime($event['event_date'])) ?></p>
            </div>
        </div>

        <div class="mb-5">
            <label class="text-secondary small text-uppercase fw-bold">Event Description</label>
            <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mt-2">
                <?= nl2br(htmlspecialchars($event['description'])) ?>
            </div>
        </div>

        <div class="d-flex gap-3">
            <a href="approve_event.php?id=<?= $id ?>&act=approve&token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-success px-5 rounded-pill fw-bold">Approve & Publish</a>
            <a href="approve_event.php?id=<?= $id ?>&act=delete&token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-danger px-5 rounded-pill fw-bold" onclick="return confirm('Reject and delete this event?')">Reject Submission</a>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>
