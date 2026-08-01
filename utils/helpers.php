<?php
/**
 * General-purpose helpers for the IMS front end.
 */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function is_active(string $page): string
{
    global $activePage;
    return ($activePage ?? '') === $page ? 'active' : '';
}

/**
 * Build an absolute app URL for a front-controller route.
 *
 *   CLEAN_URLS = false → url('inventory') = /ims/index.php?page=inventory
 *   CLEAN_URLS = true  → url('inventory') = /ims/inventory
 *
 * Extra query parameters go through $query rather than being concatenated by
 * the caller, since the query-string style already spends the "?".
 */
function url(string $path = '', array $query = []): string
{
    $base = rtrim(BASE_URL, '/');
    $path = trim($path, '/');

    if ($path === '') {
        $url = $base . '/';
    } elseif (CLEAN_URLS) {
        $url = $base . '/' . $path;
    } else {
        // Slashes stay literal so nested routes stay readable
        // (?page=api/users rather than ?page=api%2Fusers).
        $url = $base . '/index.php?page=' . str_replace('%2F', '/', rawurlencode($path));
    }

    if ($query) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
    }

    return $url;
}

/**
 * The requested route, accepting all three entry styles so the app works
 * with or without a URL-rewriting module:
 *
 *   1. ?page=dashboard        — no server config needed
 *   2. /index.php/dashboard   — PATH_INFO, no server config needed
 *   3. /dashboard             — rewritten by mod_rewrite / IIS URL Rewrite
 *
 * The app root resolves to "".
 */
function request_path(): string
{
    if (isset($_GET['page']) && is_string($_GET['page'])) {
        return trim($_GET['page'], '/');
    }

    if (!empty($_SERVER['PATH_INFO'])) {
        return trim($_SERVER['PATH_INFO'], '/');
    }

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = rawurldecode($uri ?: '/');

    $base = rtrim(BASE_URL, '/');
    if ($base !== '' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }

    $uri = trim($uri, '/');

    // Hitting the entry point itself carries no route — treat it as the root
    // rather than letting it fall through to a 404.
    return $uri === 'index.php' ? '' : $uri;
}

/**
 * Keep the physical .php files from being reachable at their own paths now
 * that every request is supposed to come through the front controller —
 * a direct hit is bounced to the canonical route instead.
 *
 * Deliberately a redirect rather than a hard failure: if the rewrite module
 * is ever missing on the host, a stray direct URL still lands somewhere
 * useful instead of a blank 500.
 */
function ensure_routed(string $canonical): void
{
    if (defined('IMS_ROUTED')) {
        return;
    }

    // Carry the existing query string over, minus the router's own 'page'
    // key so the two URL styles can't end up fighting over it.
    $params = $_GET;
    unset($params['page']);

    header('Location: ' . url($canonical, $params), true, 302);
    exit;
}
