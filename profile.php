<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

/**
 * Nexus Alumni Profile Controller
 * Level: Ultra-Extreme Professional
 */

// 1. Authorization & Secure Data Fetching
if (empty($_SESSION['alumni_id'])) {
    header("Location: login.php");
    exit;
}

$alumni_id = (int) $_SESSION['alumni_id'];
$status = ['type' => '', 'msg' => ''];

// 2. Atomic Profile Synchronization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $batch    = trim($_POST['batch'] ?? '');
    $branch   = trim($_POST['branch'] ?? '');

    if (empty($name)) {
        $status = ['type' => 'danger', 'msg' => 'Core identity name cannot be empty.'];
    } else {
        // Prepared Statement for Extreme Security
        $sql = "UPDATE alumni SET name=?, phone=?, city=?, linkedin=?, batch=?, branch=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssssi", $name, $phone, $city, $linkedin, $batch, $branch, $alumni_id);
            if (mysqli_stmt_execute($stmt)) {
                $status = ['type' => 'success', 'msg' => 'Profile synchronized with the global network.'];
            } else {
                $status = ['type' => 'danger', 'msg' => 'Synchronization failed. Check data constraints.'];
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// 3. Fetch Fresh Profile Data (Including new profile_pic column)
$res = mysqli_query($conn, "SELECT * FROM alumni WHERE id = $alumni_id LIMIT 1");
$user = mysqli_fetch_assoc($res);

// 4. Activity Statistics (Social Proof)
$post_res = mysqli_query($conn, "SELECT COUNT(*) FROM posts WHERE alumni_id = $alumni_id");
$total_posts = mysqli_fetch_row($post_res)[0] ?? 0;

// 5. Social Metrics: Unread Notifications
$notif_q = mysqli_query($conn, "SELECT COUNT(*) FROM notifications WHERE user_id = $alumni_id AND seen = 0");
$unread_notifs = mysqli_fetch_row($notif_q)[0] ?? 0;

// 6. Activity Feed: Latest Events (Requires 'events' table)
$event_res = mysqli_query($conn, "SELECT * FROM events WHERE is_approved = 1 ORDER BY event_date ASC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Hub | Alumni Nexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --p-blue: #3b82f6; --glass: rgba(15, 23, 42, 0.96); }
        body { 
            background: radial-gradient(circle at top, #0f172a 0, #020617 55%, #000 100%);
            color: #f8fafc; font-family: 'Inter', system-ui, sans-serif; min-height: 100vh;
        }
        .profile-shell { max-width: 1100px; margin: 3rem auto; padding: 0 1rem; }
        .glass-card { 
            background: var(--glass); border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 2.5rem; backdrop-filter: blur(25px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
        }
        /* Kinetic Avatar System */
        .avatar-container {
            width: 160px; height: 160px; margin: 0 auto; position: relative;
            padding: 6px; background: linear-gradient(135deg, var(--p-blue), #8b5cf6); border-radius: 50%;
        }
        .avatar-img { 
            width: 100%; height: 100%; object-fit: cover; border-radius: 50%; 
            border: 5px solid #020617; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .avatar-img:hover { transform: scale(1.03); filter: brightness(0.9); }
        .cam-overlay {
            position: absolute; bottom: 8px; right: 8px; background: var(--p-blue);
            width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; border: 4px solid #020617; cursor: pointer; color: white;
        }
        .stat-box { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 1.5rem; padding: 1.25rem; }
        .form-label { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; }
        .form-control { 
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); 
            color: white; border-radius: 1rem; padding: 0.85rem 1.25rem;
        }
        .form-control:focus { background: rgba(255,255,255,0.08); color: white; border-color: var(--p-blue); box-shadow: 0 0 0 4px rgba(59,130,246,0.2); }
    </style>
</head>
<body>

<div class="profile-shell">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">Nexus Profile</h2>
            <p class="text-secondary small mb-0">Manage your professional presence and visibility</p>
        </div>
        <div class="d-flex gap-2">
            <a href="directory.php" class="btn btn-outline-light rounded-pill px-4">Directory</a>
            <a href="logout.php" class="btn btn-danger rounded-pill px-4">Logout</a>
        </div>
    </div>
<div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">Nexus Profile</h2>
            <p class="text-secondary small mb-0">Manage your professional presence and visibility</p>
        </div>
        <div class="d-flex gap-2">
            <a href="directory.php" class="btn btn-outline-light rounded-pill px-4">Directory</a>
            <a href="logout.php" class="btn btn-danger rounded-pill px-4">Logout</a>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="glass-card p-4 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-4">
                    <div class="text-center">
                        <div class="h5 fw-bold mb-0 text-primary"><?= $unread_notifs ?></div>
                        <small class="text-secondary text-uppercase" style="font-size: 0.6rem;">New Alerts</small>
                    </div>
                    <div class="text-center">
                        <div class="h5 fw-bold mb-0 text-success">Active</div>
                        <small class="text-secondary text-uppercase" style="font-size: 0.6rem;">Status</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary rounded-pill btn-sm px-4" data-bs-toggle="modal" data-bs-target="#hostEventModal">
                        <i data-lucide="calendar-plus" size="16" class="me-1"></i> Host Event
                    </button>
                    <a href="notifications_alumni.php" class="btn btn-outline-light rounded-pill btn-sm px-4 position-relative">
                        <i data-lucide="bell" size="16"></i>
                        <?php if($unread_notifs > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 mb-5">
        <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i data-lucide="zap" class="text-warning" size="20"></i> Community Hub
        </h5>
        <div class="row g-3">
            <?php if ($event_res && mysqli_num_rows($event_res) > 0): ?>
                <?php while($ev = mysqli_fetch_assoc($event_res)): ?>
                    <div class="col-md-4">
                        <div class="glass-card p-3 border-start border-primary border-4">
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($ev['title']) ?></h6>
                            <p class="small text-secondary mb-2"><?= date('M d, Y @ H:i', strtotime($ev['event_date'])) ?></p>
                            <button class="btn btn-sm btn-link text-primary p-0 text-decoration-none">RSVP Now →</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12"><p class="text-muted small">No upcoming events hosted by alumni yet.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
    ```

---

### Step 3: Add the "Host Event" Modal
Since you are using a button with `data-bs-target="#hostEventModal"`, you must add the Modal code before your `</body>` tag so the button actually opens the popup.

```html
<div class="modal fade" id="hostEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold">Host a Community Event</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="host_event.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Event Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Batch 2020 Reunion" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event Date & Time</label>
                        <input type="datetime-local" name="event_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Tell us about the event..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Submit for Approval</button>
                </div>
            </form>
        </div>
    </div>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-card p-5 text-center">
                <div class="avatar-container mb-4">
                    <img id="profilePreview" 
                         src="<?= !empty($user['profile_pic']) ? $user['profile_pic'] : 'https://ui-avatars.com/api/?background=3b82f6&color=fff&size=200&name='.urlencode($user['name']) ?>" 
                         class="avatar-img">
                    <label for="avatarInput" class="cam-overlay">
                        <i data-lucide="camera" size="20"></i>
                    </label>
                    <input type="file" id="avatarInput" class="d-none" accept="image/*">
                </div>
                
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h3>
                <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-4">
                    Verified Alumni
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="h4 fw-bold mb-0"><?= $total_posts ?></div>
                            <div class="small text-secondary">Posts</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="h4 fw-bold mb-0">12k</div>
                            <div class="small text-secondary">Reach</div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="own_posts.php" class="btn btn-primary rounded-pill fw-bold py-2">My Content Dashboard</a>
                    <a href="view_alumni.php?id=<?= $alumni_id ?>" class="btn btn-outline-light rounded-pill py-2">Public Preview</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card p-4 p-md-5">
                <?php if ($status['msg']): ?> 
                    <div class="alert alert-<?= $status['type'] ?> border-0 small py-3 mb-4 animate-in"><?= $status['msg'] ?></div> 
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label mb-2">Display Name</label>
                        <input name="name" value="<?= htmlspecialchars($user['name']) ?>" class="form-control" placeholder="Full legal name" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label mb-2">Academic Batch</label>
                            <input name="batch" value="<?= htmlspecialchars($user['batch']) ?>" class="form-control" placeholder="e.g. 2024">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2">Major / Branch</label>
                            <input name="branch" value="<?= htmlspecialchars($user['branch']) ?>" class="form-control" placeholder="e.g. Computer Science">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label mb-2">Phone Identifier</label>
                            <input name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="form-control" placeholder="+91 00000 00000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2">Current Residence (City)</label>
                            <input name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" class="form-control" placeholder="Global Location">
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label mb-2">LinkedIn Authority URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-secondary pe-0"><i data-lucide="linkedin" size="18"></i></span>
                            <input name="linkedin" value="<?= htmlspecialchars($user['linkedin'] ?? '') ?>" class="form-control border-start-0 ps-2" placeholder="https://linkedin.com/in/username">
                        </div>
                    </div>

                    <button class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-lg" type="submit">
                        <i data-lucide="save" size="20" class="me-2"></i> Synchronize All Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    // 5. ASYCHRONOUS AVATAR UPLOAD SYSTEM
    document.getElementById('avatarInput').addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Visual feedback during upload
        document.getElementById('profilePreview').style.opacity = '0.5';

        const formData = new FormData();
        formData.append('avatar', file);

        try {
            const response = await fetch('upload_handler.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            document.getElementById('profilePreview').style.opacity = '1';

            if (result.success) {
                // Cache-busting prevents old image from showing
                document.getElementById('profilePreview').src = result.path + '?v=' + Date.now();
                if(window.NexusCore) window.NexusCore.UI.toast('Identity visual updated!');
            } else {
                alert('Upload Error: ' + result.message);
            }
        } catch (err) {
            console.error('Core upload failure:', err);
            document.getElementById('profilePreview').style.opacity = '1';
        }
    });
</script>
</body>
</html>
