<?php

header('Cache-Control: no-cache, must-revalidate');

function enforce_user(): void {
  global $config;

  if (!$config->auth_header) {
    return;
  }

  $uid_map = read_json_data('idmap.json');
  $admins = array_filter($config->admins ?? []);
  $uid = trim($_SERVER["HTTP_{$config->auth_header}_UID"]);
  $username = trim($_SERVER["HTTP_{$config->auth_header}_USERNAME"]);

  // Allow admins to access anything
  if (in_array($uid, $admins) || (
    in_array($username, $admins) && ($uid_map[$username] ?? null) === $uid
  )) {
    return;
  }

  $username = trim($_SERVER["HTTP_{$config->auth_header}_USERNAME"]);
  $user = $username ? "user '{$username}'" : 'unknown user';

  // Allow anyone to access the ping page
  if ($uid && $username && $_SERVER['SCRIPT_NAME'] === '/ping.php') {
    return;
  }

  // Redirect valid non-admin ping users from dashboard
  if ($uid && $username && !$config->show_uids) {
    die(header('Location: /ping.php', true, 307));
  }

  // Refuse to serve requests lacking correct UID header
  http_response_code($uid ? 401 : 403);

  if (!$config->show_uids) {
    exit;
  }

  $reason = $uid ? 'Unauthorised' : 'Missing auth headers';
  $reason = "Access denied for {$user}. Reason: {$reason}." . PHP_EOL;
  $uid && $reason .= PHP_EOL . PHP_EOL . 'Your UID: ' . $uid . PHP_EOL;

  header('Content-Type: text/plain');
  die($reason);
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
  'auth_header' => '',
  'show_uids' => false,
  'timezone' => 'Europe/London',
];

if (file_exists('../../data/config.json')) {
  $user_config = read_json_data('config.json');

  $config->admins = (array) ($user_config['admins'] ?? []);
  $config->auth_header = $user_config['auth_header'] ?? '';
  $config->show_uids = (bool) ($user_config['show_uids'] ?? false);

  $config->timezone = $user_config['timezone'] ?? $config->timezone;
  $config->proxies = (array) ($user_config['proxy_ips'] ?? []);
}

date_default_timezone_set($config->timezone);

if ($_SERVER['SCRIPT_NAME'] !== '/list.php') {
  enforce_user();
}
