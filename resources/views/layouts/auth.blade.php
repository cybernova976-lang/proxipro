<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Lunamars') }} - @yield('title', 'Authentification')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        .fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

        html, body { width: 100%; max-width: 100%; overflow-x: clip; -webkit-text-size-adjust: 100%; }
        img, video, svg { max-width: 100%; }

        @media (max-width: 640px) {
            input, select, textarea { font-size: 16px !important; }
            .min-h-screen { min-height: 100dvh; }
            body > .min-h-screen { display: block; }
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50">
    <div class="min-h-screen flex">
        @yield('content')
    </div>
</body>
</html>
