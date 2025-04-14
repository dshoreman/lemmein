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
