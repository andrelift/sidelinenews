<?php
declare(strict_types=1);

/**
 * Fetches RSS/Atom feeds in parallel, normalises them, caches to JSON files
 * (no database) and serves a merged, de-duplicated, newest-first list.
 */
final class Aggregator
{
    private string $dir;

    public function __construct(private array $cfg)
    {
        $this->dir = rtrim($cfg['cache']['dir'], '/\\');
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    /** Returns merged stories; refreshes in-request when stale (if enabled). */
    public function items(): array
    {
        $file   = $this->dir . '/items.json';
        $merged = $this->readJson($file);
        $ttl    = (int) $this->cfg['cache']['ttl'];
        $fresh  = $merged && (time() - (int) ($merged['built'] ?? 0)) < $ttl;

        if (!$fresh && $this->cfg['cache']['refresh_on_request']) {
            $this->refresh(false, $merged === null);
            $merged = $this->readJson($file) ?? $merged;
        }
        return $merged['items'] ?? [];
    }

    public function builtAt(): int
    {
        return (int) ($this->readJson($this->dir . '/items.json')['built'] ?? 0);
    }

    /**
     * @return array|null stats, or null if another process holds the lock
     */
    public function refresh(bool $force = false, bool $block = false): ?array
    {
        $lock = @fopen($this->dir . '/refresh.lock', 'c');
        if (!$lock) {
            return null;
        }
        if (!flock($lock, LOCK_EX | ($block ? 0 : LOCK_NB))) {
            fclose($lock);
            return null;
        }
        try {
            $ttl = (int) $this->cfg['cache']['ttl'];
            $merged = $this->readJson($this->dir . '/items.json');
            if (!$force && $merged && (time() - (int) $merged['built']) < $ttl) {
                return ['skipped' => true];
            }

            $stale = [];
            foreach ($this->cfg['feeds'] as $i => $feed) {
                $c = $this->readJson($this->feedFile($feed));
                if ($force || !$c || (time() - (int) ($c['attempted'] ?? 0)) >= $ttl) {
                    $stale[$i] = $feed;
                }
            }

            $t0 = microtime(true);
            $bodies = $this->fetchAll($stale);
            $stats  = ['feeds' => count($this->cfg['feeds']), 'fetched' => 0, 'failed' => []];

            foreach ($stale as $i => $feed) {
                $file = $this->feedFile($feed);
                $old  = $this->readJson($file) ?? ['items' => []];
                $items = isset($bodies[$i]) ? $this->parse($bodies[$i], $feed) : [];
                if ($items) {
                    // keep images found earlier by enrichImages()
                    $prev = [];
                    foreach ($old['items'] ?? [] as $o) { $prev[$o['id']] = $o; }
                    foreach ($items as &$it) {
                        if (isset($prev[$it['id']])) {
                            $p = $prev[$it['id']];
                            if (!$it['image'] && !empty($p['image'])) { $it['image'] = $p['image']; }
                            if (!empty($p['imgtry'])) { $it['imgtry'] = $p['imgtry']; }
                        }
                    }
                    unset($it);
                    $this->writeJson($file, ['fetched' => time(), 'attempted' => time(), 'items' => $items]);
                    $stats['fetched']++;
                } else {
                    $old['attempted'] = time();
                    $this->writeJson($file, $old);
                    $stats['failed'][] = $feed['url'];
                }
            }

            $this->enrichImages($t0);
            $stats['items'] = $this->rebuild();
            return $stats;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function rebuild(): int
    {
        $cutoff = time() - (int) $this->cfg['cache']['max_age_days'] * 86400;
        $all = [];
        foreach ($this->cfg['feeds'] as $feed) {
            $c = $this->readJson($this->feedFile($feed));
            foreach ($c['items'] ?? [] as $it) {
                if ($it['published'] >= $cutoff) {
                    $all[] = $it;
                }
            }
        }
        usort($all, fn($a, $b) => $b['published'] <=> $a['published']);

        $seenTitle = $seenLink = $out = [];
        foreach ($all as $it) {
            $tk = preg_replace('/[^a-z0-9]+/', '', mb_strtolower($it['title']));
            if (isset($seenTitle[$tk]) || isset($seenLink[$it['link']])) {
                continue;
            }
            $seenTitle[$tk] = $seenLink[$it['link']] = true;
            $out[] = $it;
            if (count($out) >= (int) $this->cfg['cache']['max_items']) {
                break;
            }
        }
        $this->writeJson($this->dir . '/items.json', ['built' => time(), 'items' => $out]);
        return count($out);
    }

    /* ---------- fetching ---------- */

    private function fetchAll(array $feeds): array
    {
        $out = [];
        if (!$feeds) {
            return $out;
        }
        $ua = 'Mozilla/5.0 (compatible; SidelineBot/1.0; +feed-reader)';

        if (function_exists('curl_multi_init')) {
            $mh = curl_multi_init();
            $handles = [];
            foreach ($feeds as $i => $f) {
                $ch = curl_init($f['url']);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS      => 3,
                    CURLOPT_TIMEOUT        => (int) ($this->cfg['cache']['fetch_timeout'] ?? 10),
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_USERAGENT      => $ua,
                    CURLOPT_ENCODING       => '',
                    CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                    CURLOPT_HTTPHEADER     => ['Accept: application/rss+xml, application/atom+xml, application/xml;q=0.9, */*;q=0.8'],
                ]);
                curl_multi_add_handle($mh, $ch);
                $handles[$i] = $ch;
            }
            do {
                $status = curl_multi_exec($mh, $running);
                if ($running) {
                    curl_multi_select($mh, 1.0);
                }
            } while ($running && $status === CURLM_OK);

            foreach ($handles as $i => $ch) {
                $body = curl_multi_getcontent($ch);
                $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                if ($code >= 200 && $code < 300 && is_string($body) && $body !== '') {
                    $out[$i] = $body;
                }
                curl_multi_remove_handle($mh, $ch);
            }
            curl_multi_close($mh);
            return $out;
        }

        $ctx = stream_context_create(['http' => ['timeout' => (int) ($this->cfg['cache']['fetch_timeout'] ?? 10), 'user_agent' => $ua, 'follow_location' => 1]]);
        foreach ($feeds as $i => $f) {
            $body = @file_get_contents($f['url'], false, $ctx);
            if (is_string($body) && $body !== '') {
                $out[$i] = $body;
            }
        }
        return $out;
    }

    /* ---------- parsing ---------- */

    private function parse(string $xml, array $feed): array
    {
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$doc) {
            return [];
        }

        $nodes = [];
        if (isset($doc->channel->item)) {
            foreach ($doc->channel->item as $n) { $nodes[] = $n; }
        } elseif (isset($doc->entry)) {
            foreach ($doc->entry as $n) { $nodes[] = $n; }
        }

        $items = [];
        foreach ($nodes as $n) {
            $mrss = $n->children('http://search.yahoo.com/mrss/');
            $dc   = $n->children('http://purl.org/dc/elements/1.1/');
            $enc  = (string) $n->children('http://purl.org/rss/1.0/modules/content/')->encoded;

            $title = $this->text((string) $n->title);
            $link  = $this->itemLink($n);
            if ($title === '' || $link === '') {
                continue;
            }

            $rawDesc = (string) ($n->description ?? $n->summary ?? '');
            if ($rawDesc === '') { $rawDesc = (string) ($n->content ?? ''); }
            $summary = $this->text($rawDesc);
            $summary = preg_replace('/\s*(Continue reading|Read more)\.{0,3}\s*$/i', '', $summary);

            $date = (string) ($n->pubDate ?? $n->published ?? $n->updated ?? $dc->date ?? '');
            $ts = $date !== '' ? (strtotime($date) ?: time()) : time();

            $items[] = [
                'id'        => substr(sha1($link), 0, 12),
                'title'     => $title,
                'link'      => $link,
                'summary'   => $this->truncate($summary, 320),
                'image'     => $this->itemImage($n, $mrss, $rawDesc . $enc),
                'source'    => $feed['source'],
                'sport'     => $feed['sport'],
                'published' => min($ts, time()),
            ];
        }
        return $items;
    }

