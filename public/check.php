<?php
/**
 * Temporary diagnostics — delete after site works.
 */
header('Content-Type: text/plain; charset=utf-8');
echo "OK check.php\n";
echo "PHP=" . PHP_VERSION . "\n";
echo "DIR=" . __DIR__ . "\n";
echo "HOST=" . ($_SERVER['HTTP_HOST'] ?? '') . "\n";
echo "DOCROOT=" . ($_SERVER['DOCUMENT_ROOT'] ?? '') . "\n";
echo "helpers=" . (is_file(__DIR__ . '/app/helpers.php') ? 'YES' : 'NO') . "\n";
echo "bootstrap=" . (is_file(__DIR__ . '/app/bootstrap.php') ? 'YES' : 'NO') . "\n";
echo "index=" . (is_file(__DIR__ . '/index.php') ? 'YES' : 'NO') . "\n";

try {
    if (is_dir(__DIR__ . '/app')) {
        define('BASE_PATH', __DIR__);
    } else {
        define('BASE_PATH', dirname(__DIR__));
    }
    require BASE_PATH . '/app/helpers.php';
    echo "helpers_load=OK\n";
    echo "url_dil=" . url('/dil/az') . "\n";
    echo "request_path=" . request_path() . "\n";
} catch (Throwable $e) {
    echo "helpers_load=FAIL\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}
