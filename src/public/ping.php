<?php

require_once '../core.php';

try {
  $list = list_from_file();
} catch (Exception $e) {
  $error = "<b>Error:</b> {$e->getMessage()}";
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ping | Lemmein</title>
    <style type="text/css">
      body { text-align: center; }
      select { font-size: large; padding: 1rem; }
      select option { padding: 0.5rem 1rem; text-align: center; }
    </style>
  </head>
  <body>
    <h1>Device Ping</h1>

    <select name="connection" id="connection" size="7">
      <?php foreach ($list['connections'] as $connId => $conn): ?>
      <option value="<?= $connId; ?>">
        <?= $connId; ?> — <?= $conn['last_ip'] ?? 'No known IP'; ?>
      </option>
      <?php endforeach; ?>
    </select>
    <h2>Your IP: <?= $_SERVER['REMOTE_ADDR'] ?></h2>
  </body>
</html>