    private function itemLink(\SimpleXMLElement $n): string
    {
        $link = trim((string) $n->link);
        if ($link === '') {
            foreach ($n->link as $l) {
                $a = $l->attributes();
                $rel = (string) $a['rel'];
                if ($rel === '' || $rel === 'alternate') { $link = (string) $a['href']; break; }
            }
        }
        if ($link === '' && isset($n->guid)) {
            $link = trim((string) $n->guid);
        }
        return preg_match('#^https?://#i', $link) ? $link : '';
    }

    private function itemImage(\SimpleXMLElement $n, \SimpleXMLElement $mrss, string $html): ?string
    {
        $best = null;
        $bestW = -1;
        foreach ([$mrss->content, $mrss->thumbnail] as $set) {
            foreach ($set as $m) {
                $a = $m->attributes();
                $u = (string) $a['url'];
                $type = (string) $a['type'];
                $medium = (string) $a['medium'];
                if ($u === '' || ($type !== '' && !str_starts_with($type, 'image')) || ($medium !== '' && $medium !== 'image')) {
                    continue;
                }
                if ((int) $a['width'] > $bestW) { $best = $u; $bestW = (int) $a['width']; }
            }
        }
        if (!$best && isset($n->enclosure)) {
            $a = $n->enclosure->attributes();
            if (str_starts_with((string) $a['type'], 'image')) { $best = (string) $a['url']; }
        }
        if (!$best && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
            $best = html_entity_decode($m[1]);
        }
        return $best ? $this->cleanImage($best) : null;
    }

