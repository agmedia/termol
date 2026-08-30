<?php

namespace App\Services\Integrations\Msan;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class EprelClient
{
    public const BASE_URL = 'https://eprel.ec.europa.eu';

    /**
     * EPREL uses stable URL slugs in its public product API while responses use
     * enum-style product-group codes. Keeping the map local also prevents an
     * administrator value from becoming an arbitrary request path.
     *
     * @var array<string, string>
     */
    private const PRODUCT_GROUPS = [
        'airconditioners' => 'AIR_CONDITIONER',
        'lamps' => 'LAMP',
        'televisions' => 'TELEVISION',
        'dishwashers' => 'HOUSEHOLD_DISHWASHER',
        'washingmachines' => 'HOUSEHOLD_WASHING_MACHINE',
        'localspaceheaters' => 'LOCAL_SPACE_HEATER',
        'ovens' => 'DOMESTIC_OVEN',
        'rangehoods' => 'RANGE_HOOD',
        'residentialventilationunits' => 'RESIDENTIAL_VENTILATION_UNIT',
        'solidfuelboilerpackages' => 'SOLID_FUEL_BOILER_PACKAGE',
        'solidfuelboilers' => 'SOLID_FUEL_BOILER',
        'professionalrefrigeratedstoragecabinets' => 'PROFESSIONAL_REFRIGERATOR',
        'waterheaters' => 'WATER_HEATERS',
        'washerdriers' => 'HOUSEHOLD_COMBINED_WASHER_DRIER',
        'refrigeratingappliances' => 'HOUSEHOLD_REFRIGERATING_APPLIANCE',
        'spaceheaters' => 'SPACE_HEATER',
        'spaceheatersolardevice' => 'SPACE_HEATER_SOLAR_DEVICE',
        'tumbledriers' => 'HOUSEHOLD_TUMBLE_DRIER',
        'spaceheatertemperaturecontrol' => 'SPACE_HEATER_TEMPERATURE_CONTROL',
        'spaceheaterpackages' => 'SPACE_HEATER_PACKAGE',
        'waterheaterpackages' => 'WATER_HEATER_PACKAGE',
        'hotwaterstoragetanks' => 'HOT_WATER_STORAGE_TANK',
        'waterheatersolardevices' => 'WATER_HEATER_SOLAR_DEVICE',
        'refrigeratingappliancesdirectsalesfunction' => 'DIRECT_SALES_REFRIGERATION_APPLIANCE',
        'washerdriers2019' => 'HOUSEHOLD_WASHER_DRYER_2019',
        'refrigeratingappliances2019' => 'HOUSEHOLD_REFRIGERATING_APPLIANCE_2019',
        'washingmachines2019' => 'HOUSEHOLD_WASHING_MACHINE_2019',
        'electronicdisplays' => 'ELECTRONIC_DISPLAY',
        'dishwashers2019' => 'HOUSEHOLD_DISHWASHER_2019',
        'lightsources' => 'LIGHT_SOURCE',
        'tyres' => 'TYRES',
        'smartphonestablets20231669' => 'SMARTPHONE_TABLET',
        'tumbledryers20232534' => 'HOUSEHOLD_TUMBLE_DRIER_EU_2023_2534',
    ];

    public function __construct(
        private readonly MsanSettingsService $settings,
    ) {}

    /** @return array<string, string> URL slug => EPREL API product-group code. */
    public static function productGroupOptions(): array
    {
        return self::PRODUCT_GROUPS;
    }

    /**
     * @return array{
     *   eprel_registration_number:string,
     *   eprel_product_group:string,
     *   model_identifier:?string,
     *   energy_class:?string,
     *   scale_min:?string,
     *   scale_max:?string,
     *   energy_label_image:?string,
     *   energy_label_url:string,
     *   product_information_sheet_url:?string
     * }|null
     */
    public function findByRegistrationNumber(string $productGroup, string $registrationNumber): ?array
    {
        $group = $this->validatedProductGroup($productGroup);
        $registration = $this->validatedRegistrationNumber($registrationNumber);
        $response = $this->get('/api/products/'.$group.'/'.$registration);

        if ($response === null) {
            return null;
        }

        $record = $this->singleRecord($response);
        if (! $record || ! $this->registrationMatches($record, $registration)
            || ! $this->groupMatches($record, $group)) {
            return null;
        }

        return $this->normalizeRecord($record, $group, $registration);
    }

