<?php

$user = (object) [];
require_once '../core.php';

try {
  $list = list_from_file();
  $connection = $_POST['connection'] ?? null;

  if ($connection) {
    $list = update_connection($list, $connection);

    save_json($list, 'list.json');

    header("Location: /ping.php");
  }
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
    <link rel="stylesheet" href="/assets/style.css" />
  </head>
  <body>
    <small><?= login_status(); ?></small>

    <h1>Device Ping</h1>

    <?= $error ?? "" ?>

    <h2>Which Connection?</h2>
    <form method="post" action="<?= htmlentities($_SERVER['PHP_SELF']); ?>">
      <select name="connection" id="connection" size="7" required>
        <?php foreach (user_connections($list, $user) as $connId => $conn): ?>
        <option value="<?= $connId; ?>">
          <?= $connId; ?> — <?= $conn['ip'] ?? 'No known IP'; ?>
        </option>
        <?php endforeach; ?>
      </select>

      <h2>Your IP: <?= get_ip(); ?></h2>
      <button>Send Ping</button>
    </form>
  </body>
</html>
