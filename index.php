<?php
/**
 * Front controller — the single entry point for the whole app.
 *
 * The web server rewrites every request that isn't a real file (see
 * .htaccess for Apache and web.config for IIS) to this script, which maps
 * the clean path to a handler in routes.php. Page files bounce direct hits
 * back here via ensure_routed(), so each page has exactly one canonical URL.
 *
 * Real files are served by the web server before this runs, which is what
 * keeps /styles, /scripts, /assets and the /api/*.php endpoints working.
 */

// Page files check this to tell a routed include from a direct hit.
define('IMS_ROUTED', true);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth/session.php';

$routes = require __DIR__ . '/routes.php';

$path   = request_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// App root — send the visitor wherever their session says they belong.
if ($path === '') {
    header('Location: ' . url(is_logged_in() ? 'dashboard' : 'login'));
    exit;
}

if (!isset($routes[$path])) {
    http_response_code(404);
    require __DIR__ . '/pages/404.php';
    exit;
}

$route = $routes[$path];

if (is_array($route)) {
    // Method-specific route (e.g. GET /login renders the form, POST /login
    // hands off to the credential processor).
    if (!isset($route[$method])) {
        http_response_code(405);
        header('Allow: ' . implode(', ', array_keys($route)));
        exit;
    }
    $handler = $route[$method];
} else {
    $handler = $route;
}

$file = __DIR__ . '/' . $handler;

if (!is_file($file)) {
    error_log("Front controller: route '$path' points at missing file '$handler'");
    http_response_code(500);
    exit;
}

require $file;