    /**
     * Search results are never trusted as an approximate match. An exact model
     * identifier must resolve to exactly one EPREL registration before its full
     * detail record is fetched.
     *
     * @return array{
     *   eprel_registration_number:string,
     *   eprel_product_group:string,
     *   model_identifier:?string,
     *   energy_class:?string,
     *   scale_min:?string,
     *   scale_max:?string,
     *   energy_label_image:?string,
     *   energy_label_url:string,
     *   product_information_sheet_url:?string
     * }|null
     */
    public function findByModelIdentifier(string $productGroup, string $modelIdentifier): ?array
    {
        $group = $this->validatedProductGroup($productGroup);
        $model = $this->validatedModelIdentifier($modelIdentifier);
        $response = $this->get('/api/products/'.$group, [
            '_page' => 0,
            '_limit' => 100,
            'modelIdentifier' => $model,
        ]);

        if ($response === null) {
            return null;
        }

        $matches = collect($this->records($response))
            ->filter(fn (array $record): bool => $this->modelMatches($record, $model)
                && $this->groupMatches($record, $group))
            ->unique(fn (array $record): string => (string) $this->registrationFromRecord($record))
            ->values();

        if ($matches->isEmpty()) {
            return null;
        }
        if ($matches->count() !== 1) {
            throw new RuntimeException('EPREL je vratio više artikala s istim identifikatorom modela.');
        }

        $registration = $this->registrationFromRecord($matches->first());
        if ($registration === null) {
            return null;
        }

        $detail = $this->get('/api/products/'.$group.'/'.$registration);
        if ($detail === null) {
            return null;
        }
        $record = $this->singleRecord($detail);
        if (! $record || ! $this->registrationMatches($record, $registration)
            || ! $this->groupMatches($record, $group)
            || ! $this->modelMatches($record, $model)) {
            return null;
        }

        return $this->normalizeRecord($record, $group, $registration);
    }

    /** @return array<string, mixed>|null */
    private function get(string $path, array $query = []): ?array
    {
        if (! $this->settings->eprelEnabled()) {
            throw new RuntimeException('EPREL dohvat nije uključen.');
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-api-key' => $this->settings->eprelApiKey()])
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout($this->settings->eprelConnectTimeout())
                ->timeout($this->settings->eprelTimeout())
                ->get(self::BASE_URL.$path, $query);
        } catch (ConnectionException) {
            throw new RuntimeException('Povezivanje sa službenim EPREL servisom nije uspjelo.');
        }

        if ($response->status() === 404) {
            return null;
        }
        $this->assertSuccessful($response);

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('EPREL servis vratio je neispravan odgovor.');
        }