    private function cleanImage(string $u): ?string
    {
        $u = trim(html_entity_decode($u));
        if (str_starts_with($u, '//')) { $u = 'https:' . $u; }
        if (!preg_match('#^https?://#i', $u)) { return null; }
        if (preg_match('#(logo|default|placeholder|fallback|favicon|sprite|blank)#i', $u)) { return null; }
        // BBC serves 240px thumbs by default: ask for a bigger rendition.
        return preg_replace('#(ichef\.bbci\.co\.uk/[^?]*?/)(?:240|320)/#', '${1}800/', $u);
    }

    /* ---------- image enrichment (og:image from the article page) ---------- */

    /** Some feeds (e.g. ESPN) ship no images. Look at the article page for og:image. */
    private function enrichImages(float $t0): void
    {
        $c = $this->cfg['cache'];
        if (empty($c['enrich_images']) || microtime(true) - $t0 > (float) ($c['enrich_deadline'] ?? 6)) {
            return;
        }
        $files = $cands = [];
        $perSport = [];
        foreach ($this->cfg['feeds'] as $feed) {
            $file = $this->feedFile($feed);
            if (isset($files[$file])) { continue; }
            $d = $this->readJson($file);
            if (!$d) { continue; }
            $files[$file] = $d;
            foreach ($d['items'] as $ii => $it) {
                if (empty($it['image']) && (empty($it['imgtry']) || time() - $it['imgtry'] > 21600)) {
                    $cands[] = [$it['published'], $file, $ii, $it['sport']];
                }
            }
        }
        if (!$cands) { return; }
        usort($cands, fn($a, $b) => $b[0] <=> $a[0]);
        $max = (int) ($c['enrich_max'] ?? 30);
        $pick = [];
        foreach ($cands as $cand) {
            if (count($pick) >= $max) { break; }
            $perSport[$cand[3]] = ($perSport[$cand[3]] ?? 0) + 1;
            if ($perSport[$cand[3]] <= 3) { $pick[] = $cand; }   // spread across sports
        }
        $urls = [];
        foreach ($pick as $k => [$ts, $file, $ii]) { $urls[$k] = $files[$file]['items'][$ii]['link']; }
        $pages = $this->fetchHeads($urls, (int) ($c['enrich_timeout'] ?? 5));
        foreach ($pick as $k => [$ts, $file, $ii]) {
            $img = isset($pages[$k]) ? $this->ogImage($pages[$k]) : null;
            if ($img) { $files[$file]['items'][$ii]['image'] = $img; }
            $files[$file]['items'][$ii]['imgtry'] = time();
        }
        foreach ($files as $file => $d) { $this->writeJson($file, $d); }
    }

    private function ogImage(string $html): ?string
    {
        foreach (['og:image:secure_url', 'og:image', 'twitter:image'] as $prop) {
            $q = preg_quote($prop, '/');
            if (preg_match('/<meta[^>]+(?:property|name)=["\']' . $q . '["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m)
                || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]*(?:property|name)=["\']' . $q . '["\']/i', $html, $m)) {
                if ($img = $this->cleanImage($m[1])) { return $img; }
            }
        }
        return null;
    }

    /** Downloads only the <head> of each page (parallel, size-capped). */
    private function fetchHeads(array $urls, int $timeout): array
    {
        if (!$urls || !function_exists('curl_multi_init')) { return []; }
        $mh = curl_multi_init();
        $bufs = $handles = [];
        foreach ($urls as $k => $u) {
            $bufs[$k] = '';
            $ch = curl_init($u);
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SidelineBot/1.0)',
                CURLOPT_ENCODING       => '',
                CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
                CURLOPT_WRITEFUNCTION  => function ($ch, $data) use (&$bufs, $k) {
                    $bufs[$k] .= $data;
                    return (strlen($bufs[$k]) > 300000 || stripos($bufs[$k], '</head>') !== false) ? 0 : strlen($data);
                },
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$k] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) { curl_multi_select($mh, 1.0); }
        } while ($running && $status === CURLM_OK);
        foreach ($handles as $ch) { curl_multi_remove_handle($mh, $ch); }
        curl_multi_close($mh);
        return array_filter($bufs, fn($b) => $b !== '');
    }

    private function text(string $s): string
    {
        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/u', ' ', $s));
    }

    private function truncate(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) { return $s; }
        $cut = mb_substr($s, 0, $max);
        $sp = mb_strrpos($cut, ' ');
        return rtrim($sp ? mb_substr($cut, 0, $sp) : $cut, " ,;:-") . '…';
    }

    /* ---------- storage ---------- */

    private function feedFile(array $feed): string
    {
        return $this->dir . '/feed-' . md5($feed['url']) . '.json';
    }

    private function readJson(string $file): ?array
    {
        $raw = @file_get_contents($file);
        $d = $raw ? json_decode($raw, true) : null;
        return is_array($d) ? $d : null;
    }

    private function writeJson(string $file, array $data): void
    {
        $tmp = $file . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
            @rename($tmp, $file);
        }
    }
}
