<?php

$user = (object) [];
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
    <link rel="stylesheet" href="/assets/style.css" />
  </head>
  <body>
    <small><?= login_status($user); ?></small>

    <h1>List: <?= $list['name'] ?></h1>

    <?= $error ?? "" ?>

    <h2>Static Networks</h2>
    <table align="center" cellspacing="25">
      <tr>
        <th>Network Name</th>
        <th>Subnet</th>
      </tr>
      <?php foreach ($list['networks'] as $network => $subnet): ?>
      <tr>
        <td><?= $network ?></td>
        <td><?= $subnet ?></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <h2>Dynamic Connections</h2>
    <table align="center" cellspacing="25">
      <tr>
        <th>Connection Name</th>
        <th>Last Known IP</th>
        <th>Last Updated</th>
      </tr>
      <?php foreach ($list['connections'] as $connId => $conn): ?>
      <tr>
        <td><?= $connId ?></td>
        <?php if (!is_array($conn) || !isset($conn['ip'], $conn['since'])): ?>
          <td colspan="2">Awaiting Ping</td>
        <?php else: ?>
          <td><?= $conn['ip'] ?></td>
          <td><?= date('d/m/Y, H:i:s', $conn['since']) ?></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </table>
  </body>
</html>
