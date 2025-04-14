<?php

require_once '../core.php';

header('Content-Type: text/plain');

try {
  $list = list_from_file();

  foreach ($list['connections'] as $name => $conn) {
    if (!($conn['ip'] ?? null)) {
      continue;
    }

    echo $conn['ip'] . PHP_EOL;
  }
} catch (Exception $e) {
  echo 'Error: ' . $e->getMessage();
}
