<?php
// Enhanced directory listing with basic styling
$dir = __DIR__;

function human_size($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    $units = ['KB','MB','GB','TB','PB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), $i > 1 ? 2 : 1) . ' ' . $units[$i - 1];
} // [web:429][web:432]

$items = [];
foreach (scandir($dir) as $f) {
    if ($f === '.' || $f === '..') continue;
    $path = $dir . DIRECTORY_SEPARATOR . $f;
    $isDir = is_dir($path);
    $items[] = [
        'name' => $f,
        'isDir' => $isDir,
        'size' => $isDir ? null : filesize($path),
        'mtime' => filemtime($path),
    ];
}
usort($items, function($a, $b) {
    // folders first, then by name
    if ($a['isDir'] !== $b['isDir']) return $a['isDir'] ? -1 : 1;
    return strcasecmp($a['name'], $b['name']);
}); // [web:420][web:432]
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Directory Listing</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            color: #e5e7eb;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        .wrap {
            max-width: 960px;
            margin: 32px auto;
        }
        .glass {
            border-radius: 18px;
            background: rgba(15, 23, 42, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.9),
                0 0 0 1px rgba(148, 163, 184, 0.25);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .table-dark > :not(caption) > * > * {
            border-bottom-color: rgba(148, 163, 184, 0.25);
        }
        a.file-link {
            color: #93c5fd;
            text-decoration: none;
        }
        a.file-link:hover {
            text-decoration: underline;
        }
        .badge-folder {
            background: rgba(34, 197, 94, 0.18);
            border: 1px solid rgba(34, 197, 94, 0.7);
            color: #bbf7d0;
        }
        .badge-file {
            background: rgba(59, 130, 246, 0.18);
            border: 1px solid rgba(59, 130, 246, 0.7);
            color: #bfdbfe;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="glass p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <small class="text-uppercase text-secondary fw-semibold">Utilities</small>
                <h2 class="mb-0">Directory Browser</h2>
                <p class="text-muted small mb-0">
                    Listing of files in <code><?= htmlspecialchars(basename($dir)) ?></code>.
                </p>
            </div>
        </div>

        <table class="table table-dark table-striped table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th class="text-end">Size</th>
                <th class="text-end">Modified</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        No files found in this directory.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['isDir']): ?>
                                📁 <?= htmlspecialchars($item['name']) ?>
                            <?php else: ?>
                                <a class="file-link" href="<?= rawurlencode($item['name']) ?>" target="_blank">
                                    <?= htmlspecialchars($item['name']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['isDir']): ?>
                                <span class="badge badge-folder">Folder</span>
                            <?php else: ?>
                                <span class="badge badge-file">File</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?= $item['isDir'] ? '—' : human_size($item['size']) ?>
                        </td>
                        <td class="text-end">
                            <?= date('Y-m-d H:i', $item['mtime']) ?>
                        </td>
                        <td class="text-end">
                            <?php if (!$item['isDir']): ?>
                                <a class="btn btn-sm btn-outline-light"
                                   href="<?= rawurlencode($item['name']) ?>" download>
                                    Download
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
  </div>
</body>
</html>
</div>
</body>
</html>