<?php

namespace App\Providers;

use App\Events\ConversationClosed;
use App\Events\ConversationStarted;
use App\Events\EscalationTriggered;
use App\Events\LeadCaptured;
use App\Events\MessageReceived;
use App\Listeners\DispatchTenantIntegrationEvents;
use App\Listeners\SendEscalationNotification;
use App\Models\User;
use App\Services\Billing\MercadoPagoProvider;
use App\Services\Billing\PlanFeatureGate;
use App\Services\Billing\SubscriptionManager;
use App\Services\Tenant\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(PlanFeatureGate::class);
        $this->app->singleton(SubscriptionManager::class);
        $this->app->bind(MercadoPagoProvider::class, fn ($app) => new MercadoPagoProvider(
            config('rumibot.billing.mercadopago.access_token', ''),
        ));
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureEvents();
        $this->configureRateLimiting();
        $this->configurePennantFeatures();
        $this->configurePulse();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureGates(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });
    }

    protected function configureEvents(): void
    {
        Event::listen(EscalationTriggered::class, SendEscalationNotification::class);

        $integrationEvents = [
            ConversationStarted::class,
            MessageReceived::class,
            LeadCaptured::class,
            EscalationTriggered::class,
            ConversationClosed::class,
        ];

        foreach ($integrationEvents as $event) {
            Event::listen($event, DispatchTenantIntegrationEvents::class);
        }
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('tenant-api', function (Request $request) {
            $user = $request->user();

            return $user?->current_tenant_id
                ? Limit::perMinute(60)->by('tenant-api:'.$user->current_tenant_id)
                : Limit::perMinute(10)->by('tenant-api:'.$request->ip());
        });

        RateLimiter::for('webhook-whatsapp', function (Request $request) {
            $channelSlug = $request->route('channelSlug', 'unknown');
            $tenantUuid = $request->route('tenantUuid', 'unknown');

            return Limit::perMinute(120)->by('webhook:'.$tenantUuid.':'.$channelSlug);
        });

        RateLimiter::for('webhook-payments', function (Request $request) {
            return Limit::perMinute(60)->by('webhook-payments:'.$request->ip());
        });
    }

    protected function configurePulse(): void
    {
        Gate::define('viewPulse', fn (User $user) => $user->isSuperAdmin());
    }

    protected function configurePennantFeatures(): void
    {
        $gate = app(PlanFeatureGate::class);

        Feature::define('webhook-integrations', function () use ($gate): bool {
            $tenant = app(TenantContext::class)->get();

            return $tenant ? $gate->canAccess($tenant, 'max_integrations') : false;
        });

        Feature::define('analytics-dashboard', function () use ($gate): bool {
            $tenant = app(TenantContext::class)->get();

            return $tenant ? $gate->canAccess($tenant, 'analytics') : false;
        });

        Feature::define('data-export', function () use ($gate): bool {
            $tenant = app(TenantContext::class)->get();

            return $tenant ? $gate->canAccess($tenant, 'data_export') : false;
        });
    }
}
