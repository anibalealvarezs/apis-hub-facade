<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="themeControl" 
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | APIs Hub</title>
    @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .legal-body { font-family: 'Outfit', sans-serif; transition: background-color 0.5s ease, color 0.5s ease; }
        .legal-main { padding-top: 160px; padding-bottom: 120px; padding-left: 2rem; padding-right: 2rem; min-height: 100vh; }
        .legal-header { height: 96px; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; position: fixed; top: 0; width: 100%; z-index: 1000; border-bottom: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px); }
        .legal-footer { border-top: 1px solid rgba(255,255,255,0.05); padding: 3rem 2rem; display: flex; flex-direction: column; align-items: center; gap: 1.5rem; text-align: center; }
        .footer-links { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; }
        .footer-link { margin: 0 1.5rem; text-decoration: none; font-size: 10px; letter-spacing: 0.3em; font-weight: bold; text-transform: uppercase; transition: color 0.3s ease; }
        .footer-dot { width: 6px; height: 6px; border-radius: 50%; }
        
        /* Light/Dark Overrides for safety */
        .dark .legal-body { background-color: #020617; color: #f1f5f9; }
        .legal-body:not(.dark) { background-color: #ffffff; color: #0f172a; }
        
        .dark .legal-header { background-color: rgba(2, 6, 23, 0.8); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .legal-body:not(.dark) .legal-header { background-color: rgba(255, 255, 255, 0.8); border-bottom: 1px solid rgba(0,0,0,0.05); }

        .legal-content h1 { font-size: 3.5rem; font-weight: 900; margin-bottom: 3rem; letter-spacing: -0.05em; line-height: 1; }
        .legal-content h2 { font-size: 1.8rem; font-weight: 700; margin-top: 4rem; margin-bottom: 1.5rem; color: #00A7F9; }
        .legal-content h3 { font-size: 1.25rem; font-weight: 700; margin-top: 2.5rem; margin-bottom: 0.75rem; }
        .legal-content p { font-size: 1.125rem; line-height: 1.7; margin-bottom: 1.5rem; font-weight: 300; }
        .legal-content ul, .legal-content ol { margin-bottom: 2rem; padding-left: 2rem; }
        .legal-content li { margin-bottom: 0.75rem; font-size: 1.125rem; font-weight: 300; }
        .legal-content a { color: #00A7F9; font-weight: 700; text-decoration: none; }
        .legal-content a:hover { text-decoration: underline; }
        .legal-content hr { border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 4rem 0; }
        .legal-body:not(.dark) .legal-content hr { border-top: 1px solid rgba(0,0,0,0.1); }
    </style>
</head>
<body class="legal-body antialiased" :class="{ 'dark': darkMode }">
    
    <nav class="legal-header">
        <a href="/" style="transition: all 0.3s ease; display: inline-block;">
            <img src="{{ asset('images/branding/apishub-trans-620.webp') }}" alt="APIs Hub" style="height: 40px;" :style="darkMode ? 'display:none' : 'display:block'">
            <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" alt="APIs Hub" style="height: 40px;" :style="darkMode ? 'display:block' : 'display:none'">
        </a>
        
        <div style="display: flex; align-items: center; gap: 2rem;">
            <button @click="darkMode = !darkMode" style="background: rgba(128,128,128,0.1); border: 1px solid rgba(128,128,128,0.2); padding: 10px; border-radius: 50%; cursor: pointer;">
                <span x-show="darkMode">☀️</span>
                <span x-show="!darkMode">🌙</span>
            </button>
            <a href="/" style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: inherit; text-decoration: none;">Back to Home</a>
        </div>
    </nav>

    <main class="legal-main">
        <div class="legal-content" style="max-width: 900px; margin: 0 auto;">
            @yield('content')
        </div>
    </main>

    <footer class="legal-footer">
        <!-- Footer Links: OLD SCHOOL FLEX - NO FAIL -->
        <div class="footer-links">
            <a href="/privacy" class="footer-link" style="color: inherit; opacity: 0.6;">Privacy</a>
            <span class="footer-dot" style="background: #00A7F9;"></span>
            <a href="/tos" class="footer-link" style="color: inherit; opacity: 0.6;">Terms</a>
            <span class="footer-dot" style="background: #00CAC4;"></span>
            <a href="/data-deletion" class="footer-link" style="color: inherit; opacity: 0.6;">Data Deletion</a>
        </div>
        <div style="font-size: 10px; letter-spacing: 0.2em; font-weight: bold; opacity: 0.5; color: inherit;">
            ENGINEERED BY <a href="https://anibalalvarez.com" target="_blank" style="color: #00A7F9; text-decoration: none;">ANÍBAL ÁLVAREZ</a> FOR THE APIS HUB NETWORK. (V1.0)
        </div>
    </footer>
</body>
</html>
