<?php
session_start();
include 'includes/connection.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: posts.php'); exit; }

// fetch comment info
$r = mysqli_query($conn, "SELECT c.alumni_id AS comment_owner, p.alumni_id AS post_owner
                          FROM comments c
                          LEFT JOIN posts p ON c.post_id = p.id
                          WHERE c.id = $id LIMIT 1");
if (!$r || mysqli_num_rows($r) == 0) { header('Location: posts.php'); exit; }
$row = mysqli_fetch_assoc($r);
$comment_owner = intval($row['comment_owner']);
$post_owner = intval($row['post_owner']);

$allowed = false;
if (isset($_SESSION['admin_id'])) $allowed = true;
if (isset($_SESSION['alumni_id']) && intval($_SESSION['alumni_id']) === $comment_owner) $allowed = true;
if (isset($_SESSION['alumni_id']) && intval($_SESSION['alumni_id']) === $post_owner) $allowed = true;

if ($allowed) {
    mysqli_query($conn, "DELETE FROM comments WHERE id = $id");
}
$back = $_SERVER['HTTP_REFERER'] ?? 'posts.php';
header("Location: $back");
exit;