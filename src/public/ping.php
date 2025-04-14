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
      h2 { margin-top: 8vh; }
      select { font-size: large; padding: 1rem; }
      select option { padding: 1.5vh 3vw; text-align: center; }
      button { font-size: larger; padding: 1vh 2vh; }
    </style>
  </head>
  <body>
    <h1>Device Ping</h1>

    <h2>Which Connection?</h2>
    <form method="post" action="<?= htmlentities($_SERVER['PHP_SELF']); ?>">
      <select name="connection" id="connection" size="7" required>
        <?php foreach ($list['connections'] as $connId => $conn): ?>
        <option value="<?= $connId; ?>">
          <?= $connId; ?> — <?= $conn['last_ip'] ?? 'No known IP'; ?>
        </option>
        <?php endforeach; ?>
      </select>

      <h2>Your IP: <?= $_SERVER['REMOTE_ADDR'] ?></h2>
      <button>Send Ping</button>
    </form>
  </body>
</html>
