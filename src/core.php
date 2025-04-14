<?php

function read_json_data($filename): array {
  $file = file_get_contents('../../data/' . $filename);

  $file === false && throw new Exception("Failed to open data/{$filename}.");

  return json_decode($file, true);
}

function list_from_file(): array {
  $list = read_json_data('list.json');

  $list['name'] || throw new Exception("Missing list name.");

  is_array($list['connections'] ?? '') && count($list['connections'])
    || throw new Exception("No valid connections found in JSON.");

  return $list;
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

$config = (object) [
  'timezone' => 'Europe/London',
];

if (file_exists('../../data/config.json')) {
  $user_config = read_json_data('config.json');

  $user_config['timezone'] && $config->timezone = $user_config['timezone'];
}

date_default_timezone_set($config->timezone);
