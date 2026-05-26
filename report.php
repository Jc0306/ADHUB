<?php
session_start();
include('db_connection.php');

// Only logged-in users (admin or client) may generate reports
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$project_id = intval($_GET['id'] ?? 0);
if ($project_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch the project; enforce ownership for clients
if ($_SESSION['role'] === 'admin') {
    $proj = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.*, u.full_name, u.email, u.company_name
         FROM tbl_projects p
         JOIN tbl_users u ON u.id = p.client_id
         WHERE p.id = '$project_id' LIMIT 1"));
} else {
    $user_id = (int)$_SESSION['user_id'];
    $proj    = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.*, u.full_name, u.email, u.company_name
         FROM tbl_projects p
         JOIN tbl_users u ON u.id = p.client_id
         WHERE p.id = '$project_id' AND p.client_id = '$user_id' LIMIT 1"));
}

if (!$proj) {
    http_response_code(403);
    exit("Project not found or access denied.");
}

$inv     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_invoices WHERE project_id='$project_id' LIMIT 1"));
$balance = $inv ? round($inv['total_amount'] - $inv['amount_paid'], 2) : null;
$pays_q  = $inv ? mysqli_query($conn, "SELECT * FROM tbl_payments WHERE invoice_id='{$inv['id']}' ORDER BY paid_at ASC") : null;
$files_q = mysqli_query($conn, "SELECT * FROM tbl_deliverables WHERE project_id='$project_id' ORDER BY uploaded_at ASC");
$msgs_q  = mysqli_query($conn, "SELECT m.*, u.full_name, u.role FROM tbl_messages m JOIN tbl_users u ON u.id = m.sender_id WHERE m.project_id='$project_id' ORDER BY m.sent_at ASC");

$generated = date('F d, Y \a\t g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AdHub Report - <?php echo htmlspecialchars($proj['title']); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1a202c; background: #f7f9fc; padding: 40px; }
        a { color: #2b6cb0; }
        @media print {
            body { background: #fff; padding: 20px; }
            .no-print { display: none !important; }
        }
        .report-wrap { max-width: 900px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
        .report-header { background: #0a121d; color: #fff; padding: 32px 40px; }
        .report-header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .report-header .sub { font-size: 12px; color: rgba(255,255,255,0.55); }
        .report-header .meta { margin-top: 16px; display: flex; gap: 32px; flex-wrap: wrap; }
        .meta-item label { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,0.45); display: block; margin-bottom: 2px; }
        .meta-item span  { font-size: 13px; font-weight: 600; color: #fff; }
        .section { padding: 28px 40px; border-bottom: 1px solid #e2e8f0; }
        .section:last-child { border-bottom: none; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #718096; margin-bottom: 16px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; ;font-weight: 700; text-transform: uppercase; }
        .badge-pending   { background:black; color:#4a5568; }
        .badge-progress  { background:black; color:#744210; }
        .badge-revision  { background:black; color:#742a2a; }
        .badge-completed { background:black; color:#22543d; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f4f8; font-size: 13px; }
        .info-row:last-child { border-bottom: none; }
        .info-row .lbl { color: #718096; }
        .info-row .val { font-weight: 600; }
        .val-red   { color: #e53e3e; }
        .val-green { color: #38a169; }
        .progress-wrap { background: #edf2f7; border-radius: 99px; height: 10px; margin-top: 6px; }
        .progress-fill { background: #3182ce; border-radius: 99px; height: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead tr { background: #f7f9fc; }
        th { text-align: left; padding: 8px 12px; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #718096; border-bottom: 1px solid #e2e8f0; }
        td { padding: 9px 12px; border-bottom: 1px solid #f0f4f8; color: #2d3748; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        .msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 12px; }
        .msg-client { background: #ebf4ff; border-left: 3px solid #3182ce; }
        .msg-admin  { background: #f0fff4; border-left: 3px solid #38a169; }
        .msg-meta   { font-size: 10px; color: #718096; margin-bottom: 4px; font-weight: 600; }
        .report-footer { background: #f7f9fc; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #a0aec0; }
        .toolbar { max-width: 900px; margin: 0 auto 16px; display: flex; gap: 10px; }
        .btn-print { background: #1b3252; color: #fff; border: none; padding: 9px 22px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-back  { background: #edf2f7; color: #2d3748; border: none; padding: 9px 22px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-print:hover { background: #23406a; }
        .btn-back:hover  { background: #e2e8f0; }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <?php $back = ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'client_dashboard.php'; ?>
    <a href="<?php echo $back; ?>" class="btn-back">Back to Dashboard</a>
    <button class="btn-print" onclick="window.print()">Print / Save PDF</button>
</div>

<div class="report-wrap">

    <!-- HEADER -->
    <div class="report-header">
        <h1>Campaign Report - <?php echo htmlspecialchars($proj['title']); ?></h1>
        <div class="sub">Generated by AdHub &bull; <?php echo $generated; ?></div>
        <div class="meta">
            <div class="meta-item"><label>Client</label><span><?php echo htmlspecialchars($proj['full_name']); ?></span></div>
            <div class="meta-item"><label>Company</label><span><?php echo htmlspecialchars($proj['company_name'] ?: 'N/A'); ?></span></div>
            <div class="meta-item"><label>Project ID</label><span>#ADH-<?php echo $proj['id']; ?></span></div>
            <div class="meta-item"><label>Service</label><span><?php echo htmlspecialchars($proj['service_type']); ?></span></div>
            <div class="meta-item">
                <label>Status</label>
                <?php
                $bc = ['pending'=>'badge-pending','in-progress'=>'badge-progress','revision'=>'badge-revision','completed'=>'badge-completed'][$proj['status']] ?? 'badge-pending';
                ?>
                <span><span class="badge <?php echo $bc; ?>"><?php echo $proj['status']; ?></span></span>
            </div>
        </div>
    </div>

    <!-- PROJECT OVERVIEW -->
    <div class="section">
        <div class="section-title">Project Overview</div>
        <div class="grid-2">
            <div>
                <div class="info-row"><span class="lbl">Created</span><span class="val"><?php echo date('M d, Y', strtotime($proj['created_at'])); ?></span></div>
                <div class="info-row"><span class="lbl">Budget</span><span class="val">$<?php echo number_format($proj['budget'], 2); ?></span></div>
                <div class="info-row"><span class="lbl">Payment Status</span><span class="val"><?php echo strtoupper($proj['payment_status']); ?></span></div>
            </div>
            <div>
                <div class="info-row"><span class="lbl">Progress</span><span class="val"><?php echo (int)$proj['progress_percent']; ?>%</span></div>
                <div style="padding:4px 0 8px;">
                    <div class="progress-wrap"><div class="progress-fill" style="width:<?php echo min(100,(int)$proj['progress_percent']); ?>%;"></div></div>
                </div>
                <div class="info-row"><span class="lbl">Client Email</span><span class="val"><?php echo htmlspecialchars($proj['email']); ?></span></div>
            </div>
        </div>
        <?php if ($proj['description']): ?>
        <div style="margin-top:16px;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#718096;margin-bottom:8px;">Brief</div>
            <div style="background:#f7f9fc;border:1px solid #e2e8f0;border-radius:6px;padding:14px;color:#2d3748;line-height:1.6;">
                <?php echo nl2br(htmlspecialchars($proj['description'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- BILLING SUMMARY -->
    <div class="section">
        <div class="section-title">Billing Summary</div>
        <?php if ($inv): ?>
        <div class="grid-2">
            <div>
                <div class="info-row"><span class="lbl">Invoice Total</span><span class="val">$<?php echo number_format($inv['total_amount'], 2); ?></span></div>
                <div class="info-row"><span class="lbl">Amount Paid</span><span class="val val-green">$<?php echo number_format($inv['amount_paid'], 2); ?></span></div>
                <div class="info-row"><span class="lbl">Remaining Balance</span><span class="val <?php echo $balance > 0 ? 'val-red' : 'val-green'; ?>">$<?php echo number_format($balance, 2); ?></span></div>
                <div class="info-row"><span class="lbl">Invoice Status</span><span class="val"><?php echo strtoupper($inv['status']); ?></span></div>
                <?php if ($inv['due_date']): ?>
                <div class="info-row"><span class="lbl">Due Date</span><span class="val"><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></span></div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($pays_q && mysqli_num_rows($pays_q) > 0): ?>
        <div style="margin-top:16px;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#718096;margin-bottom:8px;">Payment History</div>
            <table>
                <thead><tr><th>#</th><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
                <tbody>
                    <?php $pn = 1; while ($pay = mysqli_fetch_assoc($pays_q)): ?>
                    <tr>
                        <td><?php echo $pn++; ?></td>
                        <td><?php echo date('M d, Y', strtotime($pay['paid_at'])); ?></td>
                        <td style="color:#38a169;font-weight:600;">$<?php echo number_format($pay['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <p style="color:#718096;">No invoice has been created for this project.</p>
        <?php endif; ?>
    </div>

    <!-- DELIVERABLES -->
    <div class="section">
        <div class="section-title">Deliverables</div>
        <?php if (mysqli_num_rows($files_q) === 0): ?>
        <p style="color:#718096;">No files have been uploaded for this project.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>Version</th><th>Display Name</th><th>Uploaded</th><th>Filename</th></tr></thead>
            <tbody>
                <?php while ($f = mysqli_fetch_assoc($files_q)): ?>
                <tr>
                    <td><span class="badge" style="background:#fed7d7;color:#742a2a;">v<?php echo $f['version']; ?></span></td>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($f['display_name']); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($f['uploaded_at'])); ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($f['file_path']); ?>" class="no-print">Download</a>
                        <span style="font-size:11px;color:#a0aec0;"><?php echo basename($f['file_path']); ?></span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- DISCUSSION LOG -->
    <div class="section">
        <div class="section-title">Discussion Log</div>
        <?php if (mysqli_num_rows($msgs_q) === 0): ?>
        <p style="color:#718096;">No messages for this project.</p>
        <?php else: ?>
        <?php while ($m = mysqli_fetch_assoc($msgs_q)): ?>
        <div class="msg <?php echo $m['role'] === 'admin' ? 'msg-admin' : 'msg-client'; ?>">
            <div class="msg-meta">
                <?php echo htmlspecialchars($m['full_name']); ?> (<?php echo ucfirst($m['role']); ?>)
                &bull; <?php echo date('M d, Y H:i', strtotime($m['sent_at'])); ?>
            </div>
            <?php echo nl2br(htmlspecialchars($m['message'])); ?>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <div class="report-footer">
        <span>AdHub - Agency-Client Campaign Manager</span>
        <span>Report for #ADH-<?php echo $proj['id']; ?> &bull; <?php echo $generated; ?></span>
    </div>

</div>
</body>
</html>