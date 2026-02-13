<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @if (auth()->user()->isSuperAdmin())
                    <flux:sidebar.group :heading="__('Administration')" class="grid">
                        <flux:sidebar.item icon="building-office" :href="route('platform.dashboard')" :current="request()->routeIs('platform.dashboard')" wire:navigate>
                            {{ __('Platform') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="user-group" :href="route('platform.tenants')" :current="request()->routeIs('platform.tenants*')" wire:navigate>
                            {{ __('Tenants') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="rectangle-stack" :href="route('platform.plans')" :current="request()->routeIs('platform.plans')" wire:navigate>
                            {{ __('Plans') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="banknotes" :href="route('platform.billing')" :current="request()->routeIs('platform.billing')" wire:navigate>
                            {{ __('Platform Billing') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @can('channels.view')
                        <flux:sidebar.item icon="signal" :href="route('channels')" :current="request()->routeIs('channels')" wire:navigate>
                            {{ __('Channels') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('prompts.view')
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('prompts')" :current="request()->routeIs('prompts')" wire:navigate>
                            {{ __('Prompts') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('conversations.view')
                        <flux:sidebar.item icon="chat-bubble-bottom-center-text" :href="route('conversations')" :current="request()->routeIs('conversations*')" wire:navigate>
                            {{ __('Conversations') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('leads.view')
                        <flux:sidebar.item icon="user-group" :href="route('leads')" :current="request()->routeIs('leads')" wire:navigate>
                            {{ __('Leads') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('escalations.view')
                        <flux:sidebar.item icon="exclamation-triangle" :href="route('escalations')" :current="request()->routeIs('escalations')" wire:navigate>
                            {{ __('Escalations') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('knowledge.view')
                        <flux:sidebar.item icon="book-open" :href="route('knowledge')" :current="request()->routeIs('knowledge')" wire:navigate>
                            {{ __('Knowledge Base') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('integrations.view')
                        <flux:sidebar.item icon="puzzle-piece" :href="route('integrations')" :current="request()->routeIs('integrations')" wire:navigate>
                            {{ __('Integrations') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('team.view')
                        <flux:sidebar.item icon="users" :href="route('team')" :current="request()->routeIs('team')" wire:navigate>
                            {{ __('Team') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('billing.view')
                        <flux:sidebar.item icon="credit-card" :href="route('billing')" :current="request()->routeIs('billing*')" wire:navigate>
                            {{ __('Billing') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('settings.view')
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('activity-log')" :current="request()->routeIs('activity-log')" wire:navigate>
                            {{ __('Activity Log') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>


        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
