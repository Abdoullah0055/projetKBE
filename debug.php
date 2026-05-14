<?php
require_once __DIR__ . '/includes/debug_helper.php';
require_once __DIR__ . '/includes/session.php';

$action = $_GET['action'] ?? '';

if ($action === 'clear') {
    debug_log_clear();
    header('Location: debug.php');
    exit;
}

$logPath = DEBUG_LOG_PATH;
$lines = [];

if (is_file($logPath)) {
    $content = file_get_contents($logPath);
    $lines = array_filter(explode("\n", $content));
    $lines = array_reverse($lines);
}

$title = 'Debug Log';
$bodyClass = 'debug-page';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Debug Log</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background: #0d1117; color: #c9d1d9; font-family: monospace; padding: 20px; }
  h1 { color: #58a6ff; margin-bottom: 20px; }
  .actions { margin-bottom: 20px; }
  .actions a { color: #58a6ff; text-decoration: none; margin-right: 15px; }
  .actions a:hover { text-decoration: underline; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; padding: 8px 12px; background: #161b22; border-bottom: 2px solid #30363d; }
  td { padding: 6px 12px; border-bottom: 1px solid #21262d; font-size: 13px; word-break: break-all; }
  tr:hover td { background: #161b22; }
  .time { color: #8b949e; white-space: nowrap; width: 170px; }
  .msg { color: #c9d1d9; }
  .empty { color: #8b949e; font-style: italic; padding: 20px; }
  .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 11px; margin-right: 6px; }
  .badge-green { background: #1b4a23; color: #3fb950; }
  .badge-red { background: #4a1b1b; color: #f85149; }
  .badge-blue { background: #1b3a4a; color: #58a6ff; }
  .badge-yellow { background: #4a3b1b; color: #d29922; }
  .refresh { position: fixed; bottom: 20px; right: 20px; background: #238636; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-family: monospace; }
  .refresh:hover { background: #2ea043; }
</style>
</head>
<body>
<h1>🐛 Debug Log</h1>
<div class="actions">
  <a href="debug.php">🔄 Rafraîchir</a>
  <a href="debug.php?action=clear" onclick="return confirm('Vider le log ?')">🗑️ Vider</a>
  <a href="roadmap.php">⬅️ Roadmap</a>
</div>

<?php if (empty($lines)): ?>
  <p class="empty">Aucun log pour l'instant. Fais la quête d'Aldric puis reviens ici.</p>
<?php else: ?>
<table>
  <thead><tr><th>Heure</th><th>Message</th></tr></thead>
  <tbody>
    <?php foreach ($lines as $line):
      $parts = explode('] ', $line, 2);
      $time = str_replace('[', '', $parts[0] ?? '');
      $msg = $parts[1] ?? $line;
      $badge = '';
      if (str_contains($msg, 'is_correct')) {
        $badge = str_contains($msg, 'true') ? '<span class="badge badge-green">✅</span>' : '<span class="badge badge-red">❌</span>';
      } elseif (str_contains($msg, 'ADDED')) {
        $badge = '<span class="badge badge-green">➕</span>';
      } elseif (str_contains($msg, 'SKIPPED') || str_contains($msg, 'ABORT')) {
        $badge = '<span class="badge badge-red">⚠️</span>';
      } elseif (str_contains($msg, 'states=')) {
        $badge = '<span class="badge badge-blue">📊</span>';
      } elseif (str_contains($msg, 'Session AFTER') || str_contains($msg, 'Session BEFORE')) {
        $badge = '<span class="badge badge-yellow">💾</span>';
      }
    ?>
    <tr>
      <td class="time"><?= htmlspecialchars($time) ?></td>
      <td class="msg"><?= $badge ?><?= htmlspecialchars($msg) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<button class="refresh" onclick="window.location.href='debug.php'">🔄 Rafraîchir</button>
</body>
</html>
