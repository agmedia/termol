@php
    $analytics = $storeSettings['analytics'] ?? [];
    $cookies = $storeSettings['cookies'] ?? [];
    $analyticsEnabled = (bool) ($analytics['enabled'] ?? false);
    $ga4Id = trim((string) ($analytics['ga4_measurement_id'] ?? ''));
    $cookieConsentEnabled = (bool) ($cookies['enabled'] ?? true);
    $metaPixelId = '2376960792811713';
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

@if ($metaPixelId !== '')
    <script>
        window.loadMetaPixel = window.loadMetaPixel || function () {
            if (window.__metaPixelLoaded === true) {
                return;
            }
            window.__metaPixelLoaded = true;

            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window,document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');

            fbq('consent', 'grant');
            fbq('init', @json($metaPixelId));
            fbq('track', 'PageView');
        };

        window.updateMetaPixelConsentFromCookie = function (marketingGranted) {
            if (!marketingGranted) {
                if (typeof window.fbq === 'function') {
                    window.fbq('consent', 'revoke');
                }

                return;
            }

            window.loadMetaPixel();

            if (typeof window.fbq === 'function') {
                window.fbq('consent', 'grant');
            }
        };

        @unless ($cookieConsentEnabled)
            window.cookieMarketingAllowed = true;
            window.loadMetaPixel();
        @endunless
    </script>
    @unless ($cookieConsentEnabled)
        <noscript>
            <img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $metaPixelId }}&amp;ev=PageView&amp;noscript=1">
        </noscript>
    @endunless
@endif
