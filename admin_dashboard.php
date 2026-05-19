<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------------
// POST HANDLERS
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Upload deliverable file
    if (isset($_POST['upload_file_btn'])) {
        $pid          = intval($_POST['project_id']);
        $display_name = mysqli_real_escape_string($conn, trim($_POST['display_name']));
        $ver_q        = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_deliverables WHERE project_id='$pid'"));
        $version      = ($ver_q['c'] ?? 0) + 1;

        if (isset($_FILES['deliverable_file']) && $_FILES['deliverable_file']['error'] === 0) {
            $upload_dir = 'uploads/deliverables/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext       = strtolower(pathinfo($_FILES['deliverable_file']['name'], PATHINFO_EXTENSION));
            // Whitelist allowed file types
            $allowed_ext   = ['jpg','jpeg','png','gif','webp','pdf','zip','mp4'];
            $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','application/zip','video/mp4'];
            $finfo         = finfo_open(FILEINFO_MIME_TYPE);
            $mime          = finfo_file($finfo, $_FILES['deliverable_file']['tmp_name']);
            finfo_close($finfo);
            // Max 20 MB
            $max_size = 20 * 1024 * 1024;
            if (!in_array($ext, $allowed_ext) || !in_array($mime, $allowed_mimes) || $_FILES['deliverable_file']['size'] > $max_size) {
                header("Location: admin_dashboard.php?msg=invalid_file&open=$pid"); exit();
            }
            $safe_name = 'project_' . $pid . '_v' . $version . '_' . time() . '.' . $ext;
            $dest      = $upload_dir . $safe_name;
            if (move_uploaded_file($_FILES['deliverable_file']['tmp_name'], $dest)) {
                $fp   = mysqli_real_escape_string($conn, $dest);
                $dn   = $display_name ?: mysqli_real_escape_string($conn, $_FILES['deliverable_file']['name']);
                mysqli_query($conn, "INSERT INTO tbl_deliverables (project_id, file_path, display_name, version) VALUES ('$pid','$fp','$dn','$version')");
            }
        }
        header("Location: admin_dashboard.php?msg=file_uploaded&open=$pid"); exit();
    }

    // Create / update invoice
    if (isset($_POST['save_invoice_btn'])) {
        $pid      = intval($_POST['project_id']);
        $total    = floatval($_POST['total_amount']);
        $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM tbl_invoices WHERE project_id='$pid' LIMIT 1"));
        if ($existing) {
            mysqli_query($conn, "UPDATE tbl_invoices SET total_amount='$total', due_date='$due_date' WHERE project_id='$pid'");
        } else {
            mysqli_query($conn, "INSERT INTO tbl_invoices (project_id, total_amount, due_date, status) VALUES ('$pid','$total','$due_date','unpaid')");
        }
        header("Location: admin_dashboard.php?msg=invoice_saved&open=$pid"); exit();
    }

    // Record a payment
    if (isset($_POST['record_payment_btn'])) {
        $pid    = intval($_POST['project_id']);
        $amount = floatval($_POST['payment_amount']);
        $method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $inv    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, total_amount, amount_paid FROM tbl_invoices WHERE project_id='$pid' LIMIT 1"));
        if ($inv) {
            $inv_id     = $inv['id'];
            $new_paid   = $inv['amount_paid'] + $amount;
            $new_status = ($new_paid >= $inv['total_amount']) ? 'paid' : 'partial';
            mysqli_query($conn, "UPDATE tbl_invoices SET amount_paid='$new_paid', status='$new_status' WHERE id='$inv_id'");
            mysqli_query($conn, "INSERT INTO tbl_payments (invoice_id, amount, payment_method) VALUES ('$inv_id','$amount','$method')");
            mysqli_query($conn, "UPDATE tbl_projects SET payment_status='$new_status' WHERE id='$pid'");
        }
        header("Location: admin_dashboard.php?msg=payment_recorded&open=$pid"); exit();
    }
}

// ---------------------------------------------------------------
// FILTERS
// ---------------------------------------------------------------
$where_clauses   = [];
$search          = "";
$priority_filter = "";

