<?php

function contactType2Str($type) {
  global $contactTypes;

  if (isset($contactTypes[strtolower($type)]))
    return $contactTypes[strtolower($type)];

  return $type;
}

