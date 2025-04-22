<?php

header('Content-Type: text/plain');

try {
  require_once '../core.php';

  $list = list_from_file();
  $consumers = $list['consumers'] ?? [];

  if ($consumers && !in_array(get_ip(), $consumers)) {
    die(http_response_code(403));
  }

  $connections = array_values($list['connections']);

  $entries = array_merge(
    array_values($list['networks'] ?? []),
    array_map(fn (array $conn): string => (
      isset($conn['ip'], $conn['since']) ? $conn['ip'] : ''
    ), $connections),
  );

  foreach (array_filter($entries) as $ip_or_cidr_block) {
    echo $ip_or_cidr_block . PHP_EOL;
  }
} catch (Exception $e) {
  http_response_code(500);

  echo 'Error: ' . $e->getMessage();
}
