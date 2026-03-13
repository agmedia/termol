@php
    $title = trim((string) ($translation?->title ?? '')) !== ''
        ? trim((string) $translation->title)
        : __('ui.front.desktop.material_craftsmanship.default_title');
    $subtitle = trim((string) ($translation?->subtitle ?? '')) !== ''
        ? trim((string) $translation->subtitle)
        : __('ui.front.desktop.material_craftsmanship.default_subtitle');
    $expandLabel = __('ui.front.desktop.material_craftsmanship.expand');

    $materials = [
        [
            'key' => 'micromodal',
            'tone' => 'dark',
            'icon' => 'spark',
            'eyebrow' => __('ui.front.desktop.material_craftsmanship.materials.micromodal.eyebrow'),
            'title' => __('ui.front.desktop.material_craftsmanship.materials.micromodal.title'),
            'intro' => __('ui.front.desktop.material_craftsmanship.materials.micromodal.intro'),
            'body_1' => __('ui.front.desktop.material_craftsmanship.materials.micromodal.body_1'),
            'body_2' => __('ui.front.desktop.material_craftsmanship.materials.micromodal.body_2'),
            'bullets' => [
                ['icon' => 'touch', 'text' => __('ui.front.desktop.material_craftsmanship.materials.micromodal.bullets.0')],
                ['icon' => 'fit', 'text' => __('ui.front.desktop.material_craftsmanship.materials.micromodal.bullets.1')],
                ['icon' => 'shield', 'text' => __('ui.front.desktop.material_craftsmanship.materials.micromodal.bullets.2')],
            ],
        ],
        [
            'key' => 'giza',
            'tone' => 'light',
            'icon' => 'cotton',
            'eyebrow' => __('ui.front.desktop.material_craftsmanship.materials.giza.eyebrow'),
            'title' => __('ui.front.desktop.material_craftsmanship.materials.giza.title'),
            'intro' => __('ui.front.desktop.material_craftsmanship.materials.giza.intro'),
            'body_1' => __('ui.front.desktop.material_craftsmanship.materials.giza.body_1'),
            'body_2' => __('ui.front.desktop.material_craftsmanship.materials.giza.body_2'),
            'bullets' => [
                ['icon' => 'air', 'text' => __('ui.front.desktop.material_craftsmanship.materials.giza.bullets.0')],
                ['icon' => 'drop', 'text' => __('ui.front.desktop.material_craftsmanship.materials.giza.bullets.1')],
                ['icon' => 'shield', 'text' => __('ui.front.desktop.material_craftsmanship.materials.giza.bullets.2')],
            ],
        ],
    ];
@endphp

