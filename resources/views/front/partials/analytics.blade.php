@php
    $analytics = $storeSettings['analytics'] ?? [];
    $cookies = $storeSettings['cookies'] ?? [];
    $analyticsEnabled = (bool) ($analytics['enabled'] ?? false);
    $ga4Id = trim((string) ($analytics['ga4_measurement_id'] ?? ''));
    $cookieConsentEnabled = (bool) ($cookies['enabled'] ?? true);
@endphp

@if ($analyticsEnabled && $ga4Id !== '')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        window.updateGoogleConsentFromCookie = function (analyticsGranted, marketingGranted) {
            gtag('consent', 'update', {
                analytics_storage: analyticsGranted ? 'granted' : 'denied',
                ad_storage: marketingGranted ? 'granted' : 'denied',
                ad_user_data: marketingGranted ? 'granted' : 'denied',
                ad_personalization: marketingGranted ? 'granted' : 'denied',
            });
        };
        @if ($cookieConsentEnabled)
            gtag('consent', 'default', {
                analytics_storage: 'denied',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied',
            });
        @else
            gtag('consent', 'default', {
                analytics_storage: 'granted',
                ad_storage: 'granted',
                ad_user_data: 'granted',
                ad_personalization: 'granted',
            });
            window.cookieAnalyticsAllowed = true;
            window.canTrackAnalytics = () => true;
        @endif
        gtag('config', '{{ $ga4Id }}');
    </script>
    <script defer src="{{ asset('front-theme/scripts/shop-analytics.js') }}?v={{ filemtime(public_path('front-theme/scripts/shop-analytics.js')) }}"></script>
@endif
