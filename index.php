<?php
/**
 * Fallback entry for shared hosting when document root cannot be set to /public.
 * Prefer pointing the domain document root to the /public folder.
 */
require __DIR__ . '/public/index.php';
