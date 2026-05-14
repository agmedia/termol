<?php

declare(strict_types=1);

use App\Services\Integrations\Kipos\KiposSdkService;
use App\Services\Integrations\Kipos\KiposSyncService;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

@set_time_limit(0);

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/**
 * @param  array<string, mixed>  $row
 */
function kipos_string(array $row, string ...$keys): string
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
function kipos_float(array $row, string $key): float
{
    if (! array_key_exists($key, $row)) {
        return 0.0;
    }

    $value = $row[$key];
    if (is_numeric($value)) {
        return (float) $value;
    }

    $normalized = trim((string) $value);
    $normalized = str_replace([' ', "\xc2\xa0"], '', $normalized);

    if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
        $normalized = str_replace('.', '', $normalized);
    }

    $normalized = str_replace(',', '.', $normalized);

    return is_numeric($normalized) ? (float) $normalized : 0.0;
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_item_code(array $row): string
{
    return strtoupper(kipos_string($row, 'IDROBA'));
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_department_code(array $row): string
{
    $department = strtoupper(kipos_string($row, 'IDODJEL'));
    if ($department !== '') {
        return $department;
    }

    $itemCode = kipos_item_code($row);
    $dotPosition = strrpos($itemCode, '.');

    return $dotPosition === false ? $itemCode : substr($itemCode, 0, $dotPosition);
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_selected_price(array $row, string $priceField): float
{
    $price = kipos_float($row, $priceField);
    if ($price > 0) {
        return round($price, 2);
    }

    foreach (['CIJENA_MPC', 'CIJENA_EUR_MPC', 'CIJENA_EUR'] as $fallbackKey) {
        $fallback = kipos_float($row, $fallbackKey);
        if ($fallback > 0) {
            return round($fallback, 2);
        }
    }

    return 0.0;
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_is_active(array $row): bool
{
    if (array_key_exists('HIDE', $row)) {
        $hidden = filter_var($row['HIDE'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($hidden === true) {
            return false;
        }
    }

    return kipos_string($row, 'DATUM_DEAKTIVIRANJA') === '';
}

function kipos_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kipos_format_price(float $value): string
{
    return number_format($value, 2, ',', '.');
}

/**
 * @param  array<string, mixed>  $row
 */
function kipos_format_price_cell(array $row, string $key): string
{
    if (! array_key_exists($key, $row) || trim((string) $row[$key]) === '') {
        return '';
    }

    $raw = trim((string) $row[$key]);
    $price = kipos_float($row, $key);

    return $price > 0 || is_numeric(str_replace(',', '.', $raw))
        ? kipos_format_price($price)
        : kipos_escape($raw);
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @param  list<string>  $preferred
 * @return list<string>
 */
function kipos_price_keys(array $rows, array $preferred): array
{
    $keys = [];

    foreach ($preferred as $key) {
        $key = strtoupper(trim($key));
        if ($key !== '') {
            $keys[$key] = true;
        }
    }

    foreach ($rows as $row) {
        foreach (array_keys($row) as $key) {
            $upper = strtoupper((string) $key);
            if (str_contains($upper, 'CIJENA') || str_contains($upper, 'PRICE')) {
                $keys[$upper] = true;
            }
        }
    }

    return array_keys($keys);
}

/**
 * @param  list<array<string, mixed>>  ...$rowSets
 * @return list<array<string, mixed>>
 */
function kipos_merge_rows(array ...$rowSets): array
{
    $merged = [];

    foreach ($rowSets as $rows) {
        foreach ($rows as $row) {
            $itemCode = kipos_item_code($row);
            if ($itemCode === '') {
                $itemCode = '_ROW_'.count($merged);
            }

            $merged[$itemCode] = array_merge($merged[$itemCode] ?? [], $row);
        }
    }

    return array_values($merged);
}

/**
 * @param  array<int, string>  $errors
 * @return list<array<string, mixed>>
 */
function kipos_fetch_rows(KiposSdkService $kipos, string $route, array &$errors): array
{
    try {
        return $kipos->getRows($route);
    } catch (Throwable $exception) {
        $errors[] = $route.': '.$exception->getMessage();

        return [];
    }
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<array<string, mixed>>
 */
function kipos_filter_rows(array $rows, string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return $rows;
    }

    return array_values(array_filter($rows, static function (array $row) use ($query): bool {
        $haystack = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $haystack !== false && stripos($haystack, $query) !== false;
    }));
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<array<string, mixed>>
 */
function kipos_sort_rows(array $rows): array
{
    usort($rows, static function (array $left, array $right): int {
        $departmentCompare = strnatcasecmp(kipos_department_code($left), kipos_department_code($right));

        return $departmentCompare !== 0
            ? $departmentCompare
            : strnatcasecmp(kipos_item_code($left), kipos_item_code($right));
    });

    return $rows;
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @param  list<string>  $priceKeys
 */
function kipos_output_csv(array $rows, array $priceKeys, string $priceField): void
{
    $headers = array_merge(
        ['IDROBA', 'IDODJEL', 'NAZIV', 'IDVELICINA', 'ODABRANA_CIJENA'],
        $priceKeys,
        ['ZALIHAK', 'AKTIVAN', 'DATUM_USER']
    );

    if (PHP_SAPI !== 'cli') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="kipos-cijene.csv"');
    }

    $out = fopen('php://output', 'wb');
    fputcsv($out, $headers);

    foreach ($rows as $row) {
        $line = [
            kipos_item_code($row),
            kipos_department_code($row),
            kipos_string($row, 'NAZIV', 'NAZIV_ODJELA'),
            kipos_string($row, 'IDVELICINA'),
            kipos_format_price(kipos_selected_price($row, $priceField)),
        ];

        foreach ($priceKeys as $key) {
            $line[] = array_key_exists($key, $row) ? (string) $row[$key] : '';
        }

        $line[] = kipos_string($row, 'ZALIHAK');
        $line[] = kipos_is_active($row) ? 'DA' : 'NE';
        $line[] = kipos_string($row, 'DATUM_USER');

        fputcsv($out, $line);
    }

    fclose($out);
}

$kipos = app(KiposSdkService::class);
$sync = app(KiposSyncService::class);
$settings = $kipos->getSettings();
$syncSettings = $sync->syncSettings();
$priceField = strtoupper(trim((string) ($syncSettings['kipos_sync_price_field'] ?? 'CIJENA_MPC')));
$actionPriceField = strtoupper(trim((string) ($syncSettings['kipos_sync_action_price_field'] ?? 'AKCIJSKA_CIJENA')));
$errors = [];

$baseRows = kipos_fetch_rows($kipos, 'sif_roba/getitems', $errors);
$extendedRows = kipos_fetch_rows($kipos, 'sif_roba/getitemsextended', $errors);
$allRows = kipos_sort_rows(kipos_merge_rows($baseRows, $extendedRows));
$query = trim((string) ($_GET['q'] ?? ''));
$rows = kipos_filter_rows($allRows, $query);
$priceKeys = kipos_price_keys($allRows, [
    $priceField,
    $actionPriceField,
    'CIJENA_MPC',
    'CIJENA_EUR_MPC',
    'CIJENA_EUR',
    'CIJENA_NAJNIZA_30DANA',
]);

if (PHP_SAPI === 'cli' || (string) ($_GET['format'] ?? '') === 'csv') {
    kipos_output_csv($rows, $priceKeys, $priceField);
    exit;
}

$csvQuery = http_build_query(array_filter([
    'q' => $query,
    'format' => 'csv',
], static fn ($value): bool => $value !== null && $value !== ''));

?>
<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kipos cijene artikala</title>
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
            width: min(1500px, calc(100vw - 32px));
            margin: 28px auto;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 26px;
            font-weight: 700;
        }

        .meta,
        .errors,
        .toolbar {
            margin-top: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            padding: 14px;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
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

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }

        form {
            display: flex;
            flex: 1 1 420px;
            gap: 8px;
        }

        input {
            min-width: 220px;
            flex: 1;
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
            margin-top: 16px;
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
        }

        table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid var(--line);
            padding: 9px 10px;
            text-align: left;
            vertical-align: top;
            white-space: nowrap;
        }

        th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #eef2f6;
            color: #2d384b;
            font-size: 12px;
            text-transform: uppercase;
        }

        tr:nth-child(even) td {
            background: #fafbfc;
        }

        .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .muted {
            color: var(--muted);
        }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            background: #e8f3f8;
            color: #0d5978;
            font-weight: 700;
        }

        .inactive {
            background: #fff1f0;
            color: var(--danger);
        }
    </style>
</head>
<body>
<main>
    <h1>Kipos cijene artikala</h1>
    <div class="muted">Pregled se cita direktno iz Kipos API-ja kroz postojece Laravel postavke.</div>

    <section class="meta" aria-label="Sazetak">
        <div>
            <div class="label">Ukupno artikala</div>
            <div class="value"><?= kipos_escape(count($allRows)) ?></div>
        </div>
        <div>
            <div class="label">Prikazano</div>
            <div class="value"><?= kipos_escape(count($rows)) ?></div>
        </div>
        <div>
            <div class="label">Getitems</div>
            <div class="value"><?= kipos_escape(count($baseRows)) ?> redova</div>
        </div>
        <div>
            <div class="label">Getitemsextended</div>
            <div class="value"><?= kipos_escape(count($extendedRows)) ?> redova</div>
        </div>
        <div>
            <div class="label">Glavno polje cijene</div>
            <div class="value"><?= kipos_escape($priceField) ?></div>
        </div>
        <div>
            <div class="label">Akcijsko polje</div>
            <div class="value"><?= kipos_escape($actionPriceField) ?></div>
        </div>
        <div>
            <div class="label">Kipos suffix</div>
            <div class="value"><?= kipos_escape((string) ($settings['kipos_api_query_suffix'] ?? '')) ?></div>
        </div>
        <div>
            <div class="label">Osvjezeno</div>
            <div class="value"><?= kipos_escape(now()->format('Y-m-d H:i:s')) ?></div>
        </div>
    </section>

    <?php if ($errors !== []): ?>
        <section class="errors">
            <strong>Kipos dohvat nije prosao za sve endpointove:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= kipos_escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="toolbar">
        <form method="get">
            <input type="search" name="q" value="<?= kipos_escape($query) ?>" placeholder="Trazi sifru, naziv, odjel, cijenu...">
            <button type="submit">Trazi</button>
            <?php if ($query !== ''): ?>
                <a class="button secondary" href="<?= kipos_escape(strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: 'kipos-cijene.php') ?>">Reset</a>
            <?php endif; ?>
        </form>
        <a class="button secondary" href="?<?= kipos_escape($csvQuery) ?>">CSV</a>
    </section>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>IDROBA</th>
                <th>IDODJEL</th>
                <th>Naziv</th>
                <th>Velicina</th>
                <th class="num">Odabrana cijena</th>
                <?php foreach ($priceKeys as $key): ?>
                    <th class="num"><?= kipos_escape($key) ?></th>
                <?php endforeach; ?>
                <th class="num">Zaliha</th>
                <th>Aktivan</th>
                <th>DATUM_USER</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $active = kipos_is_active($row); ?>
                <tr>
                    <td><strong><?= kipos_escape(kipos_item_code($row)) ?></strong></td>
                    <td><?= kipos_escape(kipos_department_code($row)) ?></td>
                    <td><?= kipos_escape(kipos_string($row, 'NAZIV', 'NAZIV_ODJELA')) ?></td>
                    <td><?= kipos_escape(kipos_string($row, 'IDVELICINA')) ?></td>
                    <td class="num"><span class="badge"><?= kipos_escape(kipos_format_price(kipos_selected_price($row, $priceField))) ?></span></td>
                    <?php foreach ($priceKeys as $key): ?>
                        <td class="num"><?= kipos_format_price_cell($row, $key) ?></td>
                    <?php endforeach; ?>
                    <td class="num"><?= kipos_escape(kipos_string($row, 'ZALIHAK')) ?></td>
                    <td><span class="badge <?= $active ? '' : 'inactive' ?>"><?= $active ? 'DA' : 'NE' ?></span></td>
                    <td><?= kipos_escape(kipos_string($row, 'DATUM_USER')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= kipos_escape(8 + count($priceKeys)) ?>" class="muted">Nema redova za prikaz.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
