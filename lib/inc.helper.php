<?php

function contactType2Str($type) {
  global $contactTypes;

  if (isset($contactTypes[strtolower($type)]))
    return $contactTypes[strtolower($type)];

  return $type;
}

function trimMe($d) {
  if (is_array($d)) {
    return array_map("trimMe", $d);
  } else {
    return trim($d);
  }
}
