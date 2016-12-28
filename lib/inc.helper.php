<?php

function contactType2Str($type) {
  global $contactTypes;

  if (isset($contactTypes[strtolower($type)]))
    return $contactTypes[strtolower($type)];

  return $type;
}

function filterContact($details) {
  if (preg_match('/^-*$/', $details) === 1)
    return true;
  if (empty($details))
    return true;
  return false;
}

