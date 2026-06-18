<?php
// ─────────────────────────────────────────────
// TaperX Waitlist — Admin Viewer
// ─────────────────────────────────────────────
// Change the password below before uploading!
define('ADMIN_PASSWORD', 'TaperXAdmin2026!');

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'taperhsn_taperx_waitlist');
define('DB_USER', 'taperhsn_Rex');
define('DB_PASS', 'Duster1984!');

session_start();

// ── Handle login / logout ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['taperx_admin'] = true;
    } else {
        $login_error = 'Incorrect password.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$logged_in = !empty($_SESSION['taperx_admin']);

// ── Fetch data if logged in ──
$rows  = [];
$total = 0;
if ($logged_in) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $total = $pdo->query("SELECT COUNT(*) FROM waitlist")->fetchColumn();
        $rows  = $pdo->query("SELECT * FROM waitlist ORDER BY submitted_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $db_error = 'Could not connect to database.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TaperX — Waitlist Admin</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: system-ui, sans-serif; background: #f0f5f1; color: #1b2b25; min-height: 100vh; }

  /* ── Login screen ── */
  .login-wrap {
    display: flex; align-items: center; justify-content: center;
    min-height: 100vh; padding: 24px;
  }
  .login-card {
    background: #fff; border-radius: 16px; padding: 40px;
    box-shadow: 0 4px 24px rgba(15,79,60,0.12);
    width: 100%; max-width: 380px;
  }
  .login-card h1 { font-size: 22px; font-weight: 800; color: #0f4f3c; margin-bottom: 6px; }
  .login-card p  { font-size: 14px; color: #7a877f; margin-bottom: 24px; }
  .login-card input[type="password"] {
    width: 100%; padding: 12px 16px; border: 1px solid #dde5df;
    border-radius: 8px; font-size: 16px; margin-bottom: 12px;
  }
  .login-card button {
    width: 100%; padding: 13px; background: #0f4f3c; color: #fff;
    border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;
  }
  .login-card button:hover { background: #0a3b2c; }
  .error { color: #c0392b; font-size: 14px; margin-top: 10px; }

  /* ── Admin dashboard ── */
  .topbar {
    background: #0f4f3c; color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 32px;
  }
  .topbar h1 { font-size: 20px; font-weight: 800; letter-spacing: -0.02em; }
  .topbar .meta { font-size: 14px; opacity: 0.75; }
  .topbar a { color: #ef8466; font-size: 14px; font-weight: 600; text-decoration: none; }

  .stats-bar {
    background: #fff; border-bottom: 1px solid #e0e8e2;
    padding: 16px 32px; display: flex; gap: 40px;
  }
  .stat-item { }
  .stat-num  { font-size: 28px; font-weight: 800; color: #0f4f3c; line-height: 1; }
  .stat-lbl  { font-size: 13px; color: #7a877f; margin-top: 2px; }

  .table-wrap { padding: 24px 32px; overflow-x: auto; }

  table { width: 100%; border-collapse: collapse; background: #fff;
          border-radius: 12px; overflow: hidden;
          box-shadow: 0 1px 4px rgba(15,79,60,0.07); font-size: 14px; }
  thead { background: #0f4f3c; color: #fff; }
  thead th { padding: 12px 14px; text-align: left; font-weight: 600; font-size: 13px;
             white-space: nowrap; }
  tbody tr:nth-child(even) { background: #f7faf8; }
  tbody tr:hover { background: #e7efe9; }
  tbody td { padding: 11px 14px; border-bottom: 1px solid #eef2ef;
             vertical-align: top; max-width: 220px; word-break: break-word; }
  .badge {
    display: inline-block; padding: 2px 8px; border-radius: 100px;
    font-size: 12px; font-weight: 600; background: #e7efe9; color: #0f4f3c;
  }
  .badge.hero { background: #fceee8; color: #e06a4c; }
  .no-rows { text-align: center; padding: 60px; color: #7a877f; font-size: 15px; }

  /* ── Export button ── */
  .actions { padding: 0 32px 16px; display: flex; gap: 12px; }
  .btn-export {
    padding: 10px 20px; background: #0f4f3c; color: #fff;
    border: none; border-radius: 8px; font-size: 14px; font-weight: 600;
    cursor: pointer; text-decoration: none; display: inline-block;
  }
  .btn-export:hover { background: #0a3b2c; }

  @media (max-width: 600px) {
    .topbar, .stats-bar, .table-wrap, .actions { padding-left: 16px; padding-right: 16px; }
    .stats-bar { gap: 24px; }
  }
</style>
</head>
<body>

<?php if (!$logged_in): ?>
<!-- ── LOGIN ── -->
<div class="login-wrap">
  <div class="login-card">
    <h1>Taper<span style="color:#ef8466">X</span> Admin</h1>
    <p>Waitlist submissions viewer</p>
    <form method="POST">
      <input type="password" name="password" placeholder="Enter admin password" autofocus>
      <button type="submit">Sign in</button>
      <?php if (!empty($login_error)): ?>
        <p class="error"><?= htmlspecialchars($login_error) ?></p>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ── DASHBOARD ── -->
<div class="topbar">
  <h1>Taper<span style="color:#ef8466">X</span> — Waitlist</h1>
  <div style="display:flex;align-items:center;gap:24px;">
    <span class="meta">Admin panel</span>
    <a href="?logout=1">Sign out</a>
  </div>
</div>

<?php if (!empty($db_error)): ?>
  <div style="padding:32px;color:#c0392b;"><?= htmlspecialchars($db_error) ?></div>
<?php else: ?>

<div class="stats-bar">
  <div class="stat-item">
    <div class="stat-num"><?= $total ?></div>
    <div class="stat-lbl">Total signups</div>
  </div>
  <?php
    $main = array_filter($rows, fn($r) => $r['source'] === 'main-form');
    $hero = array_filter($rows, fn($r) => $r['source'] === 'hero-mini-form');
  ?>
  <div class="stat-item">
    <div class="stat-num"><?= count($main) ?></div>
    <div class="stat-lbl">Full form</div>
  </div>
  <div class="stat-item">
    <div class="stat-num"><?= count($hero) ?></div>
    <div class="stat-lbl">Hero quick signup</div>
  </div>
</div>

<div class="actions">
  <a class="btn-export" href="?export=csv">Download CSV</a>
</div>

<?php
// ── CSV export ──
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="taperx_waitlist_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Email','Price','Coach Importance','Rx+Pharmacy','Tracker Importance','Medication','Medication Other','Notes','Source','Submitted At']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['name'], $r['email'], $r['price'],
            $r['coach_importance'], $r['rx_pharmacy'], $r['tracker_importance'],
            $r['medication'], $r['medication_other'], $r['notes'],
            $r['source'], $r['submitted_at']
        ]);
    }
    fclose($out);
    exit;
}
?>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Email</th>
        <th>Price</th>
        <th>Coach</th>
        <th>Rx+Pharmacy</th>
        <th>Tracker</th>
        <th>Medication</th>
        <th>Notes</th>
        <th>Source</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="11" class="no-rows">No signups yet — share that landing page!</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['email']) ?></td>
          <td><?= htmlspecialchars($r['price'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['coach_importance'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['rx_pharmacy'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['tracker_importance'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['medication'] ?? '—') ?><?= !empty($r['medication_other']) ? ' / ' . htmlspecialchars($r['medication_other']) : '' ?></td>
          <td><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
          <td><span class="badge <?= $r['source'] === 'hero-mini-form' ? 'hero' : '' ?>"><?= $r['source'] === 'hero-mini-form' ? 'Hero' : 'Full form' ?></span></td>
          <td style="white-space:nowrap;"><?= date('M j, Y', strtotime($r['submitted_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>
<?php endif; ?>

</body>
</html>
