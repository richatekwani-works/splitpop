<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SplitPop') · split bills, keep friends</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍿</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/splitpop.css') }}">
    <script src="{{ asset('js/splitpop.js') }}" defer></script>
</head>
<body>
    <div class="ticker" aria-hidden="true">
        <span>NO LOGIN ★ NO STRESS ★ NO AWKWARD "BRO YOU OWE ME" TEXTS ★ NO LOGIN ★ NO STRESS ★ NO AWKWARD "BRO YOU OWE ME" TEXTS ★ NO LOGIN ★ NO STRESS ★ NO AWKWARD "BRO YOU OWE ME" TEXTS ★ NO LOGIN ★ NO STRESS ★ NO AWKWARD "BRO YOU OWE ME" TEXTS ★ </span>
    </div>

    <header class="top">
        <a class="logo" href="{{ route('home') }}"><span class="logo-pop">💥</span> Split<em>Pop</em></a>
        <span class="tagline">math so you don't have to</span>
    </header>

    <main class="wrap">
        @if (session('ok'))
            <div class="toast" role="status">{{ session('ok') }}</div>
        @endif

        @if ($errors->any())
            <div class="card oops" role="alert">
                <strong>Hold up 🛑</strong>
                <ul>
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="foot">built with caffeine &amp; bad decisions ☕</footer>
</body>
</html>
