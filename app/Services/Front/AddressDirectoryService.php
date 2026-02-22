<?php

namespace App\Services\Front;

final class AddressDirectoryService
{
    /**
     * @return array{countries:array<int,array{code:string,name_hr:string,name_en:string}>,counties_hr:array<int,string>,places:array<int,array{postal_code:string,city:string,county:string,country_code:string}>}
     */
    public function data(): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $path = public_path('front-theme/data/hr-places.json');

        if (! is_file($path)) {
            $cached = [
                'countries' => [
                    ['code' => 'HR', 'name_hr' => 'Hrvatska', 'name_en' => 'Croatia'],
                ],
                'counties_hr' => [],
                'places' => [],
            ];

            return $cached;
        }

        $raw = json_decode((string) file_get_contents($path), true);

        if (! is_array($raw)) {
            $cached = [
                'countries' => [
                    ['code' => 'HR', 'name_hr' => 'Hrvatska', 'name_en' => 'Croatia'],
                ],
                'counties_hr' => [],
                'places' => [],
            ];

            return $cached;
        }

        $cached = [
            'countries' => array_values(array_filter((array) ($raw['countries'] ?? []), 'is_array')),
            'counties_hr' => array_values(array_filter((array) ($raw['counties_hr'] ?? []), 'is_string')),
            'places' => array_values(array_filter((array) ($raw['places'] ?? []), 'is_array')),
        ];

        return $cached;
    }

    /**
     * @return array<int,array{code:string,label:string}>
     */
    public function countries(string $locale = 'hr'): array
    {
        $isHr = str_starts_with(strtolower($locale), 'hr');

        return array_map(static function (array $country) use ($isHr): array {
            return [
                'code' => (string) ($country['code'] ?? ''),
                'label' => (string) ($isHr ? ($country['name_hr'] ?? $country['name_en'] ?? $country['code'] ?? '') : ($country['name_en'] ?? $country['name_hr'] ?? $country['code'] ?? '')),
            ];
        }, $this->data()['countries']);
    }

    /**
     * @return array<int,string>
     */
    public function counties(): array
    {
        return $this->data()['counties_hr'];
    }

    public function placesAssetUrl(): string
    {
        return asset('front-theme/data/hr-places.json');
    }
}
