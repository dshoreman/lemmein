<?php

function enforce_user(): void {
  global $config;

  if (!$config->auth_header) {
    return;
  }

  $admins = array_filter($config->admins ?? []);
  $uid = trim($_SERVER["HTTP_{$config->auth_header}_UID"]);

  if (in_array($uid, $admins)) {
    return;
  }

  $username = trim($_SERVER["HTTP_{$config->auth_header}_USERNAME"]);
  $user = $username ? "user '{$username}'" : 'unknown user';

  header('Content-Type: text/plain');
  echo "Access denied for {$user}." . PHP_EOL;

  if ($uid && $config->show_uids) {
    echo PHP_EOL . PHP_EOL . 'Your UID: ' . $uid . PHP_EOL;
  }

  die(http_response_code(403));
}

function login_status(): string {
  global $config;

  if (!$config->auth_header ?? null) {
    return '';
  }

  $user = $_SERVER["HTTP_{$config->auth_header}_USERNAME"] ?? null;
  $name = $_SERVER["HTTP_{$config->auth_header}_NAME"] ?? null;

  if (!$user || !$name) {
    return 'Unauthenticated';
  }

  return "Logged in as {$user} ({$name})";
}

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
  global $config;

  $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
  $real = $_SERVER['HTTP_X_REAL_IP'] ?? null;
  $remote = $_SERVER['REMOTE_ADDR'];

  if ($forwarded && str_contains($forwarded, ',')) {
    $forwarded = explode(',', str_replace(' ', '', $forwarded))[0];
  }

  if (in_array($remote, $config->proxies ?? [])) {
    return $real ?: $forwarded ?: $remote;
  }

  return $remote;
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
  'admins' => [],
  'show_uids' => false,
  'timezone' => 'Europe/London',
];

if (file_exists('../../data/config.json')) {
  $user_config = read_json_data('config.json');

  $config->admins = (array) ($user_config['admins'] ?? []);
  $user_config['auth_header'] && $config->auth_header = $user_config['auth_header'];
  $config->show_uids = (bool) ($user_config['show_uids'] ?? false);

  $user_config['timezone'] && $config->timezone = $user_config['timezone'];
  $user_config['proxy_ips'] && $config->proxies = $user_config['proxy_ips'];
}

date_default_timezone_set($config->timezone);

if ($_SERVER['SCRIPT_NAME'] !== '/list.php') {
  enforce_user();
}
