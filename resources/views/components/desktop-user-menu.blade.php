<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
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
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>
        </flux:menu.radio.group>
        <flux:menu.separator />
        <div class="px-3 py-1.5">
            <flux:text size="xs" class="text-zinc-500 uppercase font-medium">{{ __('Language') }}</flux:text>
        </div>
        <flux:menu.radio.group>
            @php $currentLocale = auth()->user()->locale ?? app()->getLocale(); @endphp
            <flux:menu.item href="{{ url('locale/es') }}" icon="{{ $currentLocale === 'es' ? 'check' : 'minus' }}" class="{{ $currentLocale === 'es' ? 'font-semibold' : '' }}">
                Español
            </flux:menu.item>
            <flux:menu.item href="{{ url('locale/en') }}" icon="{{ $currentLocale === 'en' ? 'check' : 'minus' }}" class="{{ $currentLocale === 'en' ? 'font-semibold' : '' }}">
                English
            </flux:menu.item>
            <flux:menu.item href="{{ url('locale/pt_BR') }}" icon="{{ $currentLocale === 'pt_BR' ? 'check' : 'minus' }}" class="{{ $currentLocale === 'pt_BR' ? 'font-semibold' : '' }}">
                Português
            </flux:menu.item>
        </flux:menu.radio.group>
        <flux:menu.separator />
        <flux:menu.radio.group>
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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
