<?php

foreach (Array("person","mailingliste","gruppe","gremium") as $i) {
  if (isset($_REQUEST["{$i}_start"])) $_COOKIE["{$i}_start"] = (int) $_REQUEST["{$i}_start"];
  if (isset($_REQUEST["{$i}_length"])) $_COOKIE["{$i}_length"] = (int) $_REQUEST["{$i}_length"];
  
  if (!isset($_COOKIE["{$i}_start"])) {
    $_COOKIE["{$i}_start"] = 0;
  }
  setcookie("{$i}_start", $_COOKIE["{$i}_start"]);
  if (!isset($_COOKIE["{$i}_length"])) {
    $_COOKIE["{$i}_length"] = 10;
  }
  setcookie("{$i}_length", $_COOKIE["{$i}_length"]);
}

