<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ url('assets/images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700&display=swap" rel="stylesheet">
    <link href="{{ url('assets/css/icons.min.css') }}" rel="stylesheet">

    <style>
        /* Measured against the app's own tokens; duplicated here because app.css
           has not loaded yet at the moment this is painted. */
        #app-preloader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            background: #fff;
            font-family: Nunito, system-ui, sans-serif;
            transition: opacity .25s ease, visibility .25s ease;
        }

        #app-preloader.is-done { opacity: 0; visibility: hidden; }

        .app-preloader__mark { width: 46px; height: 46px; color: #cc1f1f; animation: app-preloader-pulse 1.4s ease-in-out infinite; }
        .app-preloader__mark svg { width: 100%; height: 100%; }

        .app-preloader__bar { width: 148px; height: 3px; border-radius: 99px; background: #f0ecee; overflow: hidden; }
        .app-preloader__bar i { display: block; width: 40%; height: 100%; border-radius: 99px; background: #cc1f1f; animation: app-preloader-slide 1.1s ease-in-out infinite; }

        .app-preloader__label {
            margin: 0;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #8a8894;
        }

        @keyframes app-preloader-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .45; } }
        @keyframes app-preloader-slide { 0% { transform: translateX(-120%); } 100% { transform: translateX(370%); } }

        @media (prefers-reduced-motion: reduce) {
            .app-preloader__mark,
            .app-preloader__bar i { animation: none; }
            .app-preloader__bar i { width: 100%; }
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{--
        The preloader.

        Inline rather than a component: it has to be on screen before Vue, the
        router and the bundle have loaded, which is exactly the gap it covers.
        app.js removes it once the app has mounted; the `no-js`-style fallback is
        the 8s animation, after which it fades itself out rather than sitting on
        a broken page forever.
    --}}
    <div id="app-preloader">
        <div class="app-preloader__mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" />
            </svg>
        </div>
        <div class="app-preloader__bar"><i></i></div>
        <p class="app-preloader__label">Factory ERP</p>
    </div>

    <div id="app"></div>
</body>
</html>
