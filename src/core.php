<?php

function list_from_file(): array {
  $file = file_get_contents('../../data/list.json');

  $json = json_decode($file, true);

  return $json;
}
