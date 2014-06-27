<?php

function multiCurlRequest($data, $options = array()) {
  $result = array();
  $curlhandles = array();
  $curlhandle = curl_multi_init();

  foreach ($data as $id => $cdata) {
    $curlhandles[$id] = curl_init();

    $url = $cdata['url'];
    curl_setopt($curlhandles[$id], CURLOPT_URL,            $url);
    curl_setopt($curlhandles[$id], CURLOPT_HEADER,         0);
    curl_setopt($curlhandles[$id], CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($curlhandles[$id], CURLOPT_POST,       1);
    curl_setopt($curlhandles[$id], CURLOPT_POSTFIELDS, $cdata['post']);

    if (!empty($options)) {
      curl_setopt_array($curlhandles[$id], $options);
    }

    curl_multi_add_handle($curlhandle, $curlhandles[$id]);
  }

  $running = null;
  do {
    curl_multi_exec($curlhandle, $running);
  } while($running > 0);

  foreach($curlhandles as $id => $c) {
    $result[$id] = curl_multi_getcontent($c);
    curl_multi_remove_handle($curlhandle, $c);
  }

  curl_multi_close($curlhandle);

  return $result;
}

