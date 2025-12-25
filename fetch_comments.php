<?php
declare(strict_types=1);
require __DIR__ . '/includes/connection.php';

// 1. Strict Request Validation
$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);

if (!$post_id || $post_id <= 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

/** * 2. PERFORMANCE: ETag Caching
 * This prevents the server from sending the same data twice if no new comments exist.
 */
$hash_sql = "SELECT MAX(updated_at), COUNT(id) FROM comments WHERE post_id = ?"; 
// Note: Assuming 'updated_at' exists; if not, use 'created_at'
$h_stmt = $conn->prepare("SELECT COUNT(id), MAX(id) FROM comments WHERE post_id = ?");
$h_stmt->bind_param("i", $post_id);
$h_stmt->execute();
$meta = $h_stmt->get_result()->fetch_row();
$etag = md5($post_id . $meta[0] . $meta[1]);
$h_stmt->close();

header("ETag: \"$etag\"");
header("Cache-Control: public, max-age=5"); // 5-second browser cache

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
    http_response_code(304);
    exit;
}

// 3. Structured Data Retrieval
$sql = "SELECT c.id, c.content, c.created_at, c.alumni_id, a.name
        FROM comments AS c
        LEFT JOIN alumni AS a ON c.alumni_id = a.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query failure']);
    exit;
}

$stmt->bind_param("i", $post_id);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = [
        'id'         => (int)$r['id'],
        'content'    => $r['content'],
        'created_at' => $r['created_at'],
        'alumni_id'  => $r['alumni_id'] !== null ? (int)$r['alumni_id'] : null,
        'name'       => $r['name'] ?? 'Unknown Member'
    ];
}

$stmt->close();

// 4. Clean Output Execution
header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
exit;
