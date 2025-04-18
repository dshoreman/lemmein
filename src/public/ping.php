<?php

$user = (object) [];
require_once '../core.php';

try {
  $list = list_from_file();
  $connection = $_POST['connection'] ?? null;

  if ($connection) {
    $list = update_connection($list, $connection, $_POST['ip'] ?? '');

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
    <small class="login"><?= login_status($user); ?></small>

    <h1>Device Ping</h1>

    <?= $error ?? "" ?>

    <h2>Which Connection?</h2>
    <form method="post" action="<?= htmlentities($_SERVER['PHP_SELF']); ?>">
      <select name="connection" id="connection" size="7" required>
        <option selected disabled>Select one...</option>
        <?php foreach (user_connections($list, $user) as $connId => $conn): ?>
        <option value="<?= $connId; ?>">
          <?= $connId; ?> — <?= $conn['ip'] ?? 'No known IP'; ?>
        </option>
        <?php endforeach; ?>
      </select>

      <h2 class="ip">Your IP: <span id="ip"><?= get_ip(); ?></span></h2>
      <p class="ipfix">
        <a href="#" onclick="fetch_ipv4(); return false;">Not quite right?</a>
      </p>
      <input type="hidden" name="ip" id="ip_input" value="" />
      <button>Send Ping</button>
    </form>

    <script>
      function fetch_ipv4() {
        fetch('https://api.ipify.org')
          .then((resp) => resp.text())
          .then((ip) => {
            document.getElementById('ip').innerText = ip;
            document.getElementById('ip_input').value = ip;
          })
        ;
      }
    </script>
  </body>
</html>
