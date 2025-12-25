<?php
// 1. SECURITY: Turn off error display for production
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

declare(strict_types=1);
session_start();

// PRESERVED PATH: Database Connection
require __DIR__ . '/../includes/connection.php';

// 2. Guard Clause
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$adminName = $_SESSION['admin_username'] ?? 'Admin';
$today = date('Y-m-d'); 

/** * 3. ROBUST DATA FETCHING */
$statsQuery = "
    SELECT 
        COUNT(CASE WHEN approved = 0 THEN 1 END) as pending_alumni,
        COUNT(CASE WHEN approved = 1 THEN 1 END) as active_alumni,
        COUNT(CASE WHEN DATE(created_at) = '$today' THEN 1 END) as today_alumni,
        (SELECT COUNT(*) FROM posts WHERE approved = 0) as pending_posts,
        (SELECT COUNT(*) FROM posts WHERE approved = 1) as active_posts,
        (SELECT COUNT(*) FROM posts WHERE DATE(created_at) = '$today') as today_posts,
        (SELECT COUNT(*) FROM events WHERE is_approved = 0) as pending_events,
        (SELECT COUNT(*) FROM notifications WHERE seen = 0) as open_reports
    FROM alumni";

$result = mysqli_query($conn, $statsQuery);

if ($result) {
    $data = mysqli_fetch_assoc($result);
} else {
    // FALLBACK
    $data = [
        'pending_alumni' => 0, 'active_alumni' => 0, 'today_alumni' => 0,
        'pending_posts' => 0, 'active_posts' => 0, 'today_posts' => 0,
        'pending_events' => 0, 'open_reports' => 0
    ];
}