<section class="relative left-1/2 w-screen max-w-[100vw] -translate-x-1/2 overflow-x-hidden py-10 sm:py-12" id="material-comparison-{{ $block->id }}">
    <style>
        #material-comparison-{{ $block->id }} .materials-heading {
            max-width: 760px;
            margin: 0 auto;
            text-align: center;
        }

        #material-comparison-{{ $block->id }} .materials-title {
            margin: 0;
            color: #0f172a;
            font-size: 2.25rem;
            line-height: 1.04;
            letter-spacing: -0.045em;
            font-weight: 600;
        }

        #material-comparison-{{ $block->id }} .materials-subtitle {
            max-width: 640px;
            margin: 14px auto 0;
            color: #64748b;
            font-size: 1rem;
            line-height: 1.75;
        }

        #material-comparison-{{ $block->id }} .materials-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 2.75rem;
        }

        #material-comparison-{{ $block->id }} .material-card {
            height: 100%;
            background: #ffffff;
            border: 1px solid #dbe4ee;
            padding: 2rem 1.75rem 1.85rem;
        }

        #material-comparison-{{ $block->id }} .material-top {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        #material-comparison-{{ $block->id }} .material-hero-icon {
            width: 62px;
            height: 62px;
            border-radius: 999px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #material-comparison-{{ $block->id }} .material-kicker {
            margin: 4px 0 8px;
            font-size: 10px;
            line-height: 1.4;
            font-weight: 600;
            letter-spacing: 0.28em;
            text-transform: uppercase;
        }

        #material-comparison-{{ $block->id }} .material-name {
            margin: 0;
            color: #0f172a;
            font-size: 1.95rem;
            line-height: 1.02;
            letter-spacing: -0.045em;
            font-weight: 600;
        }

        #material-comparison-{{ $block->id }} .material-intro {
            margin: 1.5rem 0 0;
            color: #526072;
            font-size: 1rem;
            line-height: 1.8;
        }

        #material-comparison-{{ $block->id }} summary::-webkit-details-marker {
            display: none;
        }

        #material-comparison-{{ $block->id }} details[open] .material-expand-chevron {
            transform: rotate(180deg);
        }

        #material-comparison-{{ $block->id }} .material-summary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 1.1rem;
            cursor: pointer;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.2;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            list-style: none;
        }

        #material-comparison-{{ $block->id }} .material-summary:hover {
            opacity: 0.8;
        }

        #material-comparison-{{ $block->id }} .material-body {
            margin-top: 0.95rem;
            color: #526072;
            font-size: 15px;
            line-height: 1.8;
        }

        #material-comparison-{{ $block->id }} .material-body p {
            margin: 0;
        }

        #material-comparison-{{ $block->id }} .material-body p + p {
            margin-top: 0.8rem;
        }

        #material-comparison-{{ $block->id }} .material-benefits {
            display: grid;
            gap: 1rem;
            margin-top: 1.9rem;
            padding-top: 1.35rem;
            border-top: 1px solid #edf2f7;
        }

        #material-comparison-{{ $block->id }} .material-benefit {
            min-width: 0;
        }

        #material-comparison-{{ $block->id }} .material-benefit-icon {
            width: 50px;
            height: 50px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #material-comparison-{{ $block->id }} .material-benefit-label {
            margin: 0.8rem 0 0;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.55;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }

        @media (min-width: 640px) {
            #material-comparison-{{ $block->id }} .material-card {
                padding: 2.25rem 2rem 2rem;
            }

            #material-comparison-{{ $block->id }} .material-benefits {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            #material-comparison-{{ $block->id }} .materials-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="materials-heading">
            <h2 class="materials-title">{{ $title }}</h2>

            @if ($subtitle !== '')
                <p class="materials-subtitle">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="materials-grid">
            @foreach ($materials as $material)
                @php
                    $eyebrowStyle = $material['tone'] === 'dark' ? 'color:#5b6f8d;' : 'color:#8a6f58;';
                    $heroIconStyle = $material['tone'] === 'dark'
                        ? 'background:#eef2ff;color:#23344d;'
                        : 'background:#f7f1ea;color:#6c5948;';
                    $benefitIconStyle = $material['tone'] === 'dark'
                        ? 'background:#f3f6fb;color:#23344d;'
                        : 'background:#faf5ef;color:#6c5948;';
                @endphp

                <article class="material-card">
                    <div class="material-top">
                        <span class="material-hero-icon" style="{{ $heroIconStyle }}">
                            @switch($material['icon'])
                                @case('spark')
                                    <svg width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 3.5v4"></path>
                                        <path d="M12 16.5v4"></path>
                                        <path d="M3.5 12h4"></path>
                                        <path d="M16.5 12h4"></path>
                                        <path d="m6.2 6.2 2.8 2.8"></path>
                                        <path d="m15 15 2.8 2.8"></path>
                                        <path d="m17.8 6.2-2.8 2.8"></path>
                                        <path d="m9 15-2.8 2.8"></path>
                                    </svg>
                                    @break
                                @default
                                    <svg width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M7.5 10A3.6 3.6 0 0 1 12 6.2 3.6 3.6 0 0 1 16.5 10"></path>
                                        <path d="M8 10.2c-2 0-3.6 1.6-3.6 3.6S6 17.4 8 17.4h8c2 0 3.6-1.6 3.6-3.6s-1.6-3.6-3.6-3.6"></path>
                                        <path d="M12 9.4v8"></path>
                                    </svg>
                            @endswitch
                        </span>

                        <div>
                            <p class="material-kicker" style="{{ $eyebrowStyle }}">{{ $material['eyebrow'] }}</p>
                            <h3 class="material-name">{{ $material['title'] }}</h3>
                        </div>
                    </div>

                    <p class="material-intro">{{ $material['intro'] }}</p>

                    <details>
                        <summary class="material-summary">
                            <span>{{ $expandLabel }}</span>
                            <svg class="material-expand-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </summary>

                        <div class="material-body">
                            <p>{{ $material['body_1'] }}</p>
                            <p>{{ $material['body_2'] }}</p>
                        </div>
                    </details>

                    <div class="material-benefits">
                        @foreach ($material['bullets'] as $bullet)
                            <div class="material-benefit">
                                <span class="material-benefit-icon" style="{{ $benefitIconStyle }}">
                                    @switch($bullet['icon'])
                                        @case('touch')
                                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 4.2c2.7 2.9 4 5 4 7a4 4 0 1 1-8 0c0-2 1.3-4.1 4-7Z"></path>
                                            </svg>
                                            @break
                                        @case('fit')
                                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M4.5 12h15"></path>
                                                <path d="m8.2 8.3-3.7 3.7 3.7 3.7"></path>
                                                <path d="m15.8 8.3 3.7 3.7-3.7 3.7"></path>
                                            </svg>
                                            @break
                                        @case('air')
                                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M4 9h10.5c1.8 0 3-1 3-2.2 0-1.3-1-2.2-2.2-2.2"></path>
                                                <path d="M4 14.5h13.5c1.8 0 3 1 3 2.2 0 1.3-1 2.2-2.2 2.2"></path>
                                            </svg>
                                            @break
                                        @case('drop')
                                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 3.5c3.2 4.1 4.6 6.6 4.6 8.8a4.6 4.6 0 1 1-9.2 0c0-2.2 1.4-4.7 4.6-8.8Z"></path>
                                            </svg>
                                            @break
                                        @case('shield')
                                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 3 6 5.5v5.2c0 4 2.5 7.7 6 9.3 3.5-1.6 6-5.3 6-9.3V5.5L12 3Z"></path>
                                                <path d="m9.7 12.4 1.7 1.8 3.6-3.8"></path>
                                            </svg>
                                            @break
                                        @default
                                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M7.5 10A3.6 3.6 0 0 1 12 6.2 3.6 3.6 0 0 1 16.5 10"></path>
                                                <path d="M8 10.2c-2 0-3.6 1.6-3.6 3.6S6 17.4 8 17.4h8c2 0 3.6-1.6 3.6-3.6s-1.6-3.6-3.6-3.6"></path>
                                                <path d="M12 9.4v8"></path>
                                            </svg>
                                    @endswitch
                                </span>
                                <p class="material-benefit-label">{{ $bullet['text'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
