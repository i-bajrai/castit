<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} - Construction Forecasting</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white">
        {{-- Navigation --}}
        <nav class="border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center">
                        <span class="text-2xl font-extrabold text-indigo-600">CastIt</span>
                    </div>
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Log in</a>
                            <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">Get Started</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        {{-- Hero Section --}}
        <section class="relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
                <div class="max-w-3xl">
                    <h1 class="text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight">
                        Forecast your construction projects with
                        <span class="text-indigo-600">confidence</span>
                    </h1>
                    <p class="mt-6 text-xl text-gray-600 leading-relaxed">
                        Stop guessing. CastIt gives construction teams accurate cost forecasts, schedule predictions, and risk analysis so you can deliver projects on time and on budget.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-3 bg-indigo-600 text-white text-base font-semibold rounded-lg hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                            Start Forecasting Free
                        </a>
                        <a href="#features" class="inline-flex items-center justify-center px-8 py-3 bg-white text-gray-700 text-base font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                            See How It Works
                        </a>
                    </div>
                </div>
            </div>
            <div class="absolute top-0 right-0 -z-10 w-1/2 h-full bg-gradient-to-l from-indigo-50 to-transparent"></div>
        </section>

        {{-- Stats Bar --}}
        <section class="bg-indigo-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
                    <div>
                        <p class="text-4xl font-bold text-white">30%</p>
                        <p class="mt-1 text-indigo-200">Fewer budget overruns</p>
                    </div>
                    <div>
                        <p class="text-4xl font-bold text-white">2x</p>
                        <p class="mt-1 text-indigo-200">Faster estimate turnaround</p>
                    </div>
                    <div>
                        <p class="text-4xl font-bold text-white">95%</p>
                        <p class="mt-1 text-indigo-200">Forecast accuracy</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="features" class="py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto">
                    <h2 class="text-3xl font-bold text-gray-900">Everything you need to forecast with precision</h2>
                    <p class="mt-4 text-lg text-gray-600">Built specifically for construction teams who need reliable projections, not spreadsheet headaches.</p>
                </div>

                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                    {{-- Feature 1 --}}
                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-indigo-50 transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900">Cost Forecasting</h3>
                        <p class="mt-2 text-gray-600">Track actual costs against estimates in real time. Get early warnings when a project is trending over budget before it's too late.</p>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-indigo-50 transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900">Schedule Predictions</h3>
                        <p class="mt-2 text-gray-600">Model completion dates based on current progress, weather patterns, and resource availability. Know your real finish date, not the optimistic one.</p>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-indigo-50 transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900">Risk Analysis</h3>
                        <p class="mt-2 text-gray-600">Identify and quantify project risks before they become problems. Run scenarios to understand the impact of delays, cost changes, and scope shifts.</p>
                    </div>

                    {{-- Feature 4 --}}
                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-indigo-50 transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900">Team Collaboration</h3>
                        <p class="mt-2 text-gray-600">Share forecasts with your entire project team. Everyone from PMs to site supervisors sees the same numbers, aligned on the same plan.</p>
                    </div>

                    {{-- Feature 5 --}}
                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-indigo-50 transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900">Visual Dashboards</h3>
                        <p class="mt-2 text-gray-600">See your project health at a glance with dashboards designed for construction. No data science degree required.</p>
                    </div>

                    {{-- Feature 6 --}}
                    <div class="p-8 rounded-2xl bg-gray-50 hover:bg-indigo-50 transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900">Report Generation</h3>
                        <p class="mt-2 text-gray-600">Generate stakeholder-ready reports in one click. Keep clients and investors informed with professional forecast summaries.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
                <h2 class="text-3xl font-bold text-white">Stop guessing. Start forecasting.</h2>
                <p class="mt-4 text-lg text-gray-400 max-w-2xl mx-auto">
                    Join construction teams that are delivering projects on time and on budget with data-driven forecasting.
                </p>
                <div class="mt-10">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-3 bg-indigo-600 text-white text-base font-semibold rounded-lg hover:bg-indigo-700 transition shadow-lg">
                        Create Your Free Account
                    </a>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <span class="text-sm text-gray-500">&copy; {{ date('Y') }} CastIt. All rights reserved.</span>
                    <span class="text-lg font-extrabold text-indigo-600">CastIt</span>
                </div>
            </div>
        </footer>
    </body>
</html>
