<?php
declare(strict_types=1);

function config(?string $path = null, mixed $default = null): mixed
{
    static $c;
    $c ??= require __DIR__ . '/../config.php';
    if ($path === null) { return $c; }
    $v = $c;
    foreach (explode('.', $path) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) { return $default; }
        $v = $v[$k];
    }
    return $v;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    static $b;
    if ($b !== null) { return $b; }
    $d = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return $b = ($d === '/' || $d === '.') ? '' : rtrim($d, '/');
}

function site_url(): string
{
    if ($c = config('site.url')) { return rtrim($c, '/'); }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[a-z0-9.\-:\[\]]+$/i', $host)) { $host = 'localhost'; }
    return ($https ? 'https' : 'http') . '://' . $host . base_path();
}

function url(string $route = '', array $q = []): string
{
    $route = trim($route, '/');
    if (config('pretty_urls', true)) {
        $u = base_path() . '/' . $route;
    } else {
        $u = base_path() . '/index.php';
        if ($route !== '') { $q = ['route' => $route] + $q; }
    }
    return $u . ($q ? '?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986) : '');
}

function abs_url(string $route = '', array $q = []): string
{
    return preg_replace('#' . preg_quote(base_path(), '#') . '$#', '', site_url()) . url($route, $q);
}

function asset(string $file): string
{
    $p = __DIR__ . '/../assets/' . $file;
    return base_path() . '/assets/' . $file . (is_file($p) ? '?v=' . filemtime($p) : '');
}

function current_route(): string
{
    $route = $_GET['route'] ?? null;
    if ($route === null) {
        $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $b = base_path();
        if ($b !== '' && str_starts_with($path, $b)) { $path = substr($path, strlen($b)); }
        $route = $path;
    }
    $route = trim((string) $route, '/');
    return $route === 'index.php' ? '' : $route;
}

function sports(): array
{
    return config('sports', []);
}

function sport_label(string $slug): string
{
    return sports()[$slug][0] ?? ucfirst($slug);
}

function sport_color(string $slug): string
{
    return sports()[$slug][1] ?? '#888888';
}

function time_ago(int $ts): string
{
    $d = max(0, time() - $ts);
    if ($d < 60)    { return 'just now'; }
    if ($d < 3600)  { return (int) ($d / 60) . ' min ago'; }
    if ($d < 86400) { return (int) ($d / 3600) . ' h ago'; }
    if ($d < 604800){ return (int) ($d / 86400) . ' d ago'; }
    return date('j M Y', $ts);
}

function render(string $view, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    include __DIR__ . '/../views/' . $view . '.php';
    return (string) ob_get_clean();
}

function card(array $it, string $variant = 'card'): string
{
    return render('_card', ['it' => $it, 'variant' => $variant]);
}

function story_url(array $it): string
{
    return url('story/' . $it['id']);
}

function paginate(array $items, int $page, int $per): array
{
    $pages = max(1, (int) ceil(count($items) / $per));
    $page = min(max(1, $page), $pages);
    return [array_slice($items, ($page - 1) * $per, $per), $page, $pages];
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self' 'unsafe-inline'; script-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
}
