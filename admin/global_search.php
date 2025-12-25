<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../includes/connection.php';

if (empty($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');
$results = ['alumni' => [], 'posts' => []];

if (strlen($query) >= 2) {
    $term = "%$query%";
    
    // Search Alumni
    $stmt1 = $conn->prepare("SELECT id, name, batch FROM alumni WHERE name LIKE ? OR batch LIKE ? LIMIT 5");
    $stmt1->bind_param("ss", $term, $term);
    $stmt1->execute();
    $results['alumni'] = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);

    // Search Posts
    $stmt2 = $conn->prepare("SELECT id, title FROM posts WHERE title LIKE ? LIMIT 5");
    $stmt2->bind_param("s", $term);
    $stmt2->execute();
    $results['posts'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($results);