$alumni_growth = ($data['active_alumni'] > 0) 
    ? round(($data['today_alumni'] / $data['active_alumni']) * 100, 1) 
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nexus Admin | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-deep: #020617;
            --accent-blue: #3b82f6;
            --glass: rgba(30, 41, 59, 0.7);
        }
        body {
            background-color: var(--bg-deep);
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            background-image: radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0, transparent 50%),
                              radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0, transparent 50%);
        }
        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .metric-card:hover {
            border-color: var(--accent-blue);
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .stat-icon {
            padding: 12px;
            border-radius: 12px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-blue);
        }
        .progress { height: 6px; background: #1e293b; border-radius: 10px; }
        .trend-up { color: #10b981; font-size: 0.8rem; font-weight: 600; }
        .logout-btn { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        .logout-btn:hover { background: #ef4444; color: white; }
    </style>
</head>
<body>

<div class="container py-5">
    <header class="d-flex justify-content-between align-items-center mb-5">
    <div class="d-flex align-items-center gap-4">
        <div class="position-relative">
            <?php 
            // 5. SAFER PROFILE LOADING
            $admin_id = (int)$_SESSION['admin_id'];
            $current_pic = 'https://ui-avatars.com/api/?background=3b82f6&color=fff&name='.urlencode($adminName);
            
            $pic_query = "SELECT profile_pic FROM admin WHERE id = $admin_id LIMIT 1";
            $pic_res = mysqli_query($conn, $pic_query);
            
            if ($pic_res && mysqli_num_rows($pic_res) > 0) {
                $pic_row = mysqli_fetch_assoc($pic_res);
                if (!empty($pic_row['profile_pic'])) {
                    $current_pic = '../'.$pic_row['profile_pic'];
                }
            }
            ?>
            <img id="profilePreview" 
                 src="<?= htmlspecialchars($current_pic) ?>" 
                 class="rounded-circle border border-2 border-primary" 
                 style="width: 64px; height: 64px; object-fit: cover; cursor: pointer;">
            
            <label for="avatarInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                   style="width: 24px; height: 24px; cursor: pointer; border: 2px solid var(--bg-deep);">
                <i data-lucide="camera" style="width: 12px; height: 12px;"></i>
            </label>
            <input type="file" id="avatarInput" class="d-none" accept="image/*">
        </div>

        <div>
            <h1 class="h3 fw-bold mb-1">System Overview</h1>
            <p class="text-secondary mb-0">Operational status for <span class="text-white fw-medium"><?= date('l, F j') ?></span></p>
        </div>
    </div>

    <div class="d-flex gap-3 align-items-center">
        <div class="text-end d-none d-md-block me-2">
            <div class="fw-bold"><?= htmlspecialchars($adminName) ?></div>
            <div class="small text-secondary">System Superuser</div>
        </div>

        <button class="btn btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" 
                onclick="toggleSearch()" style="width: 40px; height: 40px;">
            <i data-lucide="search" style="width: 20px; height: 20px;"></i>
        </button>

        <a href="approve_alumni.php?action=logout" class="btn logout-btn px-4">Logout</a>
        </div>
    </header>
    

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <a href="pending_alumni.php" class="text-decoration-none">
                <div class="glass-card metric-card p-4 h-100">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="stat-icon"><i data-lucide="user-plus"></i></div>
                        <span class="badge rounded-pill bg-danger"><?= $data['pending_alumni'] ?> Urgent</span>
                    </div>
                    <h3 class="h2 fw-bold mb-1"><?= $data['pending_alumni'] ?></h3>
                    <p class="text-secondary small mb-0 text-uppercase letter-spacing">Pending Alumni</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6">
            <a href="pending_posts.php" class="text-decoration-none">
                <div class="glass-card metric-card p-4 h-100">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="stat-icon" style="color:#f59e0b; background:rgba(245,158,11,0.1);"><i data-lucide="file-text"></i></div>
                        <span class="badge rounded-pill bg-warning text-dark">Review</span>
                    </div>
                    <h3 class="h2 fw-bold mb-1"><?= $data['pending_posts'] ?></h3>
                    <p class="text-secondary small mb-0 text-uppercase letter-spacing">Queue Length</p>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="pending_events.php" class="text-decoration-none">
                <div class="glass-card metric-card p-4 h-100">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="stat-icon" style="color:#8b5cf6; background:rgba(139, 92, 246, 0.1);"><i data-lucide="calendar"></i></div>
                        <span class="badge rounded-pill bg-primary"><?= $data['pending_events'] ?> New</span>
                    </div>
                    <h3 class="h2 fw-bold mb-1"><?= $data['pending_events'] ?></h3>
                    <p class="text-secondary small mb-0 text-uppercase letter-spacing">Pending Events</p>
                </div>
            </a>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <a href="notifications.php" class="text-decoration-none">
                <div class="glass-card metric-card p-4 h-100">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="stat-icon" style="color:#ef4444; background:rgba(239,68,68,0.1);"><i data-lucide="shield-alert"></i></div>
                        <?php if($data['open_reports'] > 0): ?>
                            <span class="badge rounded-pill bg-danger animate-pulse">Alert</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="h2 fw-bold mb-1"><?= $data['open_reports'] ?></h3>
                    <p class="text-secondary small mb-0 text-uppercase letter-spacing">Security Reports</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between mb-3">
                    <div class="stat-icon" style="color:#10b981; background:rgba(16,185,129,0.1);"><i data-lucide="check-circle"></i></div>
                    <span class="trend-up">+<?= $data['today_posts'] ?> Today</span>
                </div>
                <h3 class="h2 fw-bold mb-1"><?= $data['active_posts'] ?></h3>
                <p class="text-secondary small mb-0 text-uppercase letter-spacing">Active Posts</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="glass-card p-4 h-100">
                <h5 class="fw-bold mb-4">Community Growth Momentum</h5>
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <div class="display-5 fw-bold"><?= $data['active_alumni'] ?></div>
                        <p class="text-secondary small">Total Verified Members</p>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small">Growth Velocity</span>
                            <span class="small fw-bold text-primary"><?= $alumni_growth ?>%</span>
                        </div>
                        <div class="progress mb-4">
                            <div class="progress-bar bg-primary" style="width: <?= $alumni_growth ?>%"></div>
                        </div>
                    </div>
                </div>
                <hr class="opacity-10">
                <div class="d-flex gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success rounded-circle" style="width:8px; height:8px;"></div>
                        <span class="small text-secondary"><?= $data['today_alumni'] ?> Joined Today</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary rounded-circle" style="width:8px; height:8px;"></div>
                        <span class="small text-secondary">Peak Activity: 14:00 - 16:00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3">Command Center</h5>
                <div class="list-group list-group-flush bg-transparent">
                    <a href="manage_posts.php" class="list-group-item bg-transparent text-white border-secondary px-0 d-flex justify-content-between align-items-center">
                        Post Inventory <i data-lucide="chevron-right" size="16"></i>
                    </a>
                    <a href="pending_alumni.php" class="list-group-item bg-transparent text-white border-secondary px-0 d-flex justify-content-between align-items-center">
                        Validation Queue <i data-lucide="chevron-right" size="16"></i>
                    </a>
                    <a href="#" class="list-group-item bg-transparent text-white border-0 px-0 d-flex justify-content-between align-items-center">
                        System Logs <i data-lucide="external-link" size="16"></i>
                    </a>
                </div>
                <button class="btn btn-primary w-100 mt-3 py-2 fw-bold shadow-sm">Generate Registry Report</button>
            </div>
        </div>
    </div>
</div>

<div id="searchOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" 
     style="background: rgba(2, 6, 23, 0.95); backdrop-filter: blur(15px); z-index: 9999;">
    <div class="container py-5">
        <div class="d-flex justify-content-between mb-4">
            <h2 class="fw-bold text-white">Global Nexus Search</h2>
            <button class="btn btn-link text-white text-decoration-none" onclick="toggleSearch()">
                <i data-lucide="x-circle" style="width: 32px; height: 32px;"></i>
            </button>
        </div>
        <input type="text" id="globalSearchInput" 
               class="form-control form-control-lg bg-transparent border-primary text-white py-3 mb-5 rounded-pill" 
               placeholder="Search names, batches, or post titles..." autocomplete="off">
        
        <div class="row g-4" id="searchResults">
            </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize Lucide Icons
    lucide.createIcons();
    
    // ----------------------------------------------------------------
    // 1. PROFILE UPLOAD LOGIC (With Error Handling)
    // ----------------------------------------------------------------
    document.getElementById('avatarInput').addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const preview = document.getElementById('profilePreview');
        const originalSrc = preview.src; 
        preview.style.opacity = '0.5';

        const formData = new FormData();
        formData.append('avatar', file);

        try {
            const response = await fetch('../upload_handler.php', {
                method: 'POST',
                body: formData
            });
            const responseText = await response.text();
            
            try {
                const result = JSON.parse(responseText);
                preview.style.opacity = '1';
                
                if (result.success) {
                    preview.src = '../' + result.path + '?v=' + Date.now();
                } else {
                    alert('Upload failed: ' + (result.message || 'Unknown error'));
                    preview.src = originalSrc;
                }
            } catch (jsonError) {
                console.error('Server returned invalid JSON:', responseText);
                alert('System Error. Check console.');
                preview.style.opacity = '1';
                preview.src = originalSrc;
            }

        } catch (err) {
            console.error('Network Error:', err);
            preview.style.opacity = '1';
            alert('Connection failed.');
        }
    });

    // ----------------------------------------------------------------
    // 2. SEARCH LOGIC (Corrected & Secured)
    // ----------------------------------------------------------------
    
    function toggleSearch() {
        const overlay = document.getElementById('searchOverlay');
        overlay.classList.toggle('d-none');
        if (!overlay.classList.contains('d-none')) {
            setTimeout(() => document.getElementById('globalSearchInput').focus(), 100);
        }
    }

    // SECURITY: XSS Sanitization Helper
    const escapeHtml = (unsafe) => {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    let debounceTimer; // PERFORMANCE: Timer variable

    document.getElementById('globalSearchInput').addEventListener('input', function(e) {
        const query = e.target.value;
        const resultsContainer = document.getElementById('searchResults');
        
        // Clear previous timer (Stops spamming server)
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }

        // Wait 300ms after typing stops before searching
        debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(`global_search.php?q=${encodeURIComponent(query)}`);
                
                // Handle non-JSON responses gracefully
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                
                let html = '';
                
                // Process Alumni Results
                if (data.alumni && data.alumni.length > 0) {
                    html += '<div class="col-md-6"><h5 class="text-primary mb-3">Alumni</h5>';
                    data.alumni.forEach(a => {
                        // FIX: escapeHtml() prevents XSS attacks
                        html += `<div class="glass-card p-3 mb-2 border-primary border-opacity-25">
                                    <a href="view_alumni.php?id=${a.id}" class="text-white text-decoration-none d-block">
                                    <strong>${escapeHtml(a.name)}</strong> <small class="text-secondary ms-2">Batch ${escapeHtml(a.batch)}</small></a>
                                 </div>`;
                    });
                    html += '</div>';
                }

                // Process Post Results
                if (data.posts && data.posts.length > 0) {
                    html += '<div class="col-md-6"><h5 class="text-warning mb-3">Posts</h5>';
                    data.posts.forEach(p => {
                        html += `<div class="glass-card p-3 mb-2 border-warning border-opacity-25">
                                    <a href="manage_posts.php?search=${p.id}" class="text-white text-decoration-none d-block">${escapeHtml(p.title)}</a>
                                 </div>`;
                    });
                    html += '</div>';
                }

                resultsContainer.innerHTML = html || '<div class="col-12 text-center text-muted">No matching records found.</div>';
            
            } catch (error) {
                console.error('Search error:', error);
                resultsContainer.innerHTML = '<div class="col-12 text-center text-danger">Error fetching results.</div>';
            }
        }, 300); // 300ms delay
    });

    // Auto-refresh metrics (Only if page is visible)
    setTimeout(() => {
        if (!document.hidden) location.reload();
    }, 60000);
</script>
    
</body>
</html>