if (isset($_GET['filter_btn'])) {
    if (!empty($_GET['search'])) {
        $search          = mysqli_real_escape_string($conn, $_GET['search']);
        $where_clauses[] = "(u.full_name LIKE '%$search%' OR p.title LIKE '%$search%')";
    }
    if (!empty($_GET['priority']) && $_GET['priority'] !== 'All') {
        $priority_filter = mysqli_real_escape_string($conn, $_GET['priority']);
        $where_clauses[] = "p.priority = '$priority_filter'";
    }
}
$where_sql = count($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// ---------------------------------------------------------------
// GLOBAL STATS
// ---------------------------------------------------------------
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tbl_projects WHERE status='pending'"))['t'] ?? 0;
$urgent_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tbl_projects WHERE priority='High'"))['t'] ?? 0;
$revenue       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount_paid),0) as t FROM tbl_invoices"))['t'] ?? 0;
$client_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT id) as t FROM tbl_users WHERE role='client'"))['t'] ?? 0;

// ---------------------------------------------------------------
// CHART DATA — project count per month, last 6 months
// ---------------------------------------------------------------
$chart_labels = [];
$chart_data   = [];
for ($i = 5; $i >= 0; $i--) {
    $month_start    = date('Y-m-01', strtotime("-$i months"));
    $month_end      = date('Y-m-t',  strtotime("-$i months"));
    $cq             = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tbl_projects WHERE created_at BETWEEN '$month_start 00:00:00' AND '$month_end 23:59:59'"));
    $chart_labels[] = date('M Y', strtotime("-$i months"));
    $chart_data[]   = (int)($cq['t'] ?? 0);
}

