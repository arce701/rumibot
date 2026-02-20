<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ __('landing.meta_description') }}">

        <title>{{ config('app.name') }} - {{ __('landing.hero_title') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 font-sans antialiased">

        {{-- Navbar --}}
        <nav class="sticky top-0 z-50 border-b border-zinc-200 dark:border-zinc-800 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md" x-data="{ mobileOpen: false }">
            <div class="mx-auto max-w-6xl flex items-center justify-between px-6 py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="flex size-8 items-center justify-center rounded-md bg-zinc-900 dark:bg-white">
                        <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
                    </span>
                    <span class="text-lg font-semibold">Rumibot</span>
                </a>

                {{-- Desktop nav --}}
                <div class="hidden lg:flex items-center gap-3">
                    {{-- Language Switcher --}}
                    @php $currentLocale = auth()->check() ? (auth()->user()->locale ?? app()->getLocale()) : app()->getLocale(); @endphp
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="flex items-center gap-1 rounded-md px-2.5 py-1.5 text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.467.73-3.558" />
                            </svg>
                            <span>{{ $currentLocale === 'pt_BR' ? 'PT' : strtoupper($currentLocale) }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition.opacity class="absolute right-0 mt-1 w-36 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 py-1 shadow-lg">
                            <a href="{{ url('locale/es') }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $currentLocale === 'es' ? 'font-semibold text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}">
                                Español
                            </a>
                            <a href="{{ url('locale/en') }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $currentLocale === 'en' ? 'font-semibold text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}">
                                English
                            </a>
                            <a href="{{ url('locale/pt_BR') }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $currentLocale === 'pt_BR' ? 'font-semibold text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}">
                                Português
                            </a>
                        </div>
                    </div>

                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-md bg-zinc-900 dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-zinc-900 hover:bg-zinc-700 dark:hover:bg-zinc-200 transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                                {{ __('landing.hero_login') }}
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-md bg-zinc-900 dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-zinc-900 hover:bg-zinc-700 dark:hover:bg-zinc-200 transition-colors">
                                    {{ __('landing.hero_cta') }}
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                {{-- Mobile hamburger button --}}
                <button
                    @click="mobileOpen = !mobileOpen"
                    class="lg:hidden flex items-center justify-center rounded-md p-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                    aria-label="Toggle menu"
                    :aria-expanded="mobileOpen"
                >
                    <svg x-show="!mobileOpen" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg x-show="mobileOpen" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Mobile menu panel --}}
            <div
                x-show="mobileOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                x-cloak
                class="lg:hidden border-t border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-6 py-4"
            >
                {{-- Language buttons --}}
                @php $currentLocale = auth()->check() ? (auth()->user()->locale ?? app()->getLocale()) : app()->getLocale(); @endphp
                <div class="flex items-center gap-2 mb-4">
                    <a href="{{ url('locale/es') }}" class="rounded-md px-3 py-1.5 text-sm transition-colors {{ $currentLocale === 'es' ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                        ES
                    </a>
                    <a href="{{ url('locale/en') }}" class="rounded-md px-3 py-1.5 text-sm transition-colors {{ $currentLocale === 'en' ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                        EN
                    </a>
                    <a href="{{ url('locale/pt_BR') }}" class="rounded-md px-3 py-1.5 text-sm transition-colors {{ $currentLocale === 'pt_BR' ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                        PT
                    </a>
                </div>

                {{-- Auth links --}}
                @if (Route::has('login'))
                    <div class="flex flex-col gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-md bg-zinc-900 dark:bg-white px-4 py-2.5 text-sm font-medium text-white dark:text-zinc-900 text-center hover:bg-zinc-700 dark:hover:bg-zinc-200 transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md border border-zinc-300 dark:border-zinc-700 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 text-center hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors">
                                {{ __('landing.hero_login') }}
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-md bg-zinc-900 dark:bg-white px-4 py-2.5 text-sm font-medium text-white dark:text-zinc-900 text-center hover:bg-zinc-700 dark:hover:bg-zinc-200 transition-colors">
                                    {{ __('landing.hero_cta') }}
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </nav>

        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-950"></div>

            {{-- Central glow --}}
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-gradient-to-r from-green-500/10 via-emerald-500/5 to-green-500/10 rounded-full blur-3xl pointer-events-none"></div>

            {{-- Decorative sparkles --}}
            <svg class="absolute top-16 left-[15%] size-6 text-green-400/30 animate-pulse pointer-events-none" viewBox="0 0 32 32" fill="currentColor" style="animation-delay: 0s">
                <path d="M16 2Q18 14 30 16Q18 18 16 30Q14 18 2 16Q14 14 16 2Z"/>
            </svg>
            <svg class="absolute top-32 right-[20%] size-4 text-emerald-400/20 animate-pulse pointer-events-none" viewBox="0 0 32 32" fill="currentColor" style="animation-delay: 0.5s">
                <path d="M16 2Q18 14 30 16Q18 18 16 30Q14 18 2 16Q14 14 16 2Z"/>
            </svg>
            <svg class="absolute bottom-24 left-[25%] size-5 text-green-300/25 animate-pulse pointer-events-none" viewBox="0 0 32 32" fill="currentColor" style="animation-delay: 1s">
                <path d="M16 2Q18 14 30 16Q18 18 16 30Q14 18 2 16Q14 14 16 2Z"/>
            </svg>
            <svg class="absolute bottom-16 right-[15%] size-3 text-emerald-300/20 animate-pulse pointer-events-none" viewBox="0 0 32 32" fill="currentColor" style="animation-delay: 1.5s">
                <path d="M16 2Q18 14 30 16Q18 18 16 30Q14 18 2 16Q14 14 16 2Z"/>
            </svg>

            <div class="relative mx-auto max-w-4xl px-6 py-24 sm:py-32 lg:py-40 text-center">
                <div class="landing-animate">
                    <span class="inline-flex items-center gap-2 rounded-full border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-1.5 text-sm text-zinc-600 dark:text-zinc-400 mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.126.553 4.122 1.522 5.857L.06 23.649a.6.6 0 0 0 .732.727l5.735-1.505A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.78 9.78 0 0 1-5.202-1.493l-.373-.222-3.868 1.014 1.033-3.773-.244-.388A9.78 9.78 0 0 1 2.182 12 9.818 9.818 0 0 1 12 2.182 9.818 9.818 0 0 1 21.818 12 9.818 9.818 0 0 1 12 21.818z"/>
                        </svg>
                        WhatsApp + AI
                    </span>
                </div>

                <h1 class="landing-animate text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 leading-tight">
                    {{ __('landing.hero_title') }}
                </h1>

                <p class="landing-animate mt-6 text-lg sm:text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto leading-relaxed">
                    {{ __('landing.hero_subtitle') }}
                </p>

                <div class="landing-animate mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-lg bg-zinc-900 dark:bg-white px-8 py-3 text-base font-medium text-white dark:text-zinc-900 hover:bg-zinc-700 dark:hover:bg-zinc-200 transition-colors shadow-sm">
                            {{ __('landing.hero_cta') }}
                        </a>
                    @endif
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="rounded-lg border border-zinc-300 dark:border-zinc-700 px-8 py-3 text-base font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors">
                            {{ __('landing.hero_login') }}
                        </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
            <div class="mx-auto max-w-6xl px-6 py-24 sm:py-32">
                <div class="text-center mb-16 landing-animate">
                    <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
                        {{ __('landing.features_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                        {{ __('landing.features_subtitle') }}
                    </p>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Feature: WhatsApp AI --}}
                    <div class="landing-animate group rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 transition-shadow hover:shadow-lg">
                        <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.feature_whatsapp_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.feature_whatsapp_description') }}</p>
                    </div>

                    {{-- Feature: Sales --}}
                    <div class="landing-animate group rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 transition-shadow hover:shadow-lg">
                        <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.feature_sales_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.feature_sales_description') }}</p>
                    </div>

                    {{-- Feature: Support --}}
                    <div class="landing-animate group rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 transition-shadow hover:shadow-lg">
                        <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 0 1 1.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 0 0-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 0 1 0 9.424m-4.138-5.976a3.736 3.736 0 0 0-.88-1.388 3.737 3.737 0 0 0-1.388-.88m2.268 2.268a3.765 3.765 0 0 1 0 2.528m-2.268-4.796a3.765 3.765 0 0 0-2.528 0m4.796 4.796c-.181.506-.475.982-.88 1.388a3.736 3.736 0 0 1-1.388.88m2.268-2.268 4.138 3.448m0 0a9.027 9.027 0 0 1-1.306 1.652 9.027 9.027 0 0 1-1.652 1.306m0 0-3.448-4.138m3.448 4.138a9.014 9.014 0 0 1-9.424 0m5.976-4.138a3.765 3.765 0 0 1-2.528 0m0 0a3.736 3.736 0 0 1-1.388-.88 3.737 3.737 0 0 1-.88-1.388m2.268 2.268L7.288 19.67m0 0a9.024 9.024 0 0 1-1.652-1.306 9.027 9.027 0 0 1-1.306-1.652m0 0 4.138-3.448M4.33 16.712a9.014 9.014 0 0 1 0-9.424m4.138 5.976a3.765 3.765 0 0 1 0-2.528m0 0c.181-.506.475-.982.88-1.388a3.736 3.736 0 0 1 1.388-.88m-2.268 2.268L4.33 7.288m6.406 1.18L7.288 4.33m0 0a9.024 9.024 0 0 0-1.652 1.306A9.025 9.025 0 0 0 4.33 7.288" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.feature_support_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.feature_support_description') }}</p>
                    </div>

                    {{-- Feature: Knowledge Base --}}
                    <div class="landing-animate group rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 transition-shadow hover:shadow-lg">
                        <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.feature_knowledge_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.feature_knowledge_description') }}</p>
                    </div>

                    {{-- Feature: Multi-language --}}
                    <div class="landing-animate group rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 transition-shadow hover:shadow-lg">
                        <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.feature_multilang_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.feature_multilang_description') }}</p>
                    </div>

                    {{-- Feature: Analytics --}}
                    <div class="landing-animate group rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 transition-shadow hover:shadow-lg">
                        <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-cyan-50 dark:bg-cyan-900/20 text-cyan-600 dark:text-cyan-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.feature_analytics_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.feature_analytics_description') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-6xl px-6 py-24 sm:py-32">
                <div class="text-center mb-16 landing-animate">
                    <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
                        {{ __('landing.how_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                        {{ __('landing.how_subtitle') }}
                    </p>
                </div>

                <div class="relative grid gap-12 lg:grid-cols-3 lg:gap-8">
                    {{-- Connector line (desktop only) --}}
                    <div class="hidden lg:block absolute top-10 left-[calc(16.67%+1rem)] right-[calc(16.67%+1rem)] h-px bg-zinc-200 dark:bg-zinc-700"></div>

                    {{-- Step 1 --}}
                    <div class="landing-animate text-center">
                        <div class="relative mx-auto mb-6 flex size-20 items-center justify-center rounded-full border-2 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                            <span class="text-2xl font-bold text-green-600 dark:text-green-400">1</span>
                        </div>
                        <div class="mb-3 flex justify-center text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.how_step1_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.how_step1_description') }}</p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="landing-animate text-center">
                        <div class="relative mx-auto mb-6 flex size-20 items-center justify-center rounded-full border-2 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                            <span class="text-2xl font-bold text-green-600 dark:text-green-400">2</span>
                        </div>
                        <div class="mb-3 flex justify-center text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.how_step2_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.how_step2_description') }}</p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="landing-animate text-center">
                        <div class="relative mx-auto mb-6 flex size-20 items-center justify-center rounded-full border-2 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                            <span class="text-2xl font-bold text-green-600 dark:text-green-400">3</span>
                        </div>
                        <div class="mb-3 flex justify-center text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('landing.how_step3_title') }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('landing.how_step3_description') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Stats / Social proof --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
            <div class="mx-auto max-w-6xl px-6 py-24 sm:py-32">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Stat: Available --}}
                    <div class="landing-animate text-center">
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-50">{{ __('landing.stats_available') }}</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('landing.stats_available_desc') }}</p>
                    </div>

                    {{-- Stat: Languages --}}
                    <div class="landing-animate text-center">
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.467.73-3.558" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-50">{{ __('landing.stats_languages') }}</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('landing.stats_languages_desc') }}</p>
                    </div>

                    {{-- Stat: Response time --}}
                    <div class="landing-animate text-center">
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-50">{{ __('landing.stats_response') }}</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('landing.stats_response_desc') }}</p>
                    </div>

                    {{-- Stat: Unlimited --}}
                    <div class="landing-animate text-center">
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-50">{{ __('landing.stats_unlimited') }}</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('landing.stats_unlimited_desc') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="relative overflow-hidden border-t border-zinc-200 dark:border-zinc-800 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900">
            {{-- Decorative sparkles --}}
            <svg class="absolute top-8 left-[10%] size-5 text-green-400/20 animate-pulse pointer-events-none" viewBox="0 0 32 32" fill="currentColor" style="animation-delay: 0.3s">
                <path d="M16 2Q18 14 30 16Q18 18 16 30Q14 18 2 16Q14 14 16 2Z"/>
            </svg>
            <svg class="absolute bottom-8 right-[10%] size-4 text-emerald-400/15 animate-pulse pointer-events-none" viewBox="0 0 32 32" fill="currentColor" style="animation-delay: 0.8s">
                <path d="M16 2Q18 14 30 16Q18 18 16 30Q14 18 2 16Q14 14 16 2Z"/>
            </svg>

            <div class="relative mx-auto max-w-4xl px-6 py-24 sm:py-32 text-center landing-animate">
                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-white">
                    {{ __('landing.cta_title') }}
                </h2>
                <p class="mt-4 text-lg text-zinc-300 max-w-xl mx-auto">
                    {{ __('landing.cta_subtitle') }}
                </p>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="mt-8 inline-block rounded-lg bg-white px-8 py-3 text-base font-medium text-zinc-900 hover:bg-zinc-100 transition-colors shadow-sm">
                        {{ __('landing.cta_button') }}
                    </a>
                @endif
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
            <div class="mx-auto max-w-6xl px-6 pt-8 pb-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 items-center justify-center rounded bg-zinc-900 dark:bg-white">
                            <x-app-logo-icon class="size-3.5 fill-current text-white dark:text-black" />
                        </span>
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Rumibot</span>
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">&mdash; {{ __('landing.footer_tagline') }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                        <a href="#" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">{{ __('landing.footer_terms') }}</a>
                        <span class="text-zinc-300 dark:text-zinc-600">|</span>
                        <a href="#" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">{{ __('landing.footer_privacy') }}</a>
                        <span class="text-zinc-300 dark:text-zinc-600">|</span>
                        <a href="#" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">{{ __('landing.footer_contact') }}</a>
                    </div>
                </div>
                <div class="mt-6 border-t border-zinc-200 dark:border-zinc-800 pt-4 text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-500">
                        &copy; {{ date('Y') }} Rumibot. {{ __('landing.footer_rights') }}
                    </p>
                </div>
            </div>
        </footer>

        @fluxScripts

        {{-- Scroll Animation with staggered delays per section --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const elements = document.querySelectorAll('.landing-animate');

                elements.forEach(function (el) {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(1.5rem)';
                    el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                });

                const observer = new IntersectionObserver(function (entries) {
                    const groups = {};

                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            const parent = entry.target.parentElement;
                            const key = parent ? parent.getAttribute('data-section') || parent.tagName + parent.className.slice(0, 30) : 'default';
                            if (!groups[key]) {
                                groups[key] = [];
                            }
                            groups[key].push(entry.target);
                        }
                    });

                    Object.values(groups).forEach(function (group) {
                        group.forEach(function (el, index) {
                            setTimeout(function () {
                                el.style.opacity = '1';
                                el.style.transform = 'translateY(0)';
                            }, index * 100);
                            observer.unobserve(el);
                        });
                    });
                }, { threshold: 0.1 });

                elements.forEach(function (el) {
                    observer.observe(el);
                });
            });
        </script>
    </body>
</html>
