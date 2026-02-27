@once
    @push('head')
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css">
        <style>
            /* Prevent layout jump before Splide CSS is downloaded. */
            .splide:not(.is-initialized) .splide__track { overflow: hidden; }
            .splide:not(.is-initialized) .splide__list { display: flex; margin: 0; padding: 0; list-style: none; }
            .splide:not(.is-initialized) .splide__slide { flex: 0 0 100%; min-width: 0; }
        </style>
    @endpush

    @push('scripts')
        <script defer src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>
    @endpush
@endonce
