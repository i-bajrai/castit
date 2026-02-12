<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page Not Found - {{ config('app.name', 'CastIt') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center px-4">
            <div class="text-center max-w-md">
                <p class="text-7xl font-bold text-indigo-600">404</p>
                <h1 class="mt-4 text-2xl font-semibold text-gray-900">Page not found</h1>
                <p class="mt-2 text-sm text-gray-500">The page you're looking for doesn't exist or may have been moved.</p>
                <div class="mt-8 flex items-center justify-center gap-3">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Go back
                    </a>
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-indigo-700">
                        Dashboard
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
