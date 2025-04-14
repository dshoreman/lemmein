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

  $new_ip = get_ip();
  $old_ip = $list['connections'][$connection]['ip'] ?? '';
  $new_ip === $old_ip && throw new Exception("Connection IP hasn't changed!");

  $list['connections'][$connection]['ip'] = $new_ip;
  $list['connections'][$connection]['since'] = time();

  return $list;
}

function save_json(array $list): bool {
  $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

  $json = json_encode($list, $flags);

  return file_put_contents('../../data/list.json', $json)
    || throw new Exception("Failed to write JSON.");
}
