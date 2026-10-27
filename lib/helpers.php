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
    header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
}

function sport_icon(string $slug): string
{
    static $p = [
        'football'   => '<circle cx="12" cy="12" r="9"/><path d="M12 8.2l3.4 2.5-1.3 4H9.9l-1.3-4z"/><path d="M12 8.2V3.2M15.4 10.7l4.6-1.6M14.1 14.7l2.9 3.9M9.9 14.7l-2.9 3.9M8.6 10.7L4 9.1"/>',
        'basketball' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3v18M5.6 5.6c3 3 3 10.8 0 12.8M18.4 5.6c-3 3-3 10.8 0 12.8"/>',
        'nfl'        => '<ellipse cx="12" cy="12" rx="10" ry="6" transform="rotate(-35 12 12)"/><path d="M9.2 14.8l5.6-5.6M10.8 11.6l1.6 1.6M12.8 9.6l1.6 1.6M8.6 13.4l1.6 1.6"/>',
        'baseball'   => '<circle cx="12" cy="12" r="9"/><path d="M6 4.5c2.5 2.5 2.5 12.5 0 15M18 4.5c-2.5 2.5-2.5 12.5 0 15M7.5 8.5l2 .5M7.8 12h2.2M7.5 15.5l2-.5M16.5 8.5l-2 .5M16.2 12H14M16.5 15.5l-2-.5"/>',
        'hockey'     => '<path d="M5 3l8 14h6"/><path d="M5 3h3"/><ellipse cx="17" cy="20" rx="4" ry="1.6"/>',
        'tennis'     => '<circle cx="12" cy="12" r="9"/><path d="M4 6.5c4 1.5 6 5 6 11.5M20 17.5c-4-1.5-6-5-6-11.5"/>',
        'f1'         => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/><path d="M3.3 10.5l6.2.8M20.7 10.5l-6.2.8M12 14.5V21"/>',
        'cricket'    => '<path d="M4 20L14 10"/><path d="M12.5 8.5l3-3 3 3-3 3z"/><circle cx="18" cy="18" r="2.5"/>',
        'rugby'      => '<ellipse cx="12" cy="12" rx="10" ry="6.5" transform="rotate(-45 12 12)"/><path d="M8 16l8-8M10 10.5l3 3"/>',
        'golf'       => '<path d="M9 21V3l8 4-8 4"/><path d="M4 21h12"/><circle cx="19" cy="20" r="1.3"/>',
        'boxing'     => '<path d="M7 11V6.5A3.5 3.5 0 0 1 10.5 3h3A5.5 5.5 0 0 1 19 8.5V13a6 6 0 0 1-6 6h-2.5A3.5 3.5 0 0 1 7 15.5z"/><path d="M7 15H4v4h6M11 12h5"/>',
    ];
    $d = $p[$slug] ?? '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/>';
    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}