// ---------------------------------------------------------------
// FETCH ALL PROJECTS for table + modals (one query, stored in array)
// ---------------------------------------------------------------
$query    = "SELECT p.*, u.full_name, u.email FROM tbl_projects p JOIN tbl_users u ON p.client_id = u.id $where_sql ORDER BY p.created_at DESC";
$result   = mysqli_query($conn, $query);
$projects = [];
while ($r = mysqli_fetch_assoc($result)) {
    $projects[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdHub | Admin Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --adhub-bg-deep: #0a121d;
            --adhub-bg-top:  #132238;
            --adhub-accent:  #1b3252;
            --adhub-border:  rgba(255,255,255,0.18);
        }
        body            { font-family:'Inter',sans-serif; background:radial-gradient(circle at top center,var(--adhub-bg-top) 0%,var(--adhub-bg-deep) 100%); color:#fff; min-height:100vh; }
        .navbar         { background-color:var(--adhub-bg-top); border-bottom:1px solid var(--adhub-border); }
        .card           { background:rgba(255,255,255,0.03); border:1px solid var(--adhub-border); color:#fff; border-radius:12px; }
        .table          { color:#cbd5e0; border-color:var(--adhub-border); }
        .form-control, .form-select { background-color:#1a2a44 !important; border:1px solid var(--adhub-border) !important; color:#fff !important; }
        .form-control::placeholder  { color:rgba(255,255,255,0.5) !important; }
        .form-select option         { background:#132238; color:#fff; }
        .btn-adhub      { background-color:var(--adhub-accent); border:1px solid var(--adhub-border); color:#fff; border-radius:8px; font-weight:600; }
        .btn-adhub:hover{ background-color:#23406a; color:#fff; }
        .status-badge   { font-size:0.65rem; padding:4px 10px; border-radius:20px; font-weight:700; text-transform:uppercase; }
        .bg-revision    { background-color:#d9534f; color:#fff; }
        /* Modal */
        .modal-content  { background-color:#16263d; border:1px solid var(--adhub-border); color:#fff; }
        .modal-content label { color:#fff !important; }
        /* Chat */
        .activity-feed  { height:220px; overflow-y:auto; background:rgba(0,0,0,0.25); border-radius:10px; padding:12px; border:1px solid var(--adhub-border); }
        .msg-bubble     { background:#1e2e42; padding:10px 14px; border-radius:8px; border-left:3px solid #3182ce; margin-bottom:8px; font-size:0.875rem; }
        .msg-bubble.me  { background:rgba(255,255,255,0.07); border-left:none; border-right:3px solid #718096; text-align:right; }
        /* Invoice / billing */
        .billing-card   { background:linear-gradient(135deg,rgba(49,130,206,0.1) 0%,rgba(0,0,0,0.4) 100%); border:1px solid rgba(49,130,206,0.3) !important; border-radius:10px; }
        .section-label  { font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:rgba(255,255,255,0.45); margin-bottom:.5rem; display:block; }
        /* File item */
        .file-item      { background:rgba(255,255,255,0.04); border:1px solid var(--adhub-border); border-radius:8px; padding:10px 14px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-5 py-3 sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">ADHUB <span class="fw-light">| Admin Panel</span></a>
        <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid px-5 py-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">

            <!-- ALERT -->
            <?php if (isset($_GET['msg'])): ?>
            <?php
            $is_error_msg = ($_GET['msg'] === 'invalid_file');
            $alert_style  = $is_error_msg
                ? 'background:rgba(220,53,69,0.2);color:#f87171;'
                : 'background:rgba(92,184,92,0.2);color:#5cb85c;';
            ?>
            <div class="alert alert-dismissible fade show border-0 mb-4" role="alert" style="<?php echo $alert_style; ?>">
                <?php
                $alert_msgs = [
                    'updated'          => '<strong>Saved!</strong> Project updated.',
                    'deleted'          => '<strong>Removed!</strong> Project deleted.',
                    'file_uploaded'    => '<strong>Uploaded!</strong> File sent to client.',
                    'invoice_saved'    => '<strong>Invoice saved!</strong>',
                    'payment_recorded' => '<strong>Payment recorded!</strong>',
                    'invalid_file'     => '<strong>Upload rejected.</strong> Only JPG, PNG, GIF, WEBP, PDF, ZIP, MP4 files under 20MB are allowed.',
                ];
                echo $alert_msgs[htmlspecialchars($_GET['msg'])] ?? '';
                ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="row g-3 mb-5 text-center">
                <div class="col-md-3">
                    <div class="card p-3 border-info">
                        <small class="text-info fw-bold text-uppercase">Pending</small>
                        <h3 class="fw-bold mb-0"><?php echo $pending_count; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 border-danger">
                        <small class="text-danger fw-bold text-uppercase">Urgent</small>
                        <h3 class="fw-bold mb-0"><?php echo $urgent_count; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 border-success">
                        <small class="text-success fw-bold text-uppercase">Revenue Collected</small>
                        <h3 class="fw-bold mb-0">$<?php echo number_format($revenue, 2); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 border-primary">
                        <small class="text-primary fw-bold text-uppercase">Clients</small>
                        <h3 class="fw-bold mb-0"><?php echo $client_count; ?></h3>
                    </div>
                </div>
            </div>

            <!-- CHART -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-4">Project Activity Trend</h5>
                        <canvas id="projectChart" style="max-height:250px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- FILTER + TABLE HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h2 class="fw-bold mb-0">Project Management</h2>
                <form action="admin_dashboard.php" method="GET" class="d-flex gap-2 flex-wrap">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search client or title..." style="width:240px;" value="<?php echo htmlspecialchars($search); ?>">
                    <select name="priority" class="form-select form-select-sm" style="width:140px;">
                        <option value="All">All Priority</option>
                        <option value="High" <?php echo ($priority_filter === 'High') ? 'selected' : ''; ?>>High</option>
                        <option value="Low"  <?php echo ($priority_filter === 'Low')  ? 'selected' : ''; ?>>Low</option>
                    </select>
                    <button type="submit" name="filter_btn" class="btn btn-adhub btn-sm px-3">Filter</button>
                    <?php if (isset($_GET['filter_btn'])): ?>
                        <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- PROJECTS TABLE -->
            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead class="small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Client</th>
                                    <th class="py-3">Project</th>
                                    <th class="py-3 text-center">Priority</th>
                                    <th class="py-3">Budget</th>
                                    <th class="py-3">Status</th>
                                    <th class="text-end pe-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (count($projects) > 0): ?>
                                <?php foreach ($projects as $row): ?>
                                <?php
                                    $badge_class = match($row['status']) {
                                        'pending'     => 'bg-secondary',
                                        'in-progress' => 'bg-warning text-dark',
                                        'revision'    => 'bg-revision',
                                        'completed'   => 'bg-success',
                                        default       => 'bg-primary'
                                    };
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-white"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div class="small text-white-50"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td class="fw-bold text-white"><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo ($row['priority'] === 'High') ? 'danger' : 'info'; ?>">
                                            <?php echo strtoupper($row['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-white">$<?php echo number_format($row['budget'], 2); ?></td>
                                    <td><span class="status-badge <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span></td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-adhub" data-bs-toggle="modal" data-bs-target="#modal<?php echo $row['id']; ?>">Manage</button>
                                        <a href="report.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-light ms-1" target="_blank">Report</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-white-50">No projects found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- end col -->
    </div><!-- end row -->
</div><!-- end container -->

<!-- ================================================================
     MODALS — rendered outside the table to avoid broken HTML
     ================================================================ -->
<?php foreach ($projects as $row):
    $p_id    = $row['id'];
    $inv     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_invoices WHERE project_id='$p_id' LIMIT 1"));
    $balance = $inv ? round($inv['total_amount'] - $inv['amount_paid'], 2) : 0;
    $msgs_q  = mysqli_query($conn, "SELECT m.*, u2.full_name AS sender_name, u2.role AS sender_role FROM tbl_messages m JOIN tbl_users u2 ON u2.id = m.sender_id WHERE m.project_id = '$p_id' ORDER BY m.id ASC");
    $files_q = mysqli_query($conn, "SELECT * FROM tbl_deliverables WHERE project_id = '$p_id' ORDER BY uploaded_at DESC");
    $pays_q  = $inv ? mysqli_query($conn, "SELECT * FROM tbl_payments WHERE invoice_id = '{$inv['id']}' ORDER BY paid_at DESC") : null;
?>
<div class="modal fade" id="modal<?php echo $p_id; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><?php echo htmlspecialchars($row['title']); ?></h5>
                    <small class="text-white-50"><?php echo htmlspecialchars($row['full_name']); ?> &mdash; <?php echo htmlspecialchars($row['email']); ?></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body (scrollable) -->
            <div class="modal-body p-4">
                <div class="row g-4">

                    <!-- ===== LEFT: Status + Brief + Chat ===== -->
                    <div class="col-lg-5 border-end border-white border-opacity-10 pe-lg-4">

                        <!-- Project Status -->
                        <span class="section-label">Project Status</span>
                        <form action="update_project.php" method="POST" class="mb-4">
                            <input type="hidden" name="project_id" value="<?php echo $p_id; ?>">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-white-50 mb-1">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="pending"     <?php if ($row['status'] === 'pending')     echo 'selected'; ?>>Pending</option>
                                        <option value="in-progress" <?php if ($row['status'] === 'in-progress') echo 'selected'; ?>>In Progress</option>
                                        <option value="revision"    <?php if ($row['status'] === 'revision')    echo 'selected'; ?>>Revision</option>
                                        <option value="completed"   <?php if ($row['status'] === 'completed')   echo 'selected'; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small text-white-50 mb-1">Payment</label>
                                    <select name="payment_status" class="form-select form-select-sm">
                                        <option value="unpaid"  <?php if ($row['payment_status'] === 'unpaid')  echo 'selected'; ?>>Unpaid</option>
                                        <option value="partial" <?php if ($row['payment_status'] === 'partial') echo 'selected'; ?>>Partial</option>
                                        <option value="paid"    <?php if ($row['payment_status'] === 'paid')    echo 'selected'; ?>>Paid</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="small text-white-50 mb-1">Progress (%)</label>
                                    <input type="number" name="progress_percent" class="form-control form-control-sm" min="0" max="100" value="<?php echo (int)$row['progress_percent']; ?>">
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_btn" class="btn btn-adhub btn-sm px-4">Save Status</button>
                                <a href="delete_project.php?id=<?php echo $p_id; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this project permanently?');">Delete</a>
                            </div>
                        </form>

                        <!-- Project Brief -->
                        <span class="section-label">Project Brief</span>
                        <div class="mb-4 p-3 rounded" style="background:rgba(255,255,255,0.04);border:1px solid var(--adhub-border);">
                            <p class="small mb-0 text-white">
                                <?php echo $row['description'] ? nl2br(htmlspecialchars($row['description'])) : '<span class="text-white-50">No brief provided.</span>'; ?>
                            </p>
                        </div>

                        <!-- Live Discussion -->
                        <span class="section-label">Live Discussion</span>
                        <div class="activity-feed mb-2" id="adminFeed<?php echo $p_id; ?>">
                            <?php if (mysqli_num_rows($msgs_q) === 0): ?>
                                <p class="text-white-50 small mb-0">No messages yet.</p>
                            <?php endif; ?>
                            <?php while ($m = mysqli_fetch_assoc($msgs_q)):
                                $isAdmin = ($m['sender_role'] === 'admin'); ?>
                            <div class="msg-bubble <?php echo $isAdmin ? 'me' : ''; ?>">
                                <small class="d-block fw-bold mb-1 <?php echo $isAdmin ? 'text-info' : 'text-success'; ?>" style="font-size:0.65rem;">
                                    <?php echo $isAdmin ? 'YOU (Admin)' : htmlspecialchars($m['sender_name']); ?>
                                    <span class="text-white-50 fw-normal ms-1"><?php echo date('M d, H:i', strtotime($m['sent_at'])); ?></span>
                                </small>
                                <?php echo htmlspecialchars($m['message']); ?>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="text" id="adminMsgInput<?php echo $p_id; ?>" class="form-control" placeholder="Type a message to the client...">
                            <button class="btn btn-adhub px-3" onclick="adminSendMessage(<?php echo $p_id; ?>)">Send</button>
                        </div>

                    </div><!-- end left col -->

                    <!-- ===== RIGHT: Deliverables + Invoice + Payment ===== -->
                    <div class="col-lg-7">

                        <!-- Deliverables List -->
                        <span class="section-label">Deliverables Sent to Client</span>
                        <div class="mb-3">
                            <?php if (mysqli_num_rows($files_q) === 0): ?>
                                <div class="text-center py-3 rounded mb-2" style="background:rgba(255,255,255,0.03);border:1px dashed var(--adhub-border);">
                                    <small class="text-white-50">No files uploaded yet.</small>
                                </div>
                            <?php else: ?>
                                <?php while ($f = mysqli_fetch_assoc($files_q)): ?>
                                <div class="file-item d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="badge bg-danger me-2">v<?php echo $f['version']; ?></span>
                                        <span class="small fw-bold text-white"><?php echo htmlspecialchars($f['display_name']); ?></span>
                                        <div class="small text-white-50"><?php echo date('M d, Y H:i', strtotime($f['uploaded_at'])); ?></div>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($f['file_path']); ?>" class="btn btn-sm btn-outline-light px-3" download>Download</a>
                                </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Upload / Send File -->
                        <span class="section-label">Send File to Client</span>
                        <div class="mb-4 p-3 rounded" style="background:rgba(255,255,255,0.04);border:1px solid var(--adhub-border);">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="project_id" value="<?php echo $p_id; ?>">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <label class="small text-white-50 mb-1">Display Name</label>
                                        <input type="text" name="display_name" class="form-control form-control-sm" placeholder="e.g. Final Poster v2">
                                    </div>
                                    <div class="col-md-7">
                                        <label class="small text-white-50 mb-1">File (PDF, PNG, JPG, ZIP)</label>
                                        <input type="file" name="deliverable_file" class="form-control form-control-sm" accept=".pdf,.png,.jpg,.jpeg,.zip" required>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" name="upload_file_btn" class="btn btn-primary btn-sm px-4">📤 Upload &amp; Send</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Invoice Maker -->
                        <span class="section-label">Invoice Maker</span>
                        <div class="billing-card p-3 mb-3">
                            <form method="POST">
                                <input type="hidden" name="project_id" value="<?php echo $p_id; ?>">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="small text-white-50 mb-1">Total Amount ($)</label>
                                        <input type="number" name="total_amount" class="form-control form-control-sm" step="0.01" min="0"
                                            value="<?php echo $inv ? $inv['total_amount'] : $row['budget']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-white-50 mb-1">Due Date</label>
                                        <input type="date" name="due_date" class="form-control form-control-sm"
                                            value="<?php echo ($inv && $inv['due_date']) ? $inv['due_date'] : ''; ?>">
                                    </div>
                                </div>
                                <?php if ($inv): ?>
                                <div class="row g-2 mb-3 text-center">
                                    <div class="col-4">
                                        <div class="small text-white-50">Total</div>
                                        <div class="fw-bold text-white">$<?php echo number_format($inv['total_amount'], 2); ?></div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-white-50">Paid</div>
                                        <div class="fw-bold text-success">$<?php echo number_format($inv['amount_paid'], 2); ?></div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-white-50">Balance</div>
                                        <div class="fw-bold <?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>">$<?php echo number_format($balance, 2); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <button type="submit" name="save_invoice_btn" class="btn btn-primary btn-sm px-4">
                                    <?php echo $inv ? '💾 Update Invoice' : '➕ Create Invoice'; ?>
                                </button>
                            </form>
                        </div>

                        <!-- Record Payment -->
                        <?php if ($inv && $balance > 0): ?>
                        <span class="section-label">Record Payment</span>
                        <div class="p-3 mb-3 rounded" style="background:rgba(46,204,113,0.07);border:1px solid rgba(46,204,113,0.25);">
                            <form method="POST">
                                <input type="hidden" name="project_id" value="<?php echo $p_id; ?>">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <label class="small text-white-50 mb-1">Amount ($)</label>
                                        <input type="number" name="payment_amount" class="form-control form-control-sm" step="0.01" min="0.01" max="<?php echo $balance; ?>" value="<?php echo $balance; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-white-50 mb-1">Method</label>
                                        <select name="payment_method" class="form-select form-select-sm">
                                            <option>GCash</option>
                                            <option>Bank Transfer</option>
                                            <option>PayPal</option>
                                            <option>Cash</option>
                                            <option>Credit Card</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" name="record_payment_btn" class="btn btn-success btn-sm w-100">Record</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php elseif ($inv && $balance <= 0): ?>
                        <div class="p-3 mb-3 rounded text-center" style="background:rgba(46,204,113,0.1);border:1px solid rgba(46,204,113,0.3);">
                            <span class="text-success fw-bold">✓ Fully Paid</span>
                        </div>
                        <?php else: ?>
                        <div class="p-3 mb-3 rounded text-center" style="background:rgba(255,255,255,0.04);border:1px solid var(--adhub-border);">
                            <small class="text-white-50">Create an invoice first to record payments.</small>
                        </div>
                        <?php endif; ?>

                        <!-- Payment History -->
                        <?php if ($pays_q && mysqli_num_rows($pays_q) > 0): ?>
                        <span class="section-label">Payment History</span>
                        <div class="table-responsive rounded" style="border:1px solid var(--adhub-border);">
                            <table class="table table-dark table-sm mb-0" style="font-size:0.8rem;">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($pay = mysqli_fetch_assoc($pays_q)): ?>
                                    <tr>
                                        <td class="ps-3"><?php echo date('M d, Y', strtotime($pay['paid_at'])); ?></td>
                                        <td class="text-success fw-bold">$<?php echo number_format($pay['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                    </div><!-- end right col -->
                </div><!-- end row -->
            </div><!-- end modal-body -->

        </div><!-- end modal-content -->
    </div><!-- end modal-dialog -->
</div><!-- end modal -->
<?php endforeach; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-scroll chat feed to bottom when modal opens
    document.addEventListener('show.bs.modal', function(e) {
        var feed = e.target.querySelector('.activity-feed');
        if (feed) setTimeout(function () { feed.scrollTop = feed.scrollHeight; }, 150);
    });

    // AJAX send message — modal stays open
    function adminSendMessage(projectId) {
        var input   = document.getElementById('adminMsgInput' + projectId);
        var message = input.value.trim();
        if (!message) return;

        fetch('send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'project_id=' + projectId + '&message=' + encodeURIComponent(message)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                var feed   = document.getElementById('adminFeed' + projectId);
                var bubble = document.createElement('div');
                bubble.className = 'msg-bubble me';
                bubble.innerHTML =
                    '<small class="d-block fw-bold mb-1 text-info" style="font-size:0.65rem;">' +
                        'YOU (Admin) <span class="text-white-50 fw-normal ms-1">' + data.sent_at + '</span>' +
                    '</small>' +
                    data.message;
                feed.appendChild(bubble);
                feed.scrollTop = feed.scrollHeight;
                input.value = '';
            }
        });
    }

    // Enter key to send
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && document.activeElement && document.activeElement.id.startsWith('adminMsgInput')) {
            adminSendMessage(parseInt(document.activeElement.id.replace('adminMsgInput', '')));
        }
    });

    // Auto-reopen modal after POST redirect using ?open=
    <?php if (isset($_GET['open'])): ?>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('modal<?php echo intval($_GET['open']); ?>');
        if (el) new bootstrap.Modal(el).show();
    });
    <?php endif; ?>

    // Chart
    new Chart(document.getElementById('projectChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'New Projects',
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: '#5ea2ff',
                backgroundColor: 'rgba(94,162,255,0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#cbd5e0', stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: '#cbd5e0' } }
            }
        }
    });
</script>
</body>
</html>