<?php

header('Cache-Control: no-cache, must-revalidate');

function user_from_headers(object $config): object {
  if (!$config->auth_header) {
    return (object) [ 'needs_auth' => false, 'is_admin' => true ];
  }

  $user = (object) [
    'needs_auth' => true,
    'uid' => trim($_SERVER["HTTP_{$config->auth_header}_UID"] ?? ''),
    'name' => trim($_SERVER["HTTP_{$config->auth_header}_NAME"] ?? ''),
    'username' => trim($_SERVER["HTTP_{$config->auth_header}_USERNAME"] ?? ''),
  ];

  $uid_map = load_uid_map($user);
  $admins = array_filter($config->admins ?? []);
  $mapped_uid = $uid_map[$user->username] ?? null;

  $user->is_admin = in_array($user->username, $admins) && $mapped_uid === $user->uid;
  $user->is_valid = $user->uid && $user->name && $user->username;

  return $user;
}

function load_uid_map(object $user) {
  try {
    $uid_map = read_json_data('idmap.json');
  } catch (Exception $e) {
    $uid_map = [];
  }

  if ($user->uid && !isset($uid_map[$user->username])) {
    $uid_map[$user->username] = $user->uid;

    // This throws on permission errors etc, but we do NOT want
    // to catch it. If it's not written, every request would be
    // a "new" uidmap with whatever username/uid combo is tried
    // thus allowing access to anyone and everyone. Not ideal.
    save_json($uid_map, 'idmap.json');
  }

  return $uid_map;
}

function enforce_user(object $user, object $config): void {
  // Allow admins to access anything
  if ($user->is_admin || !$user->needs_auth) {
    return;
  }

  // Allow anyone to access the ping page
  if ($user->is_valid && $_SERVER['SCRIPT_NAME'] === '/ping.php') {
    return;
  }

  // Redirect valid non-admin ping users from dashboard
  if ($user->is_valid && !$config->detailed_denials) {
    die(header('Location: ' . ping_page(), true, 307));
  }

  // Refuse to serve requests lacking correct UID header
  http_response_code($user->uid ? 401 : 403);

  if (!$config->detailed_denials) {
    exit;
  }

  $who = $user->username ? "user '{$user->username}'" : 'unknown user';
  $reason = $user->uid ? 'Unauthorised' : 'Missing auth headers';
  $reason = "Access denied for {$who}. Reason: {$reason}." . PHP_EOL;
  $user->uid && $reason .= PHP_EOL . PHP_EOL . 'Your UID: ' . $user->uid . PHP_EOL;

  header('Content-Type: text/plain');
  die($reason);
}

function login_status(object $user): string {
  if (!$user->needs_auth) {
    return '';
  }

  if (!$user->username || !$user->name) {
    return 'Unauthenticated';
  }

  return "Logged in as {$user->username} ({$user->name})";
}

function read_json_data($filename): array {
  file_exists('../../data/' . $filename)
    || throw new Exception("Missing data/{$filename}");

  $file = file_get_contents('../../data/' . $filename);
  is_string($file) || throw new Exception("Failed to open data/{$filename}.");

  try {
    return json_decode($file, true, flags: JSON_THROW_ON_ERROR);
  } catch (Exception $e) {
    throw new Exception("Failed to parse {$filename}: {$e->getMessage()}.");
  }
}

function list_from_file(): array {
  $list = read_json_data('list.json');

  $list['name'] || throw new Exception("Missing list name.");

  is_array($list['connections'] ?? '') && count($list['connections'])
    || throw new Exception("No valid connections found in JSON.");

  return $list;
}

function user_connections(array $list, object $user): array {
  if (!$user->needs_auth || $user->is_admin) {
    return $list['connections'];
  }

  return array_filter(
    $list['connections'],
    fn ($conn): bool =>
      in_array($user->username, $conn['users'] ?? []) || !isset($conn['users'])
  );
}

// Attempt to find Caddy in local Docker networks for automatic trust.
// Searches the default 172.16.0.0/12 subnet, excluding the .16 range.
function detect_proxy(): string {
  $octets = explode('.', $_SERVER['REMOTE_ADDR']);

  if (!str_starts_with($_SERVER['SERVER_SOFTWARE'] ?? '', 'Caddy/v2') ||
    172 !== (int) $octets[0] || 16 >= (int) $octets[1] || 32 <= (int) $octets[1]) {
    return '';
  }

  $host = gethostbyname('frontend');

  return $host === 'frontend' ? '' : $host;
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

function ping_page(): string {
    return needs_php_ext() ? '/ping.php' : '/ping';
}

function needs_php_ext(): bool {
  return str_contains($_SERVER['REQUEST_URI'], '.php');
}

function list_uris(object $config): array {
  $proto = ($_SERVER['HTTPS'] ?? null) && 'off' !== $_SERVER['HTTPS'] ? 'https' : 'http';
  $list_uri = needs_php_ext() ? 'list.php' : 'list';
  $proxies = $config->proxies ?? [];

  if (count($proxies) && in_array($_SERVER['REMOTE_ADDR'], $proxies)) {
    $fwdproto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $proto;
  }

  $fwdproto = $fwdproto ?? $proto;
  $localhost = $_SERVER['HOSTNAME'] ?? $_SERVER['SERVER_NAME'];

  return array_unique([
    "{$fwdproto}://{$_SERVER['HTTP_HOST']}/{$list_uri}",
    "{$proto}://{$localhost}:{$_SERVER['SERVER_PORT']}/{$list_uri}",
  ]);
}

function update_connection(array $list, string $connection, string $ip = ''): array {
  is_array($list['connections'][$connection] ?? null)
    || throw new Exception("Connection '{$connection}' is missing or invalid.");

  $new_ip = filter_var($ip, FILTER_VALIDATE_IP) ?: get_ip();
  $old_ip = $list['connections'][$connection]['ip'] ?? '';
  $new_ip === $old_ip && throw new Exception("Connection IP hasn't changed!");

  $list['connections'][$connection]['ip'] = $new_ip;
  $list['connections'][$connection]['since'] = time();

  return $list;
}

function save_json(array $list, string $file): bool {
  $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

  $json = json_encode($list, $flags);

  return file_put_contents("../../data/{$file}", $json)
    || throw new Exception("Failed to write JSON.");
}

$frontend = detect_proxy();

$config = (object) [
  'admins' => [],
  'auth_header' => '',
  'detailed_denials' => false,
  'proxies' => $frontend ? [$frontend] : [],
  'timezone' => 'Europe/London',
];

if (file_exists('../../data/config.json')) {
  $user_config = read_json_data('config.json');

  $config->admins = (array) ($user_config['admins'] ?? []);
  $config->auth_header = $user_config['auth_header'] ?? '';
  $config->detailed_denials = (bool) ($user_config['detailed_denials'] ?? false);

  $user_proxies = (array) ($user_config['proxy_ips'] ?? []);
  $config->proxies = array_merge($config->proxies, $user_proxies);
  $config->timezone = $user_config['timezone'] ?? $config->timezone;
}

date_default_timezone_set($config->timezone);

if ($_SERVER['SCRIPT_NAME'] !== '/list.php') {
  $user = user_from_headers($config);

  enforce_user($user, $config);
}
