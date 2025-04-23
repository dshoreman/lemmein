<?php

$config = (object) [];

header('Content-Type: text/plain');

try {
  require_once '../core.php';

  $ip = get_ip();
  $list = list_from_file();
  $consumers = $list['consumers'] ?? [];

  if ($consumers && !in_array($ip, $consumers)) {
    http_response_code(403);

    if ($config->detailed_denials) {
      echo "IP {$ip} is not in consumers list.";
    }

    exit;
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
