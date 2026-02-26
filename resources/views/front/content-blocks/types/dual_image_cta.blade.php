@php
    $locale = app()->getLocale();
    $fallbackLocale = config('app.locale');
    $translationPayload = is_array($translation?->payload ?? null) ? $translation->payload : [];
    $customClasses = trim((string) ($translationPayload['custom_classes'] ?? ''));
    $preferWebp = (bool) ($storeSettings['images']['use_webp'] ?? false);
    $slides = $block->getMedia('block_slides')->take(2);
@endphp

@if ($slides->isNotEmpty())
    <section class="relative left-1/2 w-screen -translate-x-1/2 {{ $customClasses }}">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            @foreach ($slides as $media)
                @php
                    $imageUrl1200 = \App\Support\Media\MediaUrl::conversionOrNull($media, 'hero_1200w', $preferWebp);
                    $imageUrl960 = \App\Support\Media\MediaUrl::conversionOrNull($media, 'hero_960w', $preferWebp);
                    $imageUrl720 = \App\Support\Media\MediaUrl::conversionOrNull($media, 'hero_720w', $preferWebp);
                    $imageUrl540 = \App\Support\Media\MediaUrl::conversionOrNull($media, 'hero_540w', $preferWebp);
                    $imageUrl = $imageUrl1200 ?? $imageUrl960 ?? $imageUrl720 ?? $imageUrl540 ?? $media->getUrl();
                    $imageSrcset = collect([
                        $imageUrl540 ? $imageUrl540.' 540w' : null,
                        $imageUrl720 ? $imageUrl720.' 720w' : null,
                        $imageUrl960 ? $imageUrl960.' 960w' : null,
                        $imageUrl1200 ? $imageUrl1200.' 1200w' : null,
                    ])->filter()->unique()->implode(', ');
                    $imageWidth = max(1, (int) ($media->width ?? 1200));
                    $imageHeight = max(1, (int) ($media->height ?? 700));
                    $props = (array) ($media->custom_properties ?? []);
                    $slideTitle = trim((string) (
                        data_get($props, "block_title.$locale")
                        ?: data_get($props, "block_title.$fallbackLocale")
                        ?: $media->name
                    ));

                    $cta1Label = trim((string) (
                        data_get($props, "cta_1_label.$locale")
                        ?: data_get($props, "cta_1_label.$fallbackLocale")
                        ?: __('ui.content_blocks.dual_image_cta.default_cta_1')
                    ));
                    $cta1Url = trim((string) (
                        data_get($props, "cta_1_url.$locale")
                        ?: data_get($props, "cta_1_url.$fallbackLocale")
                        ?: '#'
                    ));

                    $cta2Label = trim((string) (
                        data_get($props, "cta_2_label.$locale")
                        ?: data_get($props, "cta_2_label.$fallbackLocale")
                        ?: __('ui.content_blocks.dual_image_cta.default_cta_2')
                    ));
                    $cta2Url = trim((string) (
                        data_get($props, "cta_2_url.$locale")
                        ?: data_get($props, "cta_2_url.$fallbackLocale")
                        ?: '#'
                    ));
                @endphp

                <article class="group relative overflow-hidden">
                    <img
                        src="{{ $imageUrl }}"
                        @if ($imageSrcset !== '') srcset="{{ $imageSrcset }}" @endif
                        sizes="(max-width: 767px) 100vw, (max-width: 1536px) 47vw, 720px"
                        alt="{{ $slideTitle !== '' ? $slideTitle : $block->name }}"
                        class="h-auto w-full bg-slate-100 object-contain transition duration-500 group-hover:scale-[1.02]"
                        width="{{ $imageWidth }}"
                        height="{{ $imageHeight }}"
                        @if ($loop->first)
                            loading="eager"
                            fetchpriority="high"
                        @else
                            loading="lazy"
                        @endif
                        decoding="async"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/20 to-transparent"></div>

                    <div class="absolute inset-x-0 bottom-12 px-8 text-center text-white md:bottom-16 md:px-10">
                        @if ($slideTitle !== '')
                            <h3 class="text-3xl font-black uppercase tracking-[0.02em] md:text-4xl">{{ $slideTitle }}</h3>
                        @endif

                        <div class="mx-auto mt-5 flex max-w-[460px] flex-col justify-center gap-2.5 sm:flex-row">
                            @if ($cta1Label !== '')
                                <a href="{{ $cta1Url !== '' ? $cta1Url : '#' }}" class="inline-flex h-11 min-w-[145px] items-center justify-center border border-white bg-white px-5 text-base font-black uppercase tracking-[0.02em] text-slate-900 transition hover:bg-slate-100">
                                    {{ $cta1Label }}
                                </a>
                            @endif

                            @if ($cta2Label !== '')
                                <a href="{{ $cta2Url !== '' ? $cta2Url : '#' }}" class="inline-flex h-11 min-w-[145px] items-center justify-center border border-white bg-white px-5 text-base font-black uppercase tracking-[0.02em] text-slate-900 transition hover:bg-slate-100">
                                    {{ $cta2Label }}
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
