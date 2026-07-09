<?php
/**
 * Temporary diagnostic — delete after fixing HTTPS redirect.
 * Open: https://www.esinaq.net/redirect-check.php
 * And:  http://www.esinaq.net/redirect-check.php
 */
header('Content-Type: text/plain; charset=utf-8');
echo "HTTPS=" . ($_SERVER['HTTPS'] ?? '(none)') . "\n";
echo "X-Forwarded-Proto=" . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '(none)') . "\n";
echo "SERVER_PORT=" . ($_SERVER['SERVER_PORT'] ?? '(none)') . "\n";
echo "HTTP_HOST=" . ($_SERVER['HTTP_HOST'] ?? '(none)') . "\n";
echo "REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '(none)') . "\n";
echo "REQUEST_SCHEME=" . ($_SERVER['REQUEST_SCHEME'] ?? '(none)') . "\n";
