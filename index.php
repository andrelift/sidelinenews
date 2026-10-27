<?php
declare(strict_types=1);

require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/Aggregator.php';

date_default_timezone_set(config('site.timezone', 'UTC'));
send_security_headers();

$agg   = new Aggregator(config());
$route = current_route();
$parts = $route === '' ? [''] : explode('/', $route);
$per   = (int) config('per_page', 18);
$page  = max(1, (int) ($_GET['page'] ?? 1));

function show(string $view, array $vars, array $meta, array $all, Aggregator $agg, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: public, max-age=60');
    $content = render($view, $vars + ['all' => $all]);
    echo render('layout', $meta + ['content' => $content, 'all' => $all, 'built' => $agg->builtAt()]);
    exit;
}

/* Endpoints that don't need the story list first */
if ($parts[0] === 'refresh') {
    header('Content-Type: application/json');
    $key = (string) config('refresh_key', '');
    if ($key === '' || !hash_equals($key, (string) ($_GET['key'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
    echo json_encode($agg->refresh(true, true));
    exit;
}

$all = $agg->items();

switch ($parts[0]) {
    case '':
        $hero = [];
        $withImg = array_values(array_filter($all, fn($i) => !empty($i['image'])));
        $lead = $withImg[0] ?? ($all[0] ?? null);
        if ($lead) {
            $used = [$lead['sport']];
            $hero[$lead['id']] = $lead;
            foreach ($withImg as $it) {
                if (count($hero) >= 5) { break; }
                if (!isset($hero[$it['id']]) && !in_array($it['sport'], $used, true)) {
                    $hero[$it['id']] = $it; $used[] = $it['sport'];
                }
            }
            foreach ($all as $it) {
                if (count($hero) >= 5) { break; }
                $hero[$it['id']] ??= $it;
            }
        }
        $heroIds = array_keys($hero);
        $rest = array_values(array_filter($all, fn($i) => !in_array($i['id'], $heroIds, true)));
        show('home', ['hero' => array_values($hero), 'latest' => array_slice($rest, 0, 12)],
            ['title' => config('site.name') . ' — ' . config('site.tagline'), 'description' => config('site.tagline'), 'canonical' => abs_url(''), 'active' => ''],
            $all, $agg);

    case 'sport':
        $slug = $parts[1] ?? '';
        if (!isset(sports()[$slug])) { break; }
        $list = array_values(array_filter($all, fn($i) => $i['sport'] === $slug));
        [$slice, $page, $pages] = paginate($list, $page, $per);
        $label = sport_label($slug);
        show('sport', ['slug' => $slug, 'items' => $slice, 'page' => $page, 'pages' => $pages, 'total' => count($list)],
            ['title' => "$label news — " . config('site.name'), 'description' => "Latest $label headlines from top sources, updated automatically.", 'canonical' => abs_url("sport/$slug"), 'active' => $slug],
            $all, $agg);

    case 'story':
        $id = $parts[1] ?? '';
        $found = null;
        foreach ($all as $i) { if ($i['id'] === $id) { $found = $i; break; } }
        if (!$found) { break; }
        $related = array_slice(array_values(array_filter($all, fn($i) => $i['sport'] === $found['sport'] && $i['id'] !== $found['id'])), 0, 4);
        show('story', ['it' => $found, 'related' => $related],
            ['title' => $found['title'] . ' — ' . config('site.name'), 'description' => $found['summary'], 'canonical' => abs_url('story/' . $found['id']), 'og_image' => $found['image'], 'active' => $found['sport']],
            $all, $agg);

    case 'search':
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
        $res = [];
        if ($q !== '') {
            $terms = array_filter(preg_split('/\s+/u', $q));
            $res = array_values(array_filter($all, function ($i) use ($terms) {
                $hay = $i['title'] . ' ' . $i['summary'] . ' ' . $i['source'] . ' ' . sport_label($i['sport']);
                foreach ($terms as $t) { if (mb_stripos($hay, $t) === false) { return false; } }
                return true;
            }));
        }
        [$slice, $page, $pages] = paginate($res, $page, $per);
        show('search', ['q' => $q, 'items' => $slice, 'page' => $page, 'pages' => $pages, 'total' => count($res)],
            ['title' => ($q !== '' ? "“$q” — " : '') . 'Search — ' . config('site.name'), 'description' => 'Search sports headlines.', 'canonical' => abs_url('search'), 'active' => '', 'noindex' => true],
            $all, $agg);

    case 'about':
        show('about', [], ['title' => 'About — ' . config('site.name'), 'description' => 'How ' . config('site.name') . ' works.', 'canonical' => abs_url('about'), 'active' => ''], $all, $agg);

    case 'feed.xml':
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo render('feed', ['items' => array_slice($all, 0, 40)]);
        exit;

    case 'sitemap.xml':
        header('Content-Type: application/xml; charset=utf-8');
        echo render('sitemap', ['items' => array_slice($all, 0, 300)]);
        exit;
}

show('404', [], ['title' => 'Not found — ' . config('site.name'), 'description' => '', 'canonical' => abs_url(''), 'active' => '', 'noindex' => true], $all, $agg, 404);
