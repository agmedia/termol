<?php

declare(strict_types=1);

use App\Services\Integrations\Kipos\KiposSdkService;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

@set_time_limit(0);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/**
 * @param  array<string, mixed>  $row
 */
function kipos_image_string(array $row, string ...$keys): string
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row)) {
            return trim((string) $row[$key]);
        }
    }

    return '';
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_image_item_code(array $row): string
{
    return strtoupper(kipos_image_string($row, 'IDROBA'));
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_image_department_code(array $row): string
{
    $department = strtoupper(kipos_image_string($row, 'IDODJEL'));
    if ($department !== '') {
        return $department;
    }

    $itemCode = kipos_image_item_code($row);
    $dotPosition = strrpos($itemCode, '.');

    return $dotPosition === false ? $itemCode : substr($itemCode, 0, $dotPosition);
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_image_bool(array $row, string $key): bool
{
    if (! array_key_exists($key, $row)) {
        return false;
    }

    $value = filter_var($row[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    if ($value !== null) {
        return $value;
    }

    return in_array(strtoupper(trim((string) $row[$key])), ['1', 'DA', 'YES', 'Y'], true);
}

function kipos_image_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kipos_image_file_name(string $url): string
{
    $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
    $file = basename($path);

    return $file !== '' ? urldecode($file) : $url;
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_image_url(KiposSdkService $kipos, array $row): ?string
{
    foreach (['URL', 'url', 'SLIKA', 'slika', 'LINK', 'link', 'DATOTEKA', 'NAZIV_DATOTEKE'] as $key) {
        $raw = kipos_image_string($row, $key);
        if ($raw === '') {
            continue;
        }

        $url = $kipos->resolveImageUrl($raw);
        if ($url !== null) {
            return $url;
        }
    }

    return null;
}

/**
 * @param  array<int, string>  $errors
 * @return list<array<string, mixed>>
 */
function kipos_image_fetch_rows(KiposSdkService $kipos, string $route, array &$errors): array
{
    try {
        $rows = $kipos->getRows($route);
    } catch (Throwable $exception) {
        $errors[] = $route.': '.$exception->getMessage();

        return [];
    }

    foreach ($rows as &$row) {
        $row['_source_route'] = $route;
    }
    unset($row);

    return $rows;
}

/**
 * @param  list<array<string, mixed>>  ...$rowSets
 * @return array<string, array{code:string,name:string,item_codes:array<int,string>,items:array<string,array<string,mixed>>}>
 */
function kipos_image_product_groups(array ...$rowSets): array
{
    $mergedItems = [];

    foreach ($rowSets as $rows) {
        foreach ($rows as $row) {
            $itemCode = kipos_image_item_code($row);
            if ($itemCode === '') {
                continue;
            }

            $mergedItems[$itemCode] = array_merge($mergedItems[$itemCode] ?? [], $row);
        }
    }

    $groups = [];

    foreach ($mergedItems as $itemCode => $row) {
        $groupCode = kipos_image_department_code($row);
        if ($groupCode === '') {
            continue;
        }

        $groups[$groupCode] ??= [
            'code' => $groupCode,
            'name' => '',
            'item_codes' => [],
            'items' => [],
        ];

        $name = kipos_image_string($row, 'NAZIV_ODJELA', 'NAZIV');
        if ($groups[$groupCode]['name'] === '' && $name !== '') {
            $groups[$groupCode]['name'] = $name;
        }

        $groups[$groupCode]['item_codes'][] = $itemCode;
        $groups[$groupCode]['items'][$itemCode] = $row;
    }

    foreach ($groups as &$group) {
        $group['item_codes'] = array_values(array_unique($group['item_codes']));
        sort($group['item_codes'], SORT_NATURAL | SORT_FLAG_CASE);
    }
    unset($group);

    ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

    return $groups;
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<array<string, mixed>>
 */
function kipos_image_normalize_rows(KiposSdkService $kipos, array $rows): array
{
    $normalized = [];

    foreach ($rows as $row) {
        $tip = strtoupper(kipos_image_string($row, 'TIP'));
        if ($tip !== '' && $tip !== 'SLIKA') {
            continue;
        }

        $url = kipos_image_url($kipos, $row);
        if ($url === null) {
            continue;
        }

        $groupCode = kipos_image_department_code($row);
        $itemCode = kipos_image_item_code($row);

        if ($groupCode === '' && $itemCode !== '') {
            $dotPosition = strrpos($itemCode, '.');
            $groupCode = $dotPosition === false ? $itemCode : substr($itemCode, 0, $dotPosition);
        }

        if ($groupCode === '') {
            $groupCode = '_BEZ_SIFRE_';
        }

        $row['URL'] = $url;
        $row['_group_code'] = strtoupper($groupCode);
        $row['_item_code'] = $itemCode;
        $row['_file_name'] = kipos_image_file_name($url);
        $normalized[] = $row;
    }

    return $normalized;
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<array<string, mixed>>
 */
function kipos_image_dedupe_rows(array $rows): array
{
    $deduped = [];

    foreach ($rows as $row) {
        $key = strtoupper((string) ($row['_group_code'] ?? '')).'|'
            .strtolower((string) ($row['URL'] ?? '')).'|'
            .strtolower(kipos_image_string($row, 'NAZIV', 'OPIS', '_file_name'));

        if (isset($deduped[$key])) {
            $routes = (array) ($deduped[$key]['_source_routes'] ?? []);
            $routes[] = (string) ($row['_source_route'] ?? '');
            $deduped[$key]['_source_routes'] = array_values(array_unique(array_filter($routes)));
            continue;
        }

        $row['_source_routes'] = array_values(array_filter([(string) ($row['_source_route'] ?? '')]));
        $deduped[$key] = $row;
    }

    return array_values($deduped);
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return array<string, list<array<string, mixed>>>
 */
function kipos_image_group_images(array $rows): array
{
    $groups = [];

    foreach ($rows as $row) {
        $groupCode = strtoupper((string) ($row['_group_code'] ?? '_BEZ_SIFRE_'));
        $groups[$groupCode] ??= [];
        $groups[$groupCode][] = $row;
    }

    foreach ($groups as &$images) {
        usort($images, static function (array $left, array $right): int {
            $mainCompare = (kipos_image_bool($right, 'GLAVNA') <=> kipos_image_bool($left, 'GLAVNA'));
            if ($mainCompare !== 0) {
                return $mainCompare;
            }

            return strnatcasecmp(
                kipos_image_string($left, 'NAZIV', '_file_name', 'URL'),
                kipos_image_string($right, 'NAZIV', '_file_name', 'URL')
            );
        });
    }
    unset($images);

    ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

    return $groups;
}

/**
 * @param  list<array<string, mixed>>  $images
 */
function kipos_image_matches_query(string $query, string $groupCode, string $name, array $itemCodes, array $images): bool
{
    $query = trim($query);
    if ($query === '') {
        return true;
    }

    $haystack = $groupCode.' '.$name.' '.implode(' ', $itemCodes).' '
        .(json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

    return stripos($haystack, $query) !== false;
}

/**
 * @param  array<int, string>  $itemCodes
 * @return list<array<string, mixed>>
 */
function kipos_image_specific_rows(KiposSdkService $kipos, string $code, array $itemCodes, array &$errors): array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return [];
    }

    $routes = [
        'sif_roba/getItemOdjelSlike/'.$code,
        'sif_roba/getOdjelItemsSlike/'.$code,
        'sif_roba/getOdjelSlike/'.$code,
    ];

    foreach (array_unique(array_merge([$code], $itemCodes)) as $itemCode) {
        $itemCode = strtoupper(trim((string) $itemCode));
        if ($itemCode === '') {
            continue;
        }

        $routes[] = 'sif_roba/getSlike/'.$itemCode;
        $routes[] = 'sif_roba/getItemSlike/'.$itemCode;
    }

    $rows = [];
    foreach (array_values(array_unique($routes)) as $route) {
        $rows = array_merge($rows, kipos_image_fetch_rows($kipos, $route, $errors));
    }

    $knownCodes = array_values(array_unique(array_merge([$code], $itemCodes)));
    $normalized = kipos_image_normalize_rows($kipos, $rows);
    $filtered = array_values(array_filter($normalized, static function (array $row) use ($code, $knownCodes): bool {
        $groupCode = strtoupper((string) ($row['_group_code'] ?? ''));
        $itemCode = strtoupper((string) ($row['_item_code'] ?? ''));

        if ($groupCode === $code || in_array($itemCode, $knownCodes, true)) {
            return true;
        }

        return $groupCode === '_BEZ_SIFRE_';
    }));

    return kipos_image_dedupe_rows($filtered);
}

/**
 * @param  array<string, list<array<string, mixed>>>  $imageGroups
 * @param  array<string, array{code:string,name:string,item_codes:array<int,string>,items:array<string,array<string,mixed>>}>  $productGroups
 * @return list<array{code:string,name:string,item_codes:array<int,string>,images:list<array<string,mixed>>,source:string}>
 */
function kipos_image_rows_for_screen(array $productGroups, array $imageGroups, string $query, bool $onlyWithImages): array
{
    $rows = [];
    $seen = [];

    foreach ($productGroups as $groupCode => $productGroup) {
        $groupCode = (string) $groupCode;
        $images = $imageGroups[$groupCode] ?? [];
        if ($onlyWithImages && $images === []) {
            continue;
        }

        if (! kipos_image_matches_query($query, $groupCode, $productGroup['name'], $productGroup['item_codes'], $images)) {
            continue;
        }

        $rows[] = [
            'code' => $groupCode,
            'name' => $productGroup['name'],
            'item_codes' => $productGroup['item_codes'],
            'images' => $images,
            'source' => 'artikl',
        ];
        $seen[$groupCode] = true;
    }

    foreach ($imageGroups as $groupCode => $images) {
        $groupCode = (string) $groupCode;
        if (isset($seen[$groupCode])) {
            continue;
        }

        if (! kipos_image_matches_query($query, $groupCode, '', [], $images)) {
            continue;
        }

        $rows[] = [
            'code' => $groupCode,
            'name' => '',
            'item_codes' => [],
            'images' => $images,
            'source' => 'samo slike',
        ];
    }

    usort($rows, static fn (array $left, array $right): int => strnatcasecmp($left['code'], $right['code']));

    return $rows;
}

/**
 * @param  list<array{code:string,name:string,item_codes:array<int,string>,images:list<array<string,mixed>>,source:string}>  $rows
 */
function kipos_image_output_csv(array $rows): void
{
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="kipos-slike.csv"');
    }

    $out = fopen('php://output', 'wb');
    fputcsv($out, ['IDODJEL', 'NAZIV', 'IDROBA', 'BROJ_SLIKA', 'GLAVNA', 'NAZIV_SLIKE', 'URL', 'RUTE']);

    foreach ($rows as $group) {
        if ($group['images'] === []) {
            fputcsv($out, [
                $group['code'],
                $group['name'],
                implode(', ', $group['item_codes']),
                0,
                '',
                '',
                '',
                '',
            ]);
            continue;
        }

        foreach ($group['images'] as $image) {
            fputcsv($out, [
                $group['code'],
                $group['name'],
                kipos_image_string($image, '_item_code'),
                count($group['images']),
                kipos_image_bool($image, 'GLAVNA') ? 'DA' : 'NE',
                kipos_image_string($image, 'NAZIV', 'OPIS', '_file_name'),
                (string) ($image['URL'] ?? ''),
                implode(', ', (array) ($image['_source_routes'] ?? [])),
            ]);
        }
    }

    fclose($out);
}

$kipos = app(KiposSdkService::class);
$settings = $kipos->getSettings();
$errors = [];

$baseRows = kipos_image_fetch_rows($kipos, 'sif_roba/getitems', $errors);
$extendedRows = kipos_image_fetch_rows($kipos, 'sif_roba/getitemsextended', $errors);
$productGroups = kipos_image_product_groups($baseRows, $extendedRows);

$globalImageRows = [];
$globalEndpointCounts = [];
foreach (['sif_roba/getOdjelSlike', 'sif_roba/getSlike'] as $route) {
    $rows = kipos_image_fetch_rows($kipos, $route, $errors);
    $globalEndpointCounts[$route] = count($rows);
    $globalImageRows = array_merge($globalImageRows, $rows);
}

$normalizedImages = kipos_image_normalize_rows($kipos, $globalImageRows);
$uniqueImages = kipos_image_dedupe_rows($normalizedImages);
$imageGroups = kipos_image_group_images($uniqueImages);

$query = trim((string) ($_GET['q'] ?? ''));
$onlyWithImages = (string) ($_GET['only_with_images'] ?? '') === '1';
$detailCode = strtoupper(trim((string) ($_GET['code'] ?? '')));
$screenRows = kipos_image_rows_for_screen($productGroups, $imageGroups, $query, $onlyWithImages);
$specificRows = $detailCode !== ''
    ? kipos_image_specific_rows($kipos, $detailCode, $productGroups[$detailCode]['item_codes'] ?? [], $errors)
    : [];

if (PHP_SAPI === 'cli' || (string) ($_GET['format'] ?? '') === 'csv') {
    kipos_image_output_csv($screenRows);
    exit;
}

$articlesWithImages = 0;
foreach ($productGroups as $groupCode => $group) {
    if (($imageGroups[$groupCode] ?? []) !== []) {
        $articlesWithImages++;
    }
}

$articlesWithoutImages = max(0, count($productGroups) - $articlesWithImages);
$csvQuery = http_build_query(array_filter([
    'q' => $query,
    'only_with_images' => $onlyWithImages ? '1' : '',
    'format' => 'csv',
], static fn ($value): bool => $value !== null && $value !== ''));
$resetPath = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: 'kipos-slike.php';

?>
<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kipos slike po artiklu</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f7f9;
            --panel: #ffffff;
            --line: #d9dee7;
            --text: #172033;
            --muted: #647084;
            --accent: #146c94;
            --danger: #b42318;
            --ok: #13795b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
        }

        main {
            width: min(1560px, calc(100vw - 32px));
            margin: 28px auto;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 26px;
            font-weight: 700;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 18px;
        }

        .muted {
            color: var(--muted);
        }

        .meta,
        .toolbar,
        .errors,
        .detail,
        .table-wrap {
            margin-top: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            padding: 14px;
        }

        .label {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
        }

        .value {
            margin-top: 4px;
            font-weight: 700;
            word-break: break-word;
        }

        .toolbar,
        .detail,
        .errors {
            padding: 14px;
        }

        .toolbar {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(260px, 420px) auto;
            gap: 10px;
            align-items: center;
        }

        form {
            display: flex;
            gap: 8px;
            min-width: 0;
        }

        input {
            min-width: 0;
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 9px 10px;
            font: inherit;
        }

        button,
        a.button {
            border: 1px solid var(--accent);
            border-radius: 6px;
            background: var(--accent);
            color: #fff;
            padding: 9px 12px;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        a.secondary {
            border-color: var(--line);
            background: #fff;
            color: var(--text);
        }

        .errors {
            border-color: #f1b8b3;
            color: var(--danger);
        }

        .table-wrap {
            overflow: auto;
        }

        table {
            width: 100%;
            min-width: 1180px;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid var(--line);
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #eef2f6;
            color: #2d384b;
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        tr:nth-child(even) td {
            background: #fafbfc;
        }

        .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            background: #e8f3f8;
            color: #0d5978;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge.ok {
            background: #e8f6f1;
            color: var(--ok);
        }

        .badge.empty {
            background: #fff1f0;
            color: var(--danger);
        }

        .items {
            max-width: 280px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            min-width: 420px;
        }

        .image-card {
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .image-card img {
            display: block;
            width: 100%;
            height: 110px;
            object-fit: contain;
            background: #f1f3f6;
        }

        .image-body {
            padding: 8px;
            font-size: 12px;
            line-height: 1.35;
        }

        .image-name {
            font-weight: 700;
            word-break: break-word;
        }

        .image-link {
            display: block;
            margin-top: 4px;
            color: var(--accent);
            word-break: break-all;
        }

        .routes {
            margin-top: 4px;
            color: var(--muted);
            word-break: break-word;
        }

        @media (max-width: 900px) {
            .toolbar {
                grid-template-columns: 1fr;
            }

            form {
                flex-wrap: wrap;
            }

            button,
            a.button {
                width: auto;
            }
        }
    </style>
</head>
<body>
<main>
    <h1>Kipos slike po artiklu</h1>
    <div class="muted">Pregled cita Kipos slike direktno kroz postojece Laravel Kipos postavke.</div>

    <section class="meta" aria-label="Sazetak">
        <div>
            <div class="label">Kipos artikala</div>
            <div class="value"><?= kipos_image_escape(count($productGroups)) ?></div>
        </div>
        <div>
            <div class="label">Artikala sa slikama</div>
            <div class="value"><?= kipos_image_escape($articlesWithImages) ?></div>
        </div>
        <div>
            <div class="label">Artikala bez slika</div>
            <div class="value"><?= kipos_image_escape($articlesWithoutImages) ?></div>
        </div>
        <div>
            <div class="label">Sirovih image redova</div>
            <div class="value"><?= kipos_image_escape(count($normalizedImages)) ?></div>
        </div>
        <div>
            <div class="label">Unikatnih slika</div>
            <div class="value"><?= kipos_image_escape(count($uniqueImages)) ?></div>
        </div>
        <div>
            <div class="label">Prikazano artikala</div>
            <div class="value"><?= kipos_image_escape(count($screenRows)) ?></div>
        </div>
        <div>
            <div class="label">Kipos image base</div>
            <div class="value"><?= kipos_image_escape((string) ($settings['kipos_api_image_base_uri'] ?? '')) ?></div>
        </div>
        <div>
            <div class="label">Osvjezeno</div>
            <div class="value"><?= kipos_image_escape(now()->format('Y-m-d H:i:s')) ?></div>
        </div>
    </section>

    <section class="meta" aria-label="Endpointi">
        <?php foreach ($globalEndpointCounts as $route => $count): ?>
            <div>
                <div class="label"><?= kipos_image_escape($route) ?></div>
                <div class="value"><?= kipos_image_escape($count) ?> redova</div>
            </div>
        <?php endforeach; ?>
    </section>

    <?php if ($errors !== []): ?>
        <section class="errors">
            <strong>Kipos dohvat nije prosao za sve endpointove:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= kipos_image_escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="toolbar">
        <form method="get">
            <input type="search" name="q" value="<?= kipos_image_escape($query) ?>" placeholder="Trazi sifru, naziv slike, URL...">
            <?php if ($onlyWithImages): ?>
                <input type="hidden" name="only_with_images" value="1">
            <?php endif; ?>
            <?php if ($detailCode !== ''): ?>
                <input type="hidden" name="code" value="<?= kipos_image_escape($detailCode) ?>">
            <?php endif; ?>
            <button type="submit">Trazi</button>
            <?php if ($query !== ''): ?>
                <a class="button secondary" href="<?= kipos_image_escape($resetPath) ?>">Reset</a>
            <?php endif; ?>
        </form>

        <form method="get">
            <input type="text" name="code" value="<?= kipos_image_escape($detailCode) ?>" placeholder="Detaljna provjera sifre, npr. W7030">
            <?php if ($query !== ''): ?>
                <input type="hidden" name="q" value="<?= kipos_image_escape($query) ?>">
            <?php endif; ?>
            <?php if ($onlyWithImages): ?>
                <input type="hidden" name="only_with_images" value="1">
            <?php endif; ?>
            <button type="submit">Provjeri</button>
        </form>

        <div>
            <?php if ($onlyWithImages): ?>
                <a class="button secondary" href="?<?= kipos_image_escape(http_build_query(array_filter(['q' => $query, 'code' => $detailCode]))) ?>">Prikazi sve</a>
            <?php else: ?>
                <a class="button secondary" href="?<?= kipos_image_escape(http_build_query(array_filter(['q' => $query, 'code' => $detailCode, 'only_with_images' => '1']))) ?>">Samo sa slikama</a>
            <?php endif; ?>
            <a class="button secondary" href="?<?= kipos_image_escape($csvQuery) ?>">CSV</a>
        </div>
    </section>

    <?php if ($detailCode !== ''): ?>
        <section class="detail">
            <h2>Detaljna provjera: <?= kipos_image_escape($detailCode) ?></h2>
            <?php if ($specificRows === []): ?>
                <div class="muted">Specificni Kipos image endpointi nisu vratili slike za ovu sifru.</div>
            <?php else: ?>
                <div class="images">
                    <?php foreach ($specificRows as $image): ?>
                        <?php $url = (string) ($image['URL'] ?? ''); ?>
                        <article class="image-card">
                            <a href="<?= kipos_image_escape($url) ?>" target="_blank" rel="noopener">
                                <img src="<?= kipos_image_escape($url) ?>" alt="<?= kipos_image_escape(kipos_image_string($image, 'NAZIV', '_file_name')) ?>" loading="lazy">
                            </a>
                            <div class="image-body">
                                <div class="image-name"><?= kipos_image_escape(kipos_image_string($image, 'NAZIV', 'OPIS', '_file_name')) ?></div>
                                <div class="muted"><?= kipos_image_escape(kipos_image_string($image, '_item_code')) ?></div>
                                <a class="image-link" href="<?= kipos_image_escape($url) ?>" target="_blank" rel="noopener"><?= kipos_image_escape(kipos_image_file_name($url)) ?></a>
                                <div class="routes"><?= kipos_image_escape(implode(', ', (array) ($image['_source_routes'] ?? []))) ?></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>IDODJEL</th>
                <th>Naziv artikla</th>
                <th>Varijante / IDROBA</th>
                <th class="num">Broj slika</th>
                <th>Slike</th>
                <th>Izvor</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($screenRows as $group): ?>
                <?php $imageCount = count($group['images']); ?>
                <tr>
                    <td><strong><?= kipos_image_escape($group['code']) ?></strong></td>
                    <td><?= kipos_image_escape($group['name']) ?></td>
                    <td class="items"><?= kipos_image_escape(implode(', ', $group['item_codes'])) ?></td>
                    <td class="num">
                        <span class="badge <?= $imageCount > 0 ? 'ok' : 'empty' ?>"><?= kipos_image_escape($imageCount) ?></span>
                    </td>
                    <td>
                        <?php if ($group['images'] === []): ?>
                            <span class="muted">Nema slika.</span>
                        <?php else: ?>
                            <div class="images">
                                <?php foreach ($group['images'] as $image): ?>
                                    <?php $url = (string) ($image['URL'] ?? ''); ?>
                                    <article class="image-card">
                                        <a href="<?= kipos_image_escape($url) ?>" target="_blank" rel="noopener">
                                            <img src="<?= kipos_image_escape($url) ?>" alt="<?= kipos_image_escape(kipos_image_string($image, 'NAZIV', '_file_name')) ?>" loading="lazy">
                                        </a>
                                        <div class="image-body">
                                            <div class="image-name">
                                                <?= kipos_image_escape(kipos_image_string($image, 'NAZIV', 'OPIS', '_file_name')) ?>
                                                <?php if (kipos_image_bool($image, 'GLAVNA')): ?>
                                                    <span class="badge ok">GLAVNA</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="muted"><?= kipos_image_escape(kipos_image_string($image, '_item_code')) ?></div>
                                            <a class="image-link" href="<?= kipos_image_escape($url) ?>" target="_blank" rel="noopener"><?= kipos_image_escape(kipos_image_file_name($url)) ?></a>
                                            <div class="routes"><?= kipos_image_escape(implode(', ', (array) ($image['_source_routes'] ?? []))) ?></div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= kipos_image_escape($group['source']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($screenRows === []): ?>
                <tr>
                    <td colspan="6" class="muted">Nema redova za prikaz.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
