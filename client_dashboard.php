<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// POST HANDLERS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update Profile
    if (isset($_POST['update_profile_btn'])) {
        $full_name    = mysqli_real_escape_string($conn, trim($_POST['full_name']));
        $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
        mysqli_query($conn, "UPDATE tbl_users SET full_name='$full_name', company_name='$company_name' WHERE id='$user_id'");
        $_SESSION['full_name']    = $full_name;
        $_SESSION['company_name'] = $company_name;
        header("Location: client_dashboard.php?msg=profile_saved");
        exit();
    }

    // New Project Request
    if (isset($_POST['new_request_btn'])) {
        $title        = mysqli_real_escape_string($conn, trim($_POST['title']));
        $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
        $budget       = floatval($_POST['budget']);
        $description  = mysqli_real_escape_string($conn, trim($_POST['description']));
        mysqli_query($conn, "INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent) VALUES ('$user_id','$title','$service_type','$budget','$description','pending',0)");
        header("Location: client_dashboard.php?msg=submitted");
        exit();
    }

    // Client Pay Balance
    if (isset($_POST['client_pay_btn'])) {
        $pid    = intval($_POST['project_id']);
        $amount = floatval($_POST['payment_amount']);
        $method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        // Ownership check: verify this project belongs to the logged-in client before touching the invoice
        $inv    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT i.id, i.total_amount, i.amount_paid FROM tbl_invoices i JOIN tbl_projects p ON p.id = i.project_id WHERE i.project_id='$pid' AND p.client_id='$user_id' LIMIT 1"));
        if ($inv) {
            $inv_id     = $inv['id'];
            $new_paid   = $inv['amount_paid'] + $amount;
            $new_status = ($new_paid >= $inv['total_amount']) ? 'paid' : 'partial';
            mysqli_query($conn, "UPDATE tbl_invoices SET amount_paid='$new_paid', status='$new_status' WHERE id='$inv_id'");
            mysqli_query($conn, "INSERT INTO tbl_payments (invoice_id, amount, payment_method) VALUES ('$inv_id','$amount','$method')");
            mysqli_query($conn, "UPDATE tbl_projects SET payment_status='$new_status' WHERE id='$pid' AND client_id='$user_id'");
        }
        header("Location: client_dashboard.php?msg=payment_sent");
        exit();
    }

    // Approve Project / Request Revision
    if (isset($_POST['change_status'])) {
        $pid        = intval($_POST['project_id']);
        $new_status = $_POST['status_val'];
        $allowed_statuses = ['completed', 'revision'];
        if (!in_array($new_status, $allowed_statuses)) {
            header("Location: client_dashboard.php");
            exit();
        }
        $new_status = mysqli_real_escape_string($conn, $new_status);
        mysqli_query($conn, "UPDATE tbl_projects SET status='$new_status' WHERE id='$pid' AND client_id='$user_id'");
        header("Location: client_dashboard.php?msg=status_updated");
        exit();
    }
}

// DATA FETCHING
$user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_users WHERE id='$user_id'"));

// Fetch all projects into array so we can loop multiple times
$proj_result = mysqli_query($conn, "SELECT * FROM tbl_projects WHERE client_id='$user_id' ORDER BY created_at DESC");
$projects    = [];
while ($r = mysqli_fetch_assoc($proj_result)) {
    $projects[] = $r;
}

