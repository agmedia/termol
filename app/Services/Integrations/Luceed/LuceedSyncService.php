<?php

namespace App\Services\Integrations\Luceed;

use App\Models\Catalog\Action\CatalogAction;
use App\Models\Catalog\Action\CatalogActionTranslation;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Category\CategoryTranslation;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Manufacturer\ManufacturerTranslation;
use App\Models\Catalog\Product\Product;
use App\Models\Catalog\Product\ProductTranslation;
use App\Models\Integrations\LuceedSyncRun;
use App\Models\Sales\Order\Order;
use App\Models\Sales\Order\OrderHistory;
use App\Models\Settings\Local\OrderStatus;
use App\Models\Settings\Local\PaymentMethod;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LuceedSyncService
{
    public function __construct(
        private readonly LuceedSdkService $luceed,
        private readonly SystemSettingsService $settings
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function endpointMap(): array
    {
        return [
            'group_list' => 'grupeartikala/lista',
            'manufacturer_list' => 'robnemarke/lista',
            'warehouse_list' => 'skladista/lista',
            'product_list' => 'artikli/naziv//',
            'product_short_list' => 'artikli/lista/',
            'product' => 'artikli/sifra/',
            'product_actions' => 'akcije/lista',
            'product_image' => 'artikli/dokumenti/',
            'manufacturer_uid' => 'partneri/uid/',
            'customer_email' => 'partneri/email/',
            'customer_create' => 'partneri/snimi/',
            'order_create' => 'NaloziProdaje/snimi/',
            'orders_get' => 'NaloziProdaje/statusi/',
            'stock_get' => 'StanjeZalihe/Skladiste/',
            'ind_stock_get' => 'StanjeZalihe/ArtiklUID/',
            'raspis' => 'NaloziProdaje/raspis/poslovnica/',
            'mjesta' => 'mjesta/naziv/',
            'vrste_placanja' => 'vrsteplacanja/list',
            'stock_skladista' => 'StanjeZalihe/Skladiste/',
            'stock_dobavljaca' => 'StanjeZaliheDobavljaci/Lista',
            'stock_dobavljac' => 'StanjeZaliheDobavljaci/Artikl/',
        ];
    }

    /**
     * @return array<string, array{title:string,description:string,actions:array<int,array{key:string,label:string,description:string}>}>
     */
    public function actionGroups(): array
    {
        return [
            'catalog' => [
                'title' => 'Catalog Base Sync',
                'description' => 'Default import/update actions for categories, manufacturers, warehouses and payments.',
                'actions' => [
                    ['key' => 'import_categories', 'label' => 'Add Categories', 'description' => 'Create missing categories from `grupeartikala/lista`.'],
                    ['key' => 'update_categories', 'label' => 'Update Categories', 'description' => 'Update existing category rows from `grupeartikala/lista`.'],
                    ['key' => 'import_manufacturers', 'label' => 'Add Manufacturers', 'description' => 'Create missing manufacturers from `robnemarke/lista`.'],
                    ['key' => 'update_manufacturers', 'label' => 'Update Manufacturers', 'description' => 'Update existing manufacturers from `robnemarke/lista`.'],
                    ['key' => 'import_warehouses', 'label' => 'Sync Warehouses', 'description' => 'Store warehouse snapshot from `skladista/lista`.'],
                    ['key' => 'import_payments', 'label' => 'Sync Payments', 'description' => 'Sync payment methods from `vrsteplacanja/list`.'],
                ],
            ],
            'products' => [
                'title' => 'Product Sync',
                'description' => 'Default product actions: add/update plus actions/related/prices/quantities.',
                'actions' => [
                    ['key' => 'import_products', 'label' => 'Add Products', 'description' => 'Create missing products from `artikli/lista/`.'],
                    ['key' => 'update_products', 'label' => 'Update Products', 'description' => 'Update existing products from `artikli/lista/`.'],
                    ['key' => 'import_actions', 'label' => 'Sync Product Actions', 'description' => 'Sync catalog actions from `akcije/lista`.'],
                    ['key' => 'import_related_products', 'label' => 'Sync Related Products', 'description' => 'Update related-product payload from product list response.'],
                    ['key' => 'update_prices', 'label' => 'Update Prices', 'description' => 'Update base prices from `artikli/lista/`.'],
                    ['key' => 'update_quantities', 'label' => 'Update Quantities', 'description' => 'Update stock from `StanjeZalihe/Skladiste/`.'],
                ],
            ],
            'orders' => [
                'title' => 'Order Sync',
                'description' => 'Order status synchronization from Luceed.',
                'actions' => [
                    ['key' => 'update_order_statuses', 'label' => 'Update Order Statuses', 'description' => 'Sync order statuses from `NaloziProdaje/statusi/`.'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function syncDefaults(): array
    {
        return [
            'luceed_sync_default_locale' => (string) config('app.locale', 'en'),
            'luceed_sync_article_limit' => 0,
            'luceed_sync_stock_warehouses' => '',
            'luceed_sync_orders_lookback_days' => 30,
            'luceed_sync_status_codes' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function syncSettings(): array
    {
        $defaults = $this->syncDefaults();
        $settings = [];

        foreach ($defaults as $key => $default) {
            $settings[$key] = $this->settings->get($key, $default);
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveSyncSettings(array $payload): void
    {
        $this->settings->putMany($payload);
    }

    public function run(string $actionKey, ?int $initiatedBy = null): LuceedSyncRun
    {
        $catalog = $this->flatActionCatalog();
        abort_unless(isset($catalog[$actionKey]), 404, 'Unknown Luceed sync action.');

        $run = LuceedSyncRun::query()->create([
            'action_key' => $actionKey,
            'action_label' => $catalog[$actionKey]['label'],
            'status' => 'started',
            'started_at' => now(),
            'initiated_by' => $initiatedBy,
        ]);

        try {
            $this->luceed->assertEnabled();

            $handler = $this->handlerMap()[$actionKey] ?? null;
            if (! $handler) {
                throw new \RuntimeException('No handler configured for action: '.$actionKey);
            }

            /** @var array<string, mixed> $result */
            $result = $this->{$handler}();

            $run->fill([
                'status' => 'success',
                'summary' => (string) ($result['summary'] ?? 'Completed.'),
                'stats' => $result,
                'error_message' => null,
                'finished_at' => now(),
            ])->save();
        } catch (\Throwable $exception) {
            $run->fill([
                'status' => 'failed',
                'summary' => 'Execution failed.',
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ])->save();

            throw $exception;
        }

        return $run->fresh(['initiator']) ?? $run;
    }

    /**
     * @return array<string, string>
     */
    private function handlerMap(): array
    {
        return [
            'import_categories' => 'handleImportCategories',
            'update_categories' => 'handleUpdateCategories',
            'import_manufacturers' => 'handleImportManufacturers',
            'update_manufacturers' => 'handleUpdateManufacturers',
            'import_warehouses' => 'handleImportWarehouses',
            'import_payments' => 'handleImportPayments',
            'import_products' => 'handleImportProducts',
            'update_products' => 'handleUpdateProducts',
            'import_actions' => 'handleImportActions',
            'import_related_products' => 'handleImportRelatedProducts',
            'update_prices' => 'handleUpdatePrices',
            'update_quantities' => 'handleUpdateQuantities',
            'update_order_statuses' => 'handleUpdateOrderStatuses',
        ];
    }

    /**
     * @return array<string, array{label:string,description:string}>
     */
    private function flatActionCatalog(): array
    {
        $flat = [];

        foreach ($this->actionGroups() as $group) {
            foreach ($group['actions'] as $action) {
                $flat[$action['key']] = [
                    'label' => $action['label'],
                    'description' => $action['description'],
                ];
            }
        }

        return $flat;
    }

    /**
     * @return array<string, mixed>
     */
    private function handleImportCategories(): array
    {
        return $this->syncCategories(updateExisting: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function handleUpdateCategories(): array
    {
        return $this->syncCategories(updateExisting: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function syncCategories(bool $updateExisting): array
    {
        $rows = $this->fetchRows('group_list', ['grupe_artikala', 'grupe']);
        $locale = (string) ($this->syncSettings()['luceed_sync_default_locale'] ?? config('app.locale', 'en'));

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $code = $this->firstString($row, ['grupa_artikala', 'grupa', 'sifra', 'oznaka', 'id']);
            if (! $code) {
                $skipped++;
                continue;
            }

            $name = $this->firstString($row, ['naziv', 'name']) ?: $code;
            $uid = $this->firstString($row, ['grupa_uid', 'uid']);

            $category = Category::query()
                ->where('scope', Category::SCOPE_CATALOG)
                ->where('code', $code)
                ->first();

            if (! $category) {
                if ($updateExisting) {
                    $skipped++;
                    continue;
                }

                $category = new Category([
                    'scope' => Category::SCOPE_CATALOG,
                    'code' => $code,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            } else {
                if (! $updateExisting) {
                    $skipped++;
                    continue;
                }
                $updated++;
            }

            $payload = (array) ($category->payload ?? []);
            $payload['luceed'] = [
                'uid' => $uid,
                'code' => $code,
                'row' => $row,
            ];

            $category->fill([
                'is_active' => $this->firstBool($row, ['enabled', 'active', 'aktivan', 'status']) ?? true,
                'show_in_menu' => true,
                'sort_order' => max(0, $this->firstInt($row, ['sort', 'sort_order', 'redni_broj']) ?? 0),
                'payload' => $payload,
                'updated_by' => auth()->id(),
            ]);
            $category->save();

            CategoryTranslation::query()->updateOrCreate(
                [
                    'category_id' => $category->id,
                    'scope' => Category::SCOPE_CATALOG,
                    'locale' => $locale,
                ],
                [
                    'name' => $name,
                    'slug' => Str::slug($name.'-'.$code),
                    'description' => $this->firstString($row, ['opis', 'description']),
                    'payload' => ['luceed' => ['code' => $code]],
                ]
            );
        }

        return [
            'summary' => sprintf('Categories: %d created, %d updated, %d skipped.', $created, $updated, $skipped),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'source_rows' => count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleImportManufacturers(): array
    {
        return $this->syncManufacturers(updateExisting: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function handleUpdateManufacturers(): array
    {
        return $this->syncManufacturers(updateExisting: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function syncManufacturers(bool $updateExisting): array
    {
        $rows = $this->fetchRows('manufacturer_list', ['robne_marke']);
        $locale = (string) ($this->syncSettings()['luceed_sync_default_locale'] ?? config('app.locale', 'en'));

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $code = $this->firstString($row, ['robna_marka', 'marka', 'sifra', 'oznaka', 'id']);
            if (! $code) {
                $skipped++;
                continue;
            }

            $name = $this->firstString($row, ['naziv', 'name']) ?: $code;
            $uid = $this->firstString($row, ['robna_marka_uid', 'uid']);

            $manufacturer = Manufacturer::query()->where('code', $code)->first();

            if (! $manufacturer) {
                if ($updateExisting) {
                    $skipped++;
                    continue;
                }

                $manufacturer = new Manufacturer([
                    'code' => $code,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            } else {
                if (! $updateExisting) {
                    $skipped++;
                    continue;
                }
                $updated++;
            }

            $payload = (array) ($manufacturer->payload ?? []);
            $payload['luceed'] = [
                'uid' => $uid,
                'code' => $code,
                'row' => $row,
            ];

            $manufacturer->fill([
                'is_active' => $this->firstBool($row, ['enabled', 'active', 'aktivan', 'status']) ?? true,
                'sort_order' => max(0, $this->firstInt($row, ['sort', 'sort_order', 'redni_broj']) ?? 0),
                'payload' => $payload,
                'updated_by' => auth()->id(),
            ]);
            $manufacturer->save();

            ManufacturerTranslation::query()->updateOrCreate(
                [
                    'manufacturer_id' => $manufacturer->id,
                    'locale' => $locale,
                ],
                [
                    'name' => $name,
                    'slug' => Str::slug($name.'-'.$code),
                    'description' => $this->firstString($row, ['opis', 'description']),
                    'payload' => ['luceed' => ['code' => $code]],
                ]
            );
        }

        return [
            'summary' => sprintf('Manufacturers: %d created, %d updated, %d skipped.', $created, $updated, $skipped),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'source_rows' => count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleImportWarehouses(): array
    {
        $rows = $this->fetchRows('warehouse_list', ['skladista']);

        $codes = collect($rows)
            ->map(fn ($row) => $this->firstString($row, ['skladiste', 'sifra', 'oznaka', 'id']))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (trim((string) $this->settings->get('luceed_sync_stock_warehouses', '')) === '' && $codes !== []) {
            $this->settings->put('luceed_sync_stock_warehouses', implode(',', $codes));
        }

        $this->settings->put('luceed_sync_warehouses_snapshot', [
            'captured_at' => now()->toDateTimeString(),
            'count' => count($rows),
            'rows' => $rows,
        ]);

        return [
            'summary' => sprintf('Warehouses synced: %d rows.', count($rows)),
            'rows' => count($rows),
            'codes' => $codes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleImportPayments(): array
    {
        $rows = $this->fetchRows('vrste_placanja', ['vrsta_placanja']);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $sourceCode = $this->firstString($row, ['vrsta_placanja', 'placanje', 'sifra', 'oznaka', 'id']);
            $name = $this->firstString($row, ['naziv', 'name']);

            if (! $sourceCode || ! $name) {
                $skipped++;
                continue;
            }

            $code = $this->toLocalCode('luceed-pay-', $sourceCode, 60);
            $method = PaymentMethod::query()->where('code', $code)->first();

            if (! $method) {
                $method = new PaymentMethod(['code' => $code]);
                $created++;
            } else {
                $updated++;
            }

            $settings = (array) ($method->settings ?? []);
            $settings['luceed'] = [
                'source_code' => $sourceCode,
                'uid' => $this->firstString($row, ['vrsta_placanja_uid', 'uid']),
                'row' => $row,
            ];

            $method->fill([
                'name' => $name,
                'provider' => 'luceed',
                'description' => $this->firstString($row, ['opis', 'description']),
                'fee_type' => 'fixed',
                'fee_value' => 0,
                'is_active' => $this->firstBool($row, ['enabled', 'active', 'aktivan', 'status']) ?? true,
                'sort_order' => max(0, $this->firstInt($row, ['sort', 'sort_order', 'redni_broj']) ?? 0),
                'settings' => $settings,
            ]);
            $method->save();
        }

        return [
            'summary' => sprintf('Payments: %d created, %d updated, %d skipped.', $created, $updated, $skipped),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'source_rows' => count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleImportProducts(): array
    {
        return $this->syncProducts(updateExisting: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function handleUpdateProducts(): array
    {
        return $this->syncProducts(updateExisting: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function syncProducts(bool $updateExisting): array
    {
        $settings = $this->syncSettings();
        $locale = (string) ($settings['luceed_sync_default_locale'] ?? config('app.locale', 'en'));
        $limit = max(0, (int) ($settings['luceed_sync_article_limit'] ?? 0));

        $rows = $this->productRows();
        if ($limit > 0 && count($rows) > $limit) {
            $rows = array_slice($rows, 0, $limit);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $code = $this->firstString($row, ['artikl', 'sifra', 'code', 'id']);
            if (! $code) {
                $skipped++;
                continue;
            }

            $product = Product::query()->where('code', $code)->first();

            if (! $product) {
                if ($updateExisting) {
                    $skipped++;
                    continue;
                }

                $product = new Product([
                    'code' => $code,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            } else {
                if (! $updateExisting) {
                    $skipped++;
                    continue;
                }
                $updated++;
            }

            $name = $this->firstString($row, ['naziv', 'name']) ?: $code;
            $uid = $this->firstString($row, ['artikl_uid', 'uid']);

            $manufacturerCode = $this->firstString($row, ['robna_marka', 'marka', 'brand']);
            $manufacturerId = null;
            if ($manufacturerCode) {
                $manufacturerId = Manufacturer::query()->where('code', $manufacturerCode)->value('id');
            }

            $payload = (array) ($product->payload ?? []);
            $payload['luceed'] = [
                'uid' => $uid,
                'code' => $code,
                'row' => $row,
            ];

            $product->fill([
                'sku' => $this->firstString($row, ['sku', 'barcode', 'ean']),
                'is_active' => $this->firstBool($row, ['enabled', 'active', 'webshop']) ?? true,
                'manufacturer_id' => $manufacturerId,
                'base_price' => $this->firstFloat($row, ['web_cijena', 'mpc', 'cijena', 'price']) ?? (float) ($product->base_price ?? 0),
                'stock_qty' => $this->firstInt($row, ['kolicina', 'stanje', 'zaliha']) ?? (int) ($product->stock_qty ?? 0),
                'payload' => $payload,
                'updated_by' => auth()->id(),
            ]);
            $product->save();

            ProductTranslation::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'locale' => $locale,
                ],
                [
                    'name' => $name,
                    'slug' => Str::slug($name.'-'.$code),
                    'excerpt' => $this->firstString($row, ['kratki_opis', 'short_description']),
                    'description' => $this->firstString($row, ['opis', 'description', 'web_opis']),
                    'payload' => ['luceed' => ['code' => $code]],
                ]
            );
        }

        return [
            'summary' => sprintf('Products: %d created, %d updated, %d skipped.', $created, $updated, $skipped),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'source_rows' => count($rows),
            'limit' => $limit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleImportActions(): array
    {
        $rows = $this->fetchRows('product_actions', ['akcije']);
        $locale = (string) ($this->syncSettings()['luceed_sync_default_locale'] ?? config('app.locale', 'en'));

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $sourceCode = $this->firstString($row, ['akcija', 'sifra', 'oznaka', 'id']);
            $name = $this->firstString($row, ['naziv', 'name']);

            if (! $sourceCode || ! $name) {
                $skipped++;
                continue;
            }

            $code = $this->toLocalCode('luceed-act-', $sourceCode, 120);

            $action = CatalogAction::query()->where('code', $code)->first();
            if (! $action) {
                $action = new CatalogAction([
                    'code' => $code,
                    'scope' => CatalogAction::SCOPE_PRODUCT,
                    'target_type' => CatalogAction::TARGET_ALL,
                    'audience_type' => CatalogAction::AUDIENCE_ALL,
                    'type' => CatalogAction::TYPE_PERCENTAGE,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            } else {
                $updated++;
            }

            $discount = $this->firstFloat($row, ['popust', 'rabat', 'postotak', 'discount', 'iznos']) ?? 0;
            $type = $discount <= 100 ? CatalogAction::TYPE_PERCENTAGE : CatalogAction::TYPE_FIXED;

            $action->fill([
                'type' => $type,
                'discount_value' => $discount,
                'is_active' => $this->firstBool($row, ['enabled', 'active', 'aktivan', 'status']) ?? true,
                'priority' => max(0, $this->firstInt($row, ['prioritet', 'priority', 'sort_order']) ?? 0),
                'payload' => [
                    'luceed' => [
                        'source_code' => $sourceCode,
                        'uid' => $this->firstString($row, ['akcija_uid', 'uid']),
                        'row' => $row,
                    ],
                ],
                'updated_by' => auth()->id(),
            ]);
            $action->save();

            CatalogActionTranslation::query()->updateOrCreate(
                [
                    'action_id' => $action->id,
                    'locale' => $locale,
                ],
                [
                    'title' => $name,
                    'description' => $this->firstString($row, ['opis', 'description']),
                    'badge' => $this->firstString($row, ['oznaka', 'label']) ?: 'LUCEED',
                    'payload' => ['luceed' => ['source_code' => $sourceCode]],
                ]
            );
        }

        return [
            'summary' => sprintf('Actions: %d created, %d updated, %d skipped.', $created, $updated, $skipped),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'source_rows' => count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleImportRelatedProducts(): array
    {
        $rows = $this->productRows();

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $code = $this->firstString($row, ['artikl', 'sifra', 'code']);
            if (! $code) {
                $skipped++;
                continue;
            }

            $product = Product::query()->where('code', $code)->first();
            if (! $product) {
                $skipped++;
                continue;
            }

            $relatedCodes = $this->extractRelatedCodes($row);

            $payload = (array) ($product->payload ?? []);
            $payload['luceed_related'] = [
                'codes' => $relatedCodes,
                'row' => $row,
            ];

            $product->payload = $payload;
            $product->updated_by = auth()->id();
            $product->save();
            $updated++;
        }

        return [
            'summary' => sprintf('Related products updated on %d products.', $updated),
            'updated' => $updated,
            'skipped' => $skipped,
            'source_rows' => count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleUpdatePrices(): array
    {
        $rows = $this->productRows();

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $code = $this->firstString($row, ['artikl', 'sifra', 'code']);
            $price = $this->firstFloat($row, ['web_cijena', 'mpc', 'cijena', 'price']);

            if (! $code || $price === null) {
                $skipped++;
                continue;
            }

            $product = Product::query()->where('code', $code)->first();
            if (! $product) {
                $skipped++;
                continue;
            }

            $payload = (array) ($product->payload ?? []);
            $payload['luceed_price_row'] = $row;

            $product->fill([
                'base_price' => $price,
                'payload' => $payload,
                'updated_by' => auth()->id(),
            ]);
            $product->save();
            $updated++;
        }

        return [
            'summary' => sprintf('Prices updated on %d products.', $updated),
            'updated' => $updated,
            'skipped' => $skipped,
            'source_rows' => count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleUpdateQuantities(): array
    {
        $settings = $this->syncSettings();
        $warehouses = $this->csvToArray((string) ($settings['luceed_sync_stock_warehouses'] ?? ''));

        if ($warehouses === []) {
            $warehouseRows = $this->fetchRows('warehouse_list', ['skladista']);
            $warehouses = collect($warehouseRows)
                ->map(fn ($row) => $this->firstString($row, ['skladiste', 'sifra', 'oznaka']))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if ($warehouses === []) {
            throw new \RuntimeException('No warehouse codes available for quantity sync.');
        }

        $rows = [];
        foreach ($warehouses as $warehouseCode) {
            $path = $this->endpoint('stock_get').$warehouseCode;
            $rows = array_merge($rows, $this->fetchRowsByPath($path, ['stanjezalihe', 'StanjeZalihe']));
        }

        $qtyByCode = [];
        foreach ($rows as $row) {
            $code = $this->firstString($row, ['artikl', 'sifra', 'code']);
            $qty = $this->firstFloat($row, ['stanje', 'kolicina', 'qty']);

            if (! $code || $qty === null) {
                continue;
            }

            $qtyByCode[$code] = ($qtyByCode[$code] ?? 0) + $qty;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($qtyByCode as $code => $qty) {
            $product = Product::query()->where('code', $code)->first();
            if (! $product) {
                $skipped++;
                continue;
            }

            $payload = (array) ($product->payload ?? []);
            $payload['luceed_stock'] = [
                'warehouses' => $warehouses,
                'quantity' => $qty,
                'synced_at' => now()->toDateTimeString(),
            ];

            $product->fill([
                'stock_qty' => (int) round($qty),
                'payload' => $payload,
                'updated_by' => auth()->id(),
            ]);
            $product->save();
            $updated++;
        }

        return [
            'summary' => sprintf('Quantities updated on %d products.', $updated),
            'updated' => $updated,
            'skipped' => $skipped,
            'source_rows' => count($rows),
            'warehouse_codes' => $warehouses,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleUpdateOrderStatuses(): array
    {
        $settings = $this->syncSettings();
        $lookback = max(1, (int) ($settings['luceed_sync_orders_lookback_days'] ?? 30));
        $statusCodes = $this->csvToArray((string) ($settings['luceed_sync_status_codes'] ?? ''));

        if ($statusCodes === []) {
            // Safe defaults if user has not configured explicit status list.
            $statusCodes = ['10', '20', '30', '40', '50', '60'];
        }

        $from = now()->subDays($lookback)->format('d.m.Y');
        $path = $this->endpoint('orders_get').'['.implode(',', $statusCodes).']/'.$from;

        $rows = $this->fetchRowsByPath($path, ['nalozi_prodaje']);

        $localStatuses = [];
        foreach ($rows as $row) {
            $remoteCode = $this->firstString($row, ['status', 'status_sifra', 'oznaka_statusa']);
            if (! $remoteCode) {
                continue;
            }

            if (isset($localStatuses[$remoteCode])) {
                continue;
            }

            $localCode = $this->toLocalCode('luceed-status-', $remoteCode, 60);
            $name = $this->firstString($row, ['status_naziv', 'naziv_statusa', 'status_name']) ?: ('Status '.$remoteCode);

            $status = OrderStatus::query()->firstOrNew(['code' => $localCode]);
            $status->fill([
                'name' => $name,
                'description' => 'Luceed status '.$remoteCode,
                'color' => 'slate',
                'is_default' => (bool) ($status->exists ? $status->is_default : false),
                'is_paid' => false,
                'is_cancelled' => false,
                'is_active' => true,
                'sort_order' => 0,
                'settings' => ['luceed' => ['source_code' => $remoteCode]],
            ]);
            $status->save();

            $localStatuses[$remoteCode] = $status;
        }

        $updated = 0;
        $unchanged = 0;
        $unmatched = 0;

        foreach ($rows as $row) {
            $remoteCode = $this->firstString($row, ['status', 'status_sifra', 'oznaka_statusa']);
            if (! $remoteCode || ! isset($localStatuses[$remoteCode])) {
                $unmatched++;
                continue;
            }

            $orderUid = $this->firstString($row, ['nalog_uid', 'uid', 'naloz_uid']);
            $orderNumber = $this->firstString($row, ['broj', 'broj_dokumenta', 'naloz']);

            $query = Order::query();

            if ($orderUid) {
                $query->where('payload->luceed_uid', $orderUid);
            } elseif ($orderNumber) {
                $query->where('order_number', $orderNumber);
            } else {
                $unmatched++;
                continue;
            }

            $order = $query->first();
            if (! $order) {
                $unmatched++;
                continue;
            }

            $targetStatusId = (int) $localStatuses[$remoteCode]->id;
            $oldStatusId = (int) ($order->status_id ?? 0);

            $payload = (array) ($order->payload ?? []);
            $payload['luceed_order_status'] = [
                'remote_code' => $remoteCode,
                'row' => $row,
                'synced_at' => now()->toDateTimeString(),
            ];
            $order->payload = $payload;

            if ($oldStatusId === $targetStatusId) {
                $order->save();
                $unchanged++;
                continue;
            }

            $order->status_id = $targetStatusId;
            $order->updated_by = auth()->id();
            $order->save();

            OrderHistory::query()->create([
                'order_id' => $order->id,
                'from_status_id' => $oldStatusId ?: null,
                'to_status_id' => $targetStatusId,
                'changed_by' => auth()->id(),
                'comment' => 'Luceed sync status update.',
                'payload' => ['luceed' => ['remote_code' => $remoteCode]],
            ]);

            $updated++;
        }

        return [
            'summary' => sprintf('Order statuses: %d updated, %d unchanged, %d unmatched.', $updated, $unchanged, $unmatched),
            'updated' => $updated,
            'unchanged' => $unchanged,
            'unmatched' => $unmatched,
            'source_rows' => count($rows),
            'status_codes' => $statusCodes,
            'lookback_days' => $lookback,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productRows(): array
    {
        return $this->fetchRows('product_short_list', ['artikli', 'katalog_artikala']);
    }

    /**
     * @return array<int, string>
     */
    private function extractRelatedCodes(array $row): array
    {
        $candidates = [
            $row['povezani'] ?? null,
            $row['related'] ?? null,
            $row['related_products'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return collect(explode(',', $candidate))
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            if (is_array($candidate)) {
                return collect($candidate)
                    ->map(function ($item): ?string {
                        if (is_string($item)) {
                            $trimmed = trim($item);

                            return $trimmed === '' ? null : $trimmed;
                        }

                        if (is_array($item)) {
                            return $this->firstString($item, ['artikl', 'sifra', 'code']);
                        }

                        return null;
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(string $endpointKey, array $namedLists = []): array
    {
        return $this->fetchRowsByPath($this->endpoint($endpointKey), $namedLists);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRowsByPath(string $path, array $namedLists = []): array
    {
        $client = $this->luceed->makeClient()->raw();

        if ($namedLists !== []) {
            foreach ($namedLists as $name) {
                try {
                    $rows = $client->getRows($path, [$name]);
                    if ($rows !== []) {
                        return $rows;
                    }
                } catch (\Throwable) {
                    // Try next list key.
                }
            }
        }

        try {
            $rows = $client->getRows($path);
            if ($rows !== []) {
                return $rows;
            }
        } catch (\Throwable) {
            // Fallback to raw payload parsing.
        }

        $payload = $client->get($path);

        return $this->extractRowsFromPayload($payload)->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, array<string, mixed>>
     */
    private function extractRowsFromPayload(array $payload): Collection
    {
        if ($payload === []) {
            return collect();
        }

        if ($this->isAssoc($payload)) {
            foreach ($payload as $value) {
                if (is_array($value) && $value !== []) {
                    if (isset($value[0]) && is_array($value[0])) {
                        return collect($value)->filter(fn ($row) => is_array($row))->values();
                    }
                }
            }

            return collect();
        }

        if (isset($payload[0]) && is_array($payload[0])) {
            return collect($payload)->filter(fn ($row) => is_array($row))->values();
        }

        return collect();
    }

    private function endpoint(string $key): string
    {
        $endpoints = $this->endpointMap();
        if (! isset($endpoints[$key])) {
            throw new \InvalidArgumentException('Unknown Luceed endpoint key: '.$key);
        }

        return (string) $endpoints[$key];
    }

    /**
     * @return array<int, string>
     */
    private function csvToArray(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function toLocalCode(string $prefix, string $source, int $max): string
    {
        $base = Str::slug($source, '-');
        if ($base === '') {
            $base = substr(sha1($source), 0, 12);
        }

        $code = $prefix.$base;

        if (strlen($code) <= $max) {
            return $code;
        }

        $hash = substr(sha1($code), 0, 8);
        $cut = max(1, $max - strlen($prefix) - 9);

        return $prefix.substr($base, 0, $cut).'-'.$hash;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstString(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;
            if ($value === null) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstInt(array $row, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstFloat(array $row, array $keys): ?float
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (is_string($value) && preg_match('/^-?\d+[\.,]\d+$/', trim($value)) === 1) {
                return (float) str_replace(',', '.', trim($value));
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstBool(array $row, array $keys): ?bool
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                return $value;
            }

            $normalized = strtoupper(trim((string) $value));
            if (in_array($normalized, ['1', 'Y', 'YES', 'TRUE', 'T', 'D'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'N', 'NO', 'FALSE', 'F'], true)) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $array
     */
    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
