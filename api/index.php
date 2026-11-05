<?php

// Vercel entry point: every request is handed to Laravel.
//
// Vercel's disk is read-only except /tmp, so Laravel's compiled caches go there.
$writable = [
    'APP_CONFIG_CACHE'   => '/tmp/config.php',
    'APP_EVENTS_CACHE'   => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE'   => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
];
foreach ($writable as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv("$key=$value");
}
if (! is_dir('/tmp/views')) {
    mkdir('/tmp/views', 0755, true);
}

// Vercel terminates HTTPS before PHP sees the request. Tell Laravel it's secure,
// otherwise it generates http:// links and the browser blocks the CSS as mixed content.
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;

require __DIR__ . '/../public/index.php';