// Stats
$active_count   = count(array_filter($projects, fn($p) => $p['status'] !== 'completed'));
$delivered      = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));
$needs_feedback = count(array_filter($projects, fn($p) => $p['status'] === 'revision'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdHub | Client Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root { --adhub-bg-deep:#0a121d; --adhub-bg-top:#132238; --adhub-accent:#3182ce; --adhub-border:rgba(255,255,255,0.12); }
        body            { font-family:'Inter',sans-serif; background:radial-gradient(circle at top center,var(--adhub-bg-top) 0%,var(--adhub-bg-deep) 100%); color:#fff; min-height:100vh; }
        .navbar         { background-color:var(--adhub-bg-top); border-bottom:1px solid var(--adhub-border); }
        .card           { background:rgba(255,255,255,0.05); border:1px solid var(--adhub-border); border-radius:12px; }
        .modal-content  { background-color:#111d2c !important; border:1px solid var(--adhub-border); color:#fff; border-radius:16px; }
        .form-control, .form-select { background-color:#080f18 !important; color:#fff !important; border:1px solid var(--adhub-border) !important; }
        .form-select option { background-color:#132238; }
        .activity-feed  { height:300px; overflow-y:auto; background:rgba(0,0,0,0.2); border-radius:10px; padding:15px; border:1px solid var(--adhub-border); }
        .msg-bubble     { background:#1e2e42; padding:12px 16px; border-radius:12px; border-left:3px solid var(--adhub-accent); margin-bottom:10px; max-width:85%; }
        .msg-bubble.me  { border-left:none; border-right:3px solid #718096; background:rgba(255,255,255,0.08); margin-left:auto; text-align:right; }
        .btn-adhub      { background:var(--adhub-accent); color:#fff; border:none; font-weight:600; border-radius:8px; transition:0.2s; }
        .btn-adhub:hover{ background:#2b6cb0; color:#fff; }
        .billing-card   { background:linear-gradient(135deg,rgba(49,130,206,0.15) 0%,rgba(0,0,0,0.3) 100%); border:1px solid rgba(49,130,206,0.3) !important; border-radius:10px; }
        .progress       { background:rgba(255,255,255,0.1); height:8px; border-radius:10px; }
        .file-item      { background:rgba(255,255,255,0.03); border:1px solid var(--adhub-border); border-radius:10px; padding:12px; }
        .modal-body label { color:#fff !important; font-weight:700; text-transform:uppercase; font-size:0.75rem; margin-bottom:8px; display:block; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-5 py-3 sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">ADHUB <span class="fw-light text-white-50">| Portal</span></a>
        <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                <?php echo htmlspecialchars($user_info['full_name']); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">Profile Settings</a></li>
                <li><hr class="dropdown-divider border-secondary"></li>
                <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">

    <!-- ALERTS -->
    <?php
    $flash_msgs = [
        'payment_sent'   => ['success', '✓ Payment submitted! Your payment has been recorded.'],
        'submitted'      => ['success', '✓ Request submitted! Your new project has been received.'],
        'profile_saved'  => ['success', '✓ Profile updated! Your name and company have been saved.'],
        'status_updated' => ['success', '✓ Project status updated.'],
    ];
    if (isset($_GET['msg']) && isset($flash_msgs[$_GET['msg']])):
        [$type, $text] = $flash_msgs[$_GET['msg']];
    ?>
    <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show mb-4" role="alert">
        <?php echo $text; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card p-4 text-center" style="border-color:black">
                <small class="text-black fw-bold text-uppercase">Active Projects</small>
                <h1 class="text-black fw-bold mb-0"><?php echo $active_count; ?></h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 text-center" style="border-color:rgba(231,76,60,0.5) !important;">
                <small class="text-danger fw-bold text-uppercase">Needs Feedback</small>
                <h1 class="fw-bold mb-0 text-danger"><?php echo $needs_feedback; ?></h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 text-center" style="border-color:black">
                <small class="text-black fw-bold text-uppercase">Completed</small>
                <h1 class="text-black fw-bold mb-0"><?php echo $delivered; ?></h1>
            </div>
        </div>
    </div>

    <!-- TABLE HEADER -->
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Requests</h2>
            <p class="text-white-50 small mb-0">Manage your ongoing creative projects</p>
        </div>
        <button class="btn btn-adhub px-4 py-2" data-bs-toggle="modal" data-bs-target="#newRequestModal">+ New Request</button>
    </div>

    <!-- PROJECTS TABLE -->
    <div class="card shadow-lg overflow-hidden mb-4">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead class="small text-white-50 text-uppercase">
                <tr>
                    <th class="ps-4 py-3">Project Details</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($projects) === 0): ?>
                    <tr><td colspan="4" class="text-center py-4 text-white-50">No projects yet. Submit a new request to get started.</td></tr>
                <?php endif; ?>
                <?php foreach ($projects as $row): ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></div>
                        <small class="text-white-50"><?php echo htmlspecialchars($row['service_type']); ?></small>
                    </td>
                    <td>
                        <?php
                        $badge = match($row['status']) {
                            'pending'     => ['bg-secondary', 'Pending'],
                            'in-progress' => ['bg-warning text-dark', 'In Progress'],
                            'revision'    => ['bg-danger', 'Revision Needed'],
                            'completed'   => ['bg-success', 'Completed'],
                            default       => ['bg-primary', ucfirst($row['status'])]
                        };
                        ?>
                        <span class="badge <?php echo $badge[0]; ?> rounded-pill px-3"><?php echo $badge[1]; ?></span>
                    </td>
                    <td style="width:180px;">
                        <div class="progress">
                            <div class="progress-bar bg-info" style="width:<?php echo (int)$row['progress_percent']; ?>%"></div>
                        </div>
                        <small class="text-white-50" style="font-size:0.65rem;"><?php echo (int)$row['progress_percent']; ?>%</small>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-adhub px-3" data-bs-toggle="modal" data-bs-target="#ws<?php echo $row['id']; ?>">Open Workspace</button>
                        <a href="report.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-light ms-1" target="_blank">Report</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!--WORKSPACE MODALS — one per project, outside the table-->
<?php foreach ($projects as $ws):
    $pid     = $ws['id'];
    $msgs_q  = mysqli_query($conn, "SELECT m.*, u.full_name, u.role FROM tbl_messages m JOIN tbl_users u ON u.id = m.sender_id WHERE m.project_id = '$pid' ORDER BY m.id ASC");
    $files_q = mysqli_query($conn, "SELECT * FROM tbl_deliverables WHERE project_id = '$pid' ORDER BY uploaded_at DESC");
    $inv     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_invoices WHERE project_id = '$pid' LIMIT 1"));
    $balance = $inv ? round($inv['total_amount'] - $inv['amount_paid'], 2) : 0;
?>
<div class="modal fade" id="ws<?php echo $pid; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">

            <div class="modal-header border-0 py-4 px-4">
                <div>
                    <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($ws['title']); ?></h4>
                    <span class="text-white-50 small">Project ID: #ADH-<?php echo $pid; ?> &mdash; <?php echo htmlspecialchars($ws['service_type']); ?></span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">
                <div class="row g-4">

                    <!-- Chat + Deliverables -->
                    <div class="col-lg-7 border-end border-white border-opacity-10 pe-lg-4">

                        <label class="mb-2">Discussion &amp; Feedback</label>
                        <div class="activity-feed mb-2" id="feed<?php echo $pid; ?>">
                            <?php if (mysqli_num_rows($msgs_q) === 0): ?>
                                <p class="text-white-50 small">No messages yet.</p>
                            <?php endif; ?>
                            <?php while ($m = mysqli_fetch_assoc($msgs_q)):
                                $isMe = ((int)$m['sender_id'] === $user_id); ?>
                            <div class="msg-bubble <?php echo $isMe ? 'me' : ''; ?>">
                                <small class="d-block fw-bold mb-1 <?php echo $isMe ? 'text-success' : 'text-info'; ?>" style="font-size:0.65rem;">
                                    <?php echo $isMe ? 'YOU' : htmlspecialchars($m['full_name']); ?>
                                    <span class="text-white-50 fw-normal ms-1"><?php echo date('M d, H:i', strtotime($m['sent_at'])); ?></span>
                                </small>
                                <?php echo htmlspecialchars($m['message']); ?>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="input-group mb-4">
                            <input type="text" id="msgInput<?php echo $pid; ?>" class="form-control" placeholder="Type a message...">
                            <button class="btn btn-adhub px-4" onclick="sendMessage(<?php echo $pid; ?>)">Send</button>
                        </div>

                        <label class="mb-2">Deliverables</label>
                        <?php if (mysqli_num_rows($files_q) === 0): ?>
                            <div class="text-center py-4 rounded mb-2" style="border:1px dashed rgba(255,255,255,0.15);">
                                <small class="text-white-50">Waiting for first draft...</small>
                            </div>
                        <?php else: ?>
                            <?php while ($d = mysqli_fetch_assoc($files_q)): ?>
                            <div class="file-item d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-danger me-2">v<?php echo $d['version']; ?></span>
                                    <div>
                                        <div class="small fw-bold text-white"><?php echo htmlspecialchars($d['display_name']); ?></div>
                                        <div class="small text-white-50"><?php echo date('M d, Y H:i', strtotime($d['uploaded_at'])); ?></div>
                                    </div>
                                </div>
                                <a href="<?php echo htmlspecialchars($d['file_path']); ?>" class="btn btn-sm btn-outline-light px-3" download>Download</a>
                            </div>
                            <?php endwhile; ?>
                        <?php endif; ?>

                    </div>

                    <!--Actions + Billing + Brief -->
                    <div class="col-lg-5">

                        <!-- Quick Actions -->
                        <label class="mb-2">Project Management</label>
                        <div class="mb-4 p-3 rounded" style="background:rgba(255,255,255,0.05);border:1px solid var(--adhub-border);">
                            <form method="POST" class="mb-2">
                                <input type="hidden" name="project_id" value="<?php echo $pid; ?>">
                                <input type="hidden" name="status_val" value="completed">
                                <button type="submit" name="change_status" class="btn w-100 fw-bold py-2"
                                    style="background-color:<?php echo $ws['status']==='completed' ? '#0f3d20' : '#1a6b3a'; ?>;color:#fff;border:1px solid #2ecc71;"
                                    <?php echo ($ws['status'] === 'completed') ? 'disabled' : ''; ?>>
                                    <?php echo $ws['status'] === 'completed' ? '✓ Approved' : 'APPROVE PROJECT'; ?>
                                </button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="project_id" value="<?php echo $pid; ?>">
                                <input type="hidden" name="status_val" value="revision">
                                <button type="submit" name="change_status" class="btn w-100 fw-bold py-2"
                                    style="background-color:rgba(231,76,60,0.15);color:#e74c3c;border:1px solid #e74c3c;"
                                    <?php echo ($ws['status'] === 'revision') ? 'disabled' : ''; ?>>
                                    <?php echo $ws['status'] === 'revision' ? '⏳ Revision Requested' : 'REQUEST REVISION'; ?>
                                </button>
                            </form>
                        </div>

                        <!-- Billing Summary -->
                        <label class="mb-2">Billing Summary</label>
                        <div class="billing-card p-3 mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-white-50">Project Budget</small>
                                <span class="fw-bold text-white">$<?php echo number_format($ws['budget'], 2); ?></span>
                            </div>
                            <?php if ($inv): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-white-50">Invoice Total</small>
                                <span class="text-white">$<?php echo number_format($inv['total_amount'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-white-50">Amount Paid</small>
                                <span class="text-success fw-bold">$<?php echo number_format($inv['amount_paid'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-white-50">Balance Due</small>
                                <span class="fw-bold <?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>">$<?php echo number_format($balance, 2); ?></span>
                            </div>
                            <?php if ($inv['due_date']): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-white-50">Due Date</small>
                                <span class="text-white"><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-white-50">Status</small>
                                <span class="text-info fw-bold"><?php echo strtoupper($inv['status']); ?></span>
                            </div>
                            <hr class="my-2 opacity-10">
                            <?php if ($balance > 0): ?>
                                <button type="button" class="btn btn-primary w-100 btn-sm py-2 fw-bold"
                                    onclick="openPayModal(<?php echo $pid; ?>)">
                                    💳 PAY BALANCE — $<?php echo number_format($balance, 2); ?>
                                </button>
                            <?php else: ?>
                                <div class="text-center"><span class="text-success fw-bold small">✓ Fully Paid</span></div>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="mt-2"><small class="text-white-50">Awaiting invoice from admin...</small></div>
                            <?php endif; ?>
                        </div>

                        <!-- Progress -->
                        <label class="mb-2">Production Status</label>
                        <div class="p-3 rounded" style="background:rgba(255,255,255,0.04);border:1px solid var(--adhub-border);">
                            <div class="progress mb-1">
                                <div class="progress-bar bg-info" style="width:<?php echo (int)$ws['progress_percent']; ?>%"></div>
                            </div>
                            <small class="text-info"><?php echo (int)$ws['progress_percent']; ?>% — <?php echo ucfirst(str_replace('-',' ',$ws['status'])); ?></small>
                        </div>

                        <!-- Project Brief -->
                        <label class="mt-3 mb-2">Project Brief</label>
                        <div class="p-3 rounded" style="background:rgba(49,130,206,0.08);border:1px solid rgba(49,130,206,0.2);">
                            <p class="small mb-0 text-white opacity-85"><?php echo $ws['description'] ? nl2br(htmlspecialchars($ws['description'])) : '<span class="text-white-50">No brief provided.</span>'; ?></p>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php endforeach; ?>

<!--PAY BALANCE MODALS — one per project that has an outstanding balance-->
<?php foreach ($projects as $ws2):
    $pid2  = $ws2['id'];
    $inv2  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_invoices WHERE project_id='$pid2' LIMIT 1"));
    if (!$inv2) continue;
    $bal2  = round($inv2['total_amount'] - $inv2['amount_paid'], 2);
    if ($bal2 <= 0) continue;
?>
<div class="modal fade" id="payModal<?php echo $pid2; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">💳 Pay Balance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-white-50 small mb-3">Project: <strong class="text-white"><?php echo htmlspecialchars($ws2['title']); ?></strong></p>
                <div class="billing-card p-3 mb-4">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-white-50">Total:</span>
                        <span class="text-white">$<?php echo number_format($inv2['total_amount'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-white-50">Paid:</span>
                        <span class="text-success fw-bold">$<?php echo number_format($inv2['amount_paid'], 2); ?></span>
                    </div>
                    <hr class="my-2 opacity-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold small text-white">Balance Due:</span>
                        <h5 class="fw-bold text-danger mb-0">$<?php echo number_format($bal2, 2); ?></h5>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $pid2; ?>">
                    <div class="mb-3">
                        <label>Amount to Pay ($)</label>
                        <input type="number" name="payment_amount" class="form-control" step="0.01" min="0.01" max="<?php echo $bal2; ?>" value="<?php echo $bal2; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option>GCash</option>
                            <option>Bank Transfer</option>
                            <option>PayPal</option>
                            <option>Cash</option>
                            <option>Credit Card</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="client_pay_btn" class="btn btn-primary fw-bold py-2">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- NEW REQUEST MODAL -->
<div class="modal fade" id="newRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold">New Project Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Project Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Summer Sale Banner" required>
                        </div>
                        <div class="col-md-3">
                            <label>Service Type</label>
                            <select name="service_type" class="form-select">
                                <option>Graphic Design</option>
                                <option>Logo Design</option>
                                <option>Slogan</option>
                                <option>Poster</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Budget ($)</label>
                            <input type="number" name="budget" class="form-control" placeholder="e.g. 300" min="0" step="0.01">
                        </div>
                        <div class="col-12">
                            <label>Brief Details &amp; Instructions</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Tell us about the project..."></textarea>
                        </div>
                        <div class="col-12 text-end mt-2">
                            <button type="submit" name="new_request_btn" class="btn btn-adhub px-5 py-2">Submit Request</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- PROFILE SETTINGS MODAL -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Profile Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" id="profileForm">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label>Company Name</label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($user_info['company_name'] ?? ''); ?>">
                    </div>
                    <div class="d-grid">
                        <button type="button" class="btn btn-adhub py-2" onclick="confirmSaveProfile()">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script>
    // ---- Profile save confirmation ----
    function confirmSaveProfile() {
        if (confirm('Are you sure you want to save these changes?')) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'update_profile_btn'; inp.value = '1';
            document.getElementById('profileForm').appendChild(inp);
            document.getElementById('profileForm').submit();
        }
    }

    // ---- AJAX send message — modal stays open ----
    function sendMessage(projectId) {
        var input   = document.getElementById('msgInput' + projectId);
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
                var feed   = document.getElementById('feed' + projectId);
                var bubble = document.createElement('div');
                bubble.className = 'msg-bubble me';
                bubble.innerHTML =
                    '<small class="d-block fw-bold mb-1 text-success" style="font-size:0.65rem;">' +
                        'YOU <span class="text-white-50 fw-normal ms-1">' + data.sent_at + '</span>' +
                    '</small>' +
                    data.message;
                feed.appendChild(bubble);
                feed.scrollTop = feed.scrollHeight;
                input.value = '';
            }
        });
    }

    // ---- Enter key to send ----
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && document.activeElement && document.activeElement.id.startsWith('msgInput')) {
            sendMessage(parseInt(document.activeElement.id.replace('msgInput', '')));
        }
    });

    // ---- Close workspace modal, then open pay modal ----
    function openPayModal(pid) {
        var wsEl  = document.getElementById('ws' + pid);
        var payEl = document.getElementById('payModal' + pid);
        if (!wsEl || !payEl) return;
        var wsModal  = bootstrap.Modal.getInstance(wsEl) || new bootstrap.Modal(wsEl);
        var payModal = new bootstrap.Modal(payEl);
        wsModal.hide();
        wsEl.addEventListener('hidden.bs.modal', function handler() {
            payModal.show();
            wsEl.removeEventListener('hidden.bs.modal', handler);
        });
    }

    // ---- Auto-scroll chat feeds to bottom when modal opens ----
    document.addEventListener('show.bs.modal', function(e) {
        var feed = e.target.querySelector('.activity-feed');
        if (feed) setTimeout(function () { feed.scrollTop = feed.scrollHeight; }, 150);
    });
</script>

</body>
</html>