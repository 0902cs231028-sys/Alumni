<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/connection.php';

// 1. Pagination Configuration
$limit = 12; // Alumni per page
$page  = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$offset = ($page - 1) * $limit;

// 2. Build Secure Dynamic Query
$params = [];
$types  = "";
$where  = "approved = 1";

$filters = [
    'name'   => ['col' => 'name',   'op' => 'LIKE', 'val' => $_GET['name'] ?? ''],
    'batch'  => ['col' => 'batch',  'op' => '=',    'val' => $_GET['batch'] ?? ''],
    'branch' => ['col' => 'branch', 'op' => '=',    'val' => $_GET['branch'] ?? ''],
    'city'   => ['col' => 'city',   'op' => 'LIKE', 'val' => $_GET['city'] ?? '']
];

foreach ($filters as $key => $f) {
    if (!empty($f['val'])) {
        $where .= " AND {$f['col']} {$f['op']} ?";
        $params[] = ($f['op'] === 'LIKE') ? "%{$f['val']}%" : $f['val'];
        $types .= "s";
    }
}

// 3. Fetch Data with Prepared Statements
// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM alumni WHERE $where";
$c_stmt = $conn->prepare($count_sql);
if (!empty($params)) $c_stmt->bind_param($types, ...$params);
$c_stmt->execute();
$total_rows = $c_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// Get paginated results
$sql = "SELECT id, name, email, batch, branch, city, linkedin, profile_pic 
        FROM alumni WHERE $where ORDER BY name LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// 4. Fetch Filter Dropdowns
$batches  = mysqli_query($conn, "SELECT DISTINCT batch FROM alumni WHERE approved=1 AND batch<>'' ORDER BY batch DESC");
$branches = mysqli_query($conn, "SELECT DISTINCT branch FROM alumni WHERE approved=1 AND branch<>'' ORDER BY branch");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alumni Network | Professional Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --accent: #3b82f6; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .search-card { border-radius: 1rem; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .alumni-card { 
            border: none; border-radius: 1rem; transition: all 0.3s ease;
            background: #fff; overflow: hidden;
        }
        .alumni-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); }
        .profile-avatar {
            width: 70px; height: 70px; object-fit: cover; border-radius: 50%;
            border: 3px solid #f1f5f9;
        }
        .filter-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .pagination .page-link { border-radius: 0.5rem; margin: 0 3px; color: #475569; }
        .active > .page-link { background-color: var(--accent) !important; border-color: var(--accent) !important; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-slate-800 m-0">Alumni Directory</h2>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-light border"><i data-lucide="home" size="18"></i></a>
            <?php if(isset($_SESSION['alumni_id'])): ?>
                <a href="profile.php" class="btn btn-primary d-flex align-items-center gap-2">
                    <i data-lucide="user" size="18"></i> My Profile
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-primary">Join Network</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card search-card p-4 mb-5">
        <form method="GET" class="row g-3">
            <div class="col-lg-4">
                <label class="filter-label mb-2">Search Name</label>
                <input name="name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>" class="form-control" placeholder="Type a name...">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="filter-label mb-2">Batch</label>
                <select name="batch" class="form-select">
                    <option value="">All Years</option>
                    <?php while($b = mysqli_fetch_assoc($batches)): ?>
                        <option value="<?= $b['batch'] ?>" <?= (($_GET['batch']??'')==$b['batch'])?'selected':'' ?>><?= $b['batch'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="filter-label mb-2">Branch</label>
                <select name="branch" class="form-select">
                    <option value="">All Branches</option>
                    <?php while($br = mysqli_fetch_assoc($branches)): ?>
                        <option value="<?= $br['branch'] ?>" <?= (($_GET['branch']??'')==$br['branch'])?'selected':'' ?>><?= $br['branch'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="filter-label mb-2">Location</label>
                <input name="city" value="<?= htmlspecialchars($_GET['city'] ?? '') ?>" class="form-control" placeholder="City name">
            </div>
            <div class="col-md-2 col-lg-1 d-flex align-items-end">
                <button class="btn btn-primary w-100 py-2"><i data-lucide="search"></i></button>
            </div>
        </form>
    </div>

    <div class="row g-4">
        <?php if($res->num_rows === 0): ?>
            <div class="col-12 text-center py-5">
                <i data-lucide="search-x" size="48" class="text-muted mb-3"></i>
                <h4 class="text-secondary">No members found matching your search.</h4>
            </div>
        <?php else: ?>
            <?php while($r = $res->fetch_assoc()): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card alumni-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= $r['profile_pic'] ?: 'https://ui-avatars.com/api/?name='.urlencode($r['name']) ?>" 
                                 class="profile-avatar" alt="Profile">
                            <div class="overflow-hidden">
                                <h5 class="fw-bold mb-0 text-truncate"><?= htmlspecialchars($r['name']) ?></h5>
                                <div class="text-secondary small"><?= htmlspecialchars($r['branch']) ?> • Batch <?= htmlspecialchars($r['batch']) ?></div>
                                <div class="text-muted small"><i data-lucide="map-pin" size="12"></i> <?= htmlspecialchars($r['city']) ?></div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top d-flex gap-2">
                            <a href="view_alumni.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-light flex-grow-1">View Profile</a>
                            <?php if(!empty($r['linkedin'])): ?>
                                <a href="<?= htmlspecialchars($r['linkedin']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i data-lucide="linkedin" size="14"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <?php if($total_pages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i)?'active':'' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>''])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>lucide.createIcons();</script>
    </body>
</html>
</body>
</html>
