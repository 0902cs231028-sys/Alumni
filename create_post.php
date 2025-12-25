<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/includes/connection.php';

// 1. Authorization & Security Setup
if (!isset($_SESSION['alumni_id'])) {
    header("Location: login.php");
    exit;
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$alumni_id = (int)$_SESSION['alumni_id'];
$status = ['type' => '', 'msg' => ''];

// 2. Handling the Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die("Security token expired. Please refresh.");
    }

    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');

    if ($title === '' || $body === '') {
        $status = ['type' => 'warning', 'msg' => 'Both title and content are required.'];
    } else {
        try {
            // 3. EXTREME SECURITY: Prepared Statements
            $stmt = $conn->prepare("INSERT INTO posts (alumni_id, title, body, approved, created_at) VALUES (?, ?, ?, 0, NOW())");
            $stmt->bind_param("iss", $alumni_id, $title, $body);
            
            if ($stmt->execute()) {
                $status = ['type' => 'success', 'msg' => 'Post submitted successfully! It will appear after admin review.'];
                // Script to clear localstorage on success is handled in JS below
            } else {
                throw new Exception($stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Post Creation Error: " . $e->getMessage());
            $status = ['type' => 'danger', 'msg' => 'A system error occurred. Please try again later.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compose Post | Alumni Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --glass-bg: rgba(255, 255, 255, 0.9); }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .editor-container { max-width: 800px; margin: 50px auto; }
        .post-card { border: none; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .form-control:focus { box-shadow: none; border-color: #0d6efd; }
        .char-count { font-size: 0.8rem; color: #6c757d; }
        .preview-area { background: #fff; border-left: 4px solid #0d6efd; display: none; }
    </style>
</head>
<body>

<div class="container editor-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i data-lucide="pen-tool" class="me-2"></i>Create New Post</h2>
        <a href="posts.php" class="btn btn-outline-secondary btn-sm rounded-pill">Cancel</a>
    </div>

    <?php if ($status['msg']): ?>
        <div class="alert alert-<?= $status['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= htmlspecialchars($status['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card post-card p-4">
        <form method="POST" id="postForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Title</label>
                <input class="form-control form-control-lg" name="title" id="postTitle" 
                       placeholder="Give your post a catchy headline..." required>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label fw-semibold">Content</label>
                    <span class="char-count" id="countDisplay">0 characters</span>
                </div>
                <textarea class="form-control" rows="10" name="body" id="postBody" 
                          placeholder="Share your thoughts, experiences, or updates with the community..." required></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i data-lucide="info" size="14"></i> Posts are moderated by admins.
                </div>
                <div class="btn-group">
                    <button type="button" id="previewBtn" class="btn btn-outline-primary">Preview</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Publish Post</button>
                </div>
            </div>
        </form>
    </div>

    <div id="previewContainer" class="card post-card p-4 mt-4 preview-area">
        <h4 id="previewTitle" class="fw-bold"></h4>
        <hr>
        <div id="previewBody" style="white-space: pre-wrap;"></div>
    </div>
</div>

<script>
    lucide.createIcons();

    const postTitle = document.getElementById('postTitle');
    const postBody = document.getElementById('postBody');
    const countDisplay = document.getElementById('countDisplay');
    const previewBtn = document.getElementById('previewBtn');
    const previewContainer = document.getElementById('previewContainer');

    // 1. Auto-Save Logic (LocalStorage)
    window.addEventListener('load', () => {
        if(localStorage.getItem('draft_title')) postTitle.value = localStorage.getItem('draft_title');
        if(localStorage.getItem('draft_body')) postBody.value = localStorage.getItem('draft_body');
        updateCount();
    });

    [postTitle, postBody].forEach(el => {
        el.addEventListener('input', () => {
            localStorage.setItem('draft_title', postTitle.value);
            localStorage.setItem('draft_body', postBody.value);
            updateCount();
        });
    });

    // Clear draft on successful submit
    <?php if ($status['type'] === 'success'): ?>
        localStorage.removeItem('draft_title');
        localStorage.removeItem('draft_body');
    <?php endif; ?>

    function updateCount() {
        countDisplay.innerText = `${postBody.value.length} characters`;
    }

    // 2. Preview Toggle
    previewBtn.addEventListener('click', () => {
        if(previewContainer.style.display === 'block') {
            previewContainer.style.display = 'none';
            previewBtn.innerText = 'Preview';
        } else {
            document.getElementById('previewTitle').innerText = postTitle.value || 'No Title';
            document.getElementById('previewBody').innerText = postBody.value || 'No Content';
            previewContainer.style.display = 'block';
            previewBtn.innerText = 'Hide Preview';
        }
    });
</script>
    </body>
</html>
</body>
</html>
