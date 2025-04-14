<?php

function list_from_file(): array {
  $file = file_get_contents('../../data/list.json');

  $file === false && throw new Exception("Failed to open list.json file.");

  $json = json_decode($file, true);

  $json['name'] || throw new Exception("Missing list name.");

  is_array($json['connections'] ?? '') && count($json['connections'])
    || throw new Exception("No valid connections found in JSON.");

  return $json;
}

function get_ip(): string {
  return $_SERVER['REMOTE_ADDR'];
}

function update_connection(array $list, string $connection): array {
  is_array($list['connections'][$connection] ?? null)
    || throw new Exception("Connection '{$connection}' is missing or invalid.");

  $list['connections'][$connection]['ip'] = get_ip();

  return $list;
}
