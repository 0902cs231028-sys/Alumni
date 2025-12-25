<?php
session_start();
include 'includes/connection.php';
if (!isset($_SESSION['alumni_id'])) {
    header("Location: login.php");
    exit;
}
$alumni_id = intval($_SESSION['alumni_id']);
if (isset($_GET['delete'])) {
    $pid = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM posts WHERE id=$pid AND alumni_id=$alumni_id");
    header("Location: own_posts.php");
    exit;
}
$res = mysqli_query($conn, "SELECT id, title, body, approved, created_at FROM posts WHERE alumni_id=$alumni_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>My Posts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <h3>My Posts</h3>
            <div class="top-controls">
                <a href="create_post.php" class="btn btn-sm btn-outline-primary">Create New</a>
                <a href="profile.php" class="btn btn-sm btn-outline-secondary">Back to Profile</a>
                <button id="themeToggle" class="btn btn-sm btn-outline-primary">Dark</button>
            </div>
        </div>

        <?php if (!$res || mysqli_num_rows($res) == 0): ?>
        <div class="alert alert-info mt-3">You have not created any posts yet.</div>
        <?php else: ?>
        <?php while($p = mysqli_fetch_assoc($res)): ?>
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($p['title']) ?></h5>
                <p class="card-text"><?= nl2br(htmlspecialchars($p['body'])) ?></p>
                <p class="small text-muted">Status:
                    <?= $p['approved']==1 ? '<span class="text-success">Approved</span>' : '<span class="text-warning">Pending approval</span>' ?>
                    — <?= htmlspecialchars($p['created_at']) ?>
                </p>
                <?php if($p['approved']==0): ?>
                <a href="own_posts.php?delete=<?= intval($p['id']) ?>" class="btn btn-sm btn-danger"
                    onclick="return confirm('Delete this post?')">Delete</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <script src="js/app.js"></script>
</body>

</html>