        return $payload;
    }

    private function assertSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw match ($response->status()) {
            401, 403 => new RuntimeException('EPREL je odbio API ključ ili pristup.'),
            429 => new RuntimeException('EPREL je privremeno ograničio broj zahtjeva.'),
            default => new RuntimeException('EPREL servis trenutačno nije dostupan (HTTP '.$response->status().').'),
        };
    }

    private function validatedProductGroup(string $value): string
    {
        $group = strtolower(trim($value));
        if (! array_key_exists($group, self::PRODUCT_GROUPS)) {
            throw new InvalidArgumentException('EPREL grupa proizvoda nije podržana.');
        }

        return $group;
    }

    private function validatedRegistrationNumber(string $value): string
    {
        $number = trim($value);
        if (! preg_match('/^[0-9]{1,20}$/D', $number)) {
            throw new InvalidArgumentException('EPREL registracijski broj nije ispravan.');
        }

        return $number;
    }

    private function validatedModelIdentifier(string $value): string
    {
        $model = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($model === '' || mb_strlen($model) > 191
            || ! preg_match('/^[\pL\pN][\pL\pN ._+\-\/()#]{0,190}$/uD', $model)) {
            throw new InvalidArgumentException('EPREL identifikator modela nije ispravan.');
        }

        return $model;
    }

    /** @param array<string, mixed> $record */
    private function registrationMatches(array $record, string $registration): bool
    {
        $candidate = $this->registrationFromRecord($record);

        return $candidate !== null && hash_equals($registration, $candidate);
    }

    /** @param array<string, mixed> $record */
    private function modelMatches(array $record, string $model): bool
    {
        $candidate = $this->stringValue($record, [
            'modelIdentifier', 'model_identifier', 'model.identifier', 'product.modelIdentifier',
        ]);

        return $candidate !== null && hash_equals($model, $candidate);
    }

    /** @param array<string, mixed> $record */
    private function groupMatches(array $record, string $group): bool
    {
        $candidate = $this->stringValue($record, [
            'productGroup', 'product_group', 'productGroupCode', 'product.productGroup',
        ]);
        if ($candidate === null) {
            // A product-group-specific endpoint already scopes records to this
            // group; some API representations omit the repeated group field.
            return true;
        }

        return hash_equals($group, $this->groupSlugFromResponse($candidate) ?? '');
    }

    /** @param array<string, mixed> $record */
    private function registrationFromRecord(array $record): ?string
    {
        $candidate = $this->stringValue($record, [
            'eprelRegistrationNumber', 'registrationNumber', 'eprel_registration_number',
            'product.eprelRegistrationNumber',
        ]);

        return $candidate !== null && preg_match('/^[0-9]{1,20}$/D', $candidate)
            ? $candidate
            : null;
    }

    private function groupSlugFromResponse(string $value): ?string
    {
        $candidate = trim($value);
        $slug = strtolower($candidate);
        if (isset(self::PRODUCT_GROUPS[$slug])) {
            return $slug;
        }

        foreach (self::PRODUCT_GROUPS as $group => $code) {
            if (strcasecmp($code, $candidate) === 0) {
                return $group;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{
     *   eprel_registration_number:string,
     *   eprel_product_group:string,
     *   model_identifier:?string,
     *   energy_class:?string,
     *   scale_min:?string,
     *   scale_max:?string,
     *   energy_label_image:?string,
     *   energy_label_url:string,
     *   product_information_sheet_url:?string
     * }
     */
    private function normalizeRecord(array $record, string $group, string $registration): array
    {
        $energyClass = $this->energyClass($this->stringValue($record, [
            'energyClass', 'energyEfficiencyClass', 'energy_class',
            'energyLabel.energyClass', 'productData.energyClass',
        ]));
        $scaleMin = $this->energyClass($this->stringValue($record, [
            'scaleMin', 'energyScaleMin', 'energyScale.minimum', 'energyLabel.scaleMin',
        ]));
        $scaleMax = $this->energyClass($this->stringValue($record, [
            'scaleMax', 'energyScaleMax', 'energyScale.maximum', 'energyLabel.scaleMax',
        ]));
        if ($scaleMin === null || $scaleMax === null) {
            [$parsedMin, $parsedMax] = $this->parseScale($this->stringValue($record, [
                'energyScale', 'energyEfficiencyScale', 'energyClassRange', 'energyLabel.scale',
            ]));
            $scaleMin ??= $parsedMin;
            $scaleMax ??= $parsedMax;
        }

        $labelUrl = self::BASE_URL.'/api/products/'.$group.'/'.$registration.'/labels?format=PDF';
        $sheetUrl = $this->officialUrl($this->stringValue($record, [
            'productInformationSheetUrl', 'productInformationSheetURL',
            'product_information_sheet_url', 'informationSheetUrl',
            'documents.productInformationSheet.url', 'productInformationSheet.url',
        ])) ?: self::BASE_URL.'/fiches/'.$group.'/Fiche_'.$registration.'_HR.pdf';

        return [
            'eprel_registration_number' => $registration,
            'eprel_product_group' => $group,
            'model_identifier' => $this->stringValue($record, [
                'modelIdentifier', 'model_identifier', 'model.identifier', 'product.modelIdentifier',
            ]),
            'energy_class' => $energyClass,
            'scale_min' => $scaleMin,
            'scale_max' => $scaleMax,
            'energy_label_image' => $this->safeImageName($this->stringValue($record, [
                'energyClassImageWithScale', 'energyClassImage',
                'energyLabelImage', 'energy_label_image', 'energyLabel.image',
            ])),
            'energy_label_url' => $labelUrl,
            'product_information_sheet_url' => $sheetUrl,
        ];
    }

    /** @return array{?string, ?string} */
    private function parseScale(?string $value): array
    {
        if ($value === null) {
            return [null, null];
        }

        preg_match_all('/(?:A\+{1,3}|[A-G])/i', strtoupper($value), $matches);
        $classes = array_values(array_filter(array_map(
            fn (string $class): ?string => $this->energyClass($class),
            $matches[0] ?? [],
        )));

        return count($classes) >= 2
            ? [$classes[0], $classes[array_key_last($classes)]]
            : [null, null];
    }

    private function energyClass(?string $value): ?string
    {
        $class = strtoupper(trim((string) $value));

        return preg_match('/^(?:A\+{0,3}|[B-G])$/D', $class) ? $class : null;
    }

    private function officialUrl(?string $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }
        if (str_starts_with($url, '//')) {
            return null;
        }
        if (str_starts_with($url, '/')) {
            $url = self::BASE_URL.$url;
        } elseif (! preg_match('#^https://#i', $url)) {
            $url = self::BASE_URL.'/'.ltrim($url, '/');
        }

        $parts = parse_url($url);
        if (! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($parts['host'] ?? '')) !== 'eprel.ec.europa.eu'
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
            || isset($parts['fragment'])) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private function safeImageName(?string $value): ?string
    {
        $name = trim((string) $value);
        if ($name === '' || basename($name) !== $name || mb_strlen($name) > 255) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:svg|png|jpe?g|webp)$/iD', $name)
            ? $name
            : null;
    }

    /** @param array<string, mixed> $record */
    private function stringValue(array $record, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($record, $path);
            if (is_array($value)) {
                $value = $value['value'] ?? $value['code'] ?? $value['label'] ?? null;
            }
            if (! is_scalar($value)) {
                continue;
            }
            $normalized = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private function records(array $payload): array
    {
        foreach (['hits', 'results', 'items', 'content'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return array_values(array_filter($payload[$key], 'is_array'));
            }
        }
        if (isset($payload['data']) && is_array($payload['data'])) {
            return array_is_list($payload['data'])
                ? array_values(array_filter($payload['data'], 'is_array'))
                : [$payload['data']];
        }
        if (array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        return [$payload];
    }

    /** @return array<string, mixed>|null */
    private function singleRecord(array $payload): ?array
    {
        $records = $this->records($payload);

        return count($records) === 1 ? $records[0] : null;
    }
}
