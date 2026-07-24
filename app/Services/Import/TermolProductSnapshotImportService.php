<?php

namespace App\Services\Import;

use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Product\Product;
use App\Models\Settings\Local\TaxRate;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use App\Support\ImportedDescriptionHtmlCleaner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class TermolProductSnapshotImportService
{
    public function __construct(
        private readonly ImportedDescriptionHtmlCleaner $descriptionCleaner,
        private readonly SystemSettingsService $settings,
    ) {}

    /**
     * @return array<string, int|string>
     *
     * @throws JsonException
     */
    public function import(string $snapshotFile, bool $importImages = true): array
    {
        $rows = $this->snapshotRows($snapshotFile);

        $userId = User::query()->value('id');
        $taxRate = TaxRate::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $taxRate) {
            throw new RuntimeException('An active tax rate is required before importing Termol products.');
        }

        $manufacturer = $this->upsertSmegManufacturer($userId);
        $productsWithRows = [];
        $stats = [
            'snapshot_file' => $snapshotFile,
            'source_products' => count($rows),
            'products_imported' => 0,
            'categories_linked' => 0,
            'main_images_attached' => 0,
            'images_skipped' => 0,
            'prices_include_tax' => 1,
            'manufacturer_id' => (int) $manufacturer->id,
            'tax_rate_id' => (int) $taxRate->id,
        ];

        DB::transaction(function () use (
            $rows,
            $manufacturer,
            $taxRate,
            $userId,
            &$productsWithRows,
            &$stats
        ): void {
            foreach (array_values($rows) as $index => $row) {
                if (! is_array($row)) {
                    throw new RuntimeException('Invalid Termol product row at index '.$index.'.');
                }

                $normalized = $this->normalizeRow($row);
                $category = $this->resolveCategory($normalized['category_path']);
                $code = 'termol-'.$normalized['sku'];
                $grossPrice = $this->parseGrossPrice($normalized['price']);

                $product = Product::query()
                    ->where('code', $code)
                    ->orWhere('sku', $normalized['sku'])
                    ->first() ?? new Product;

                if (! $product->exists) {
                    $product->created_by = $userId;
                }

                $product->fill([
                    'code' => $code,
                    'sku' => $normalized['sku'],
                    'is_active' => true,
                    'manufacturer_id' => $manufacturer->id,
                    'tax_rate_id' => $taxRate->id,
                    'base_price' => $grossPrice,
                    'stock_qty' => $this->stockQuantity($normalized['stock']),
                    'payload' => [
                        'source' => 'termol.hr',
                        'source_url' => $normalized['source_url'],
                        'source_category_url' => $this->absoluteUrl($normalized['category_path']),
                        'source_image_url' => $normalized['source_image_url'],
                        'source_price_gross' => $grossPrice,
                        'source_price_includes_tax' => true,
                        'source_availability' => $normalized['stock'],
                    ],
                    'updated_by' => $userId,
                ]);
                $product->save();

                $description = $this->descriptionCleaner->clean($normalized['description_html']);
                $excerpt = $this->excerpt($normalized['name'], $description);
                $slug = $this->sourceSlug($normalized['source_url'], $normalized['name']);

                $product->translations()->updateOrCreate(
                    ['locale' => 'hr'],
                    [
                        'name' => $normalized['name'],
                        'slug' => $slug,
                        'excerpt' => $excerpt,
                        'description' => $description,
                        'meta_title' => $normalized['name'],
                        'meta_description' => $excerpt,
                        'payload' => [
                            'source' => 'termol.hr',
                            'source_url' => $normalized['source_url'],
                        ],
                    ]
                );

                $product->categories()->sync([
                    $category->id => [
                        'sort_order' => ($index + 1) * 10,
                        'is_primary' => true,
                    ],
                ]);

                $productsWithRows[] = [$product, $normalized];
                $stats['products_imported']++;
                $stats['categories_linked']++;
            }
        });

        $this->settings->put('store_pricing_prices_include_tax', true);

        foreach ($productsWithRows as [$product, $row]) {
            $imagePath = trim((string) $row['local_image_path']);
            if (! $importImages || $imagePath === '' || ! is_file($imagePath)) {
                $stats['images_skipped']++;

                continue;
            }

            $extension = strtolower((string) pathinfo($imagePath, PATHINFO_EXTENSION));
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)) {
                $extension = 'jpg';
            }

            $product->clearMediaCollection('product_main');
            $product->addMedia($imagePath)
                ->usingName($row['name'])
                ->usingFileName($row['sku'].'.'.$extension)
                ->preservingOriginal()
                ->withCustomProperties([
                    'alt' => ['hr' => $row['name']],
                    'source' => 'termol.hr',
                    'source_url' => $row['source_image_url'],
                ])
                ->toMediaCollection('product_main');

            $stats['main_images_attached']++;
        }

        return $stats;
    }

    /**
     * @return array<string, int|string>
     *
     * @throws JsonException
     */
    public function importGalleries(string $snapshotFile, bool $clearExisting = true): array
    {
        $rows = $this->snapshotRows($snapshotFile);
        $stats = [
            'snapshot_file' => $snapshotFile,
            'source_products' => count($rows),
            'source_images' => 0,
            'matched_products' => 0,
            'products_with_galleries' => 0,
            'galleries_cleared' => 0,
            'gallery_images_attached' => 0,
            'images_skipped' => 0,
        ];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                throw new RuntimeException('Invalid Termol gallery row at index '.$index.'.');
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            $name = trim((string) ($row['name'] ?? $sku));
            $images = is_array($row['images'] ?? null) ? array_values($row['images']) : [];
            $stats['source_images'] += count($images);

            if ($sku === '') {
                throw new RuntimeException('Termol gallery row is missing a product SKU.');
            }

            $product = Product::query()
                ->where('sku', $sku)
                ->where('code', 'like', 'termol-%')
                ->first();

            if (! $product) {
                continue;
            }

            $stats['matched_products']++;

            if ($clearExisting) {
                $product->clearMediaCollection('product_gallery');
                $stats['galleries_cleared']++;
            }

            if ($images !== []) {
                $stats['products_with_galleries']++;
            }

            foreach ($images as $imageIndex => $image) {
                if (! is_array($image)) {
                    $stats['images_skipped']++;

                    continue;
                }

                $imagePath = trim((string) ($image['local_image_path'] ?? ''));
                if ($imagePath === '' || ! is_file($imagePath)) {
                    $stats['images_skipped']++;

                    continue;
                }

                $dimensions = getimagesize($imagePath);
                if (! is_array($dimensions) || (int) $dimensions[0] !== (int) $dimensions[1]) {
                    throw new RuntimeException('Termol gallery image must be square: '.$imagePath);
                }

                $extension = strtolower((string) pathinfo($imagePath, PATHINFO_EXTENSION));
                if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)) {
                    $extension = 'jpg';
                }

                $position = $imageIndex + 2;
                $alt = $name.' — fotografija '.$position;
                $sourceUrl = trim((string) ($image['source_url'] ?? ''));

                $product->addMedia($imagePath)
                    ->usingName($alt)
                    ->usingFileName($sku.'-gallery-'.$position.'.'.$extension)
                    ->preservingOriginal()
                    ->withCustomProperties([
                        'alt' => ['hr' => $alt],
                        'source' => 'termol.hr',
                        'source_url' => $sourceUrl,
                        'source_dimensions' => [
                            'width' => (int) $dimensions[0],
                            'height' => (int) $dimensions[1],
                        ],
                    ])
                    ->toMediaCollection('product_gallery');

                $stats['gallery_images_attached']++;
            }
        }

        return $stats;
    }

    private function upsertSmegManufacturer(?int $userId): Manufacturer
    {
        $manufacturer = Manufacturer::query()->firstOrNew(['code' => 'smeg']);

        if (! $manufacturer->exists) {
            $manufacturer->created_by = $userId;
        }

        $manufacturer->fill([
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 10,
            'payload' => [
                'source' => 'termol.hr',
            ],
            'updated_by' => $userId,
        ]);
        $manufacturer->save();

        $manufacturer->translations()->updateOrCreate(
            ['locale' => 'hr'],
            [
                'name' => 'SMEG',
                'slug' => 'smeg',
                'description' => null,
                'meta_title' => 'SMEG',
                'meta_description' => null,
                'payload' => [
                    'source' => 'termol.hr',
                ],
            ]
        );

        return $manufacturer;
    }

    /**
     * @return array<int, mixed>
     *
     * @throws JsonException
     */
    private function snapshotRows(string $snapshotFile): array
    {
        if (! is_file($snapshotFile) || ! is_readable($snapshotFile)) {
            throw new RuntimeException('Termol snapshot not found or unreadable: '.$snapshotFile);
        }

        $contents = file_get_contents($snapshotFile);
        if ($contents === false) {
            throw new RuntimeException('Unable to read Termol snapshot: '.$snapshotFile);
        }

        $rows = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows) || $rows === []) {
            throw new RuntimeException('Termol snapshot contains no products.');
        }

        return array_values($rows);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function normalizeRow(array $row): array
    {
        $required = [
            'name',
            'sku',
            'price',
            'stock',
            'source_url',
            'category_path',
            'description_html',
            'source_image_url',
            'local_image_path',
        ];

        $normalized = [];
        foreach ($required as $field) {
            $normalized[$field] = trim((string) ($row[$field] ?? ''));
        }

        foreach (['name', 'sku', 'price', 'source_url', 'category_path'] as $field) {
            if ($normalized[$field] === '') {
                throw new RuntimeException('Termol product row is missing required field: '.$field);
            }
        }

        return $normalized;
    }

    private function resolveCategory(string $sourcePath): Category
    {
        $path = '/'.ltrim((string) (parse_url($sourcePath, PHP_URL_PATH) ?: $sourcePath), '/');
        $code = 'termol-'.substr(hash('sha256', $path), 0, 24);

        $category = Category::query()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('code', $code)
            ->first();

        if (! $category) {
            throw new RuntimeException('Termol category is missing from the local catalog: '.$path);
        }

        return $category;
    }

    private function parseGrossPrice(string $price): float
    {
        $normalized = str_replace(["\u{00A0}", ' ', '€', '.'], '', $price);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            throw new RuntimeException('Invalid Termol price: '.$price);
        }

        return round(max(0, (float) $normalized), 2);
    }

    private function stockQuantity(string $availability): int
    {
        return in_array(Str::lower(trim($availability)), ['na stanju', 'raspoloživo'], true) ? 1 : 0;
    }

    private function excerpt(string $name, string $description): ?string
    {
        $withLineBreaks = preg_replace('/<br\s*\/?>/i', "\n", $description) ?? $description;
        $text = html_entity_decode(strip_tags($withLineBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = preg_split('/\R+/u', $text) ?: [];
        $lines = array_values(array_filter(
            array_map(static fn (string $line): string => trim(preg_replace('/\s+/u', ' ', $line) ?? $line), $lines),
            static fn (string $line): bool => $line !== ''
        ));

        if ($lines !== [] && Str::lower($lines[0]) === Str::lower(trim($name))) {
            array_shift($lines);
        }

        $excerpt = $lines[0] ?? '';

        return $excerpt !== '' ? Str::limit($excerpt, 300, '') : null;
    }

    private function sourceSlug(string $sourceUrl, string $fallbackName): string
    {
        $path = (string) (parse_url($sourceUrl, PHP_URL_PATH) ?: '');
        $basename = preg_replace('/\.aspx$/i', '', basename($path)) ?? '';
        $slug = Str::slug($basename);

        return $slug !== '' ? $slug : Str::slug($fallbackName);
    }

    private function absoluteUrl(string $path): string
    {
        return 'https://www.termol.hr/'.ltrim($path, '/');
    }
}
