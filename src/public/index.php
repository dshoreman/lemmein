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
    <title>Connections | Lemmein</title>
  </head>
  <body>
    <h1>Connections Dashboard</h1>

    <?= $error ?? "" ?>

    <h2>Managing List: <?= $list['name'] ?></h2>

    <table cellspacing="25">
      <tr>
        <th>Connection Name</th>
        <th>Last Known IP</th>
        <th>Last Updated</th>
      </tr>
      <?php foreach ($list['connections'] as $connId => $conn): ?>
      <tr align="center">
        <td><?= $connId ?></td>
        <td><?= $conn['current_ip'] ?? 'None' ?></td>
        <td><?= $conn['updated_at'] ?? 'Never' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </body>
</html>
