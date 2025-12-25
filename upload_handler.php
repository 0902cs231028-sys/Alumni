<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

// 1. Authorization Check
$userId = $_SESSION['alumni_id'] ?? $_SESSION['admin_id'] ?? null;
$role = isset($_SESSION['admin_id']) ? 'admin' : 'alumni';

if (!$userId) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB Limit

    // 2. Defensive Validation
    if (!in_array($file['type'], $allowedTypes)) {
        die(json_encode(['success' => false, 'message' => 'Invalid file type. JPG, PNG, WEBP only.']));
    }
    if ($file['size'] > $maxSize) {
        die(json_encode(['success' => false, 'message' => 'File too large (Max 2MB).']));
    }

    // 3. Unique File Naming (Prevents overwriting)
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = $role . '_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $uploadPath = __DIR__ . '/uploads/profile_pics/' . $fileName;
    $dbPath = 'uploads/profile_pics/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // 4. Update Database
        $table = ($role === 'admin') ? 'admin' : 'alumni';
        $stmt = $pdo->prepare("UPDATE $table SET profile_pic = ? WHERE id = ?");
        
        if ($stmt->execute([$dbPath, $userId])) {
            echo json_encode(['success' => true, 'path' => $dbPath]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed. Check directory permissions.']);
    }
}
