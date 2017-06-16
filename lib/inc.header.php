<?php

// http://people.mozilla.com/~bsterne/content-security-policy/details.html
// https://wiki.mozilla.org/Security/CSP/Specification
header("X-Content-Security-Policy: allow 'self'; options inline-script eval-script");
header("X-Frame-Options: DENY");

// do not cache me
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

