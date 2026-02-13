<?php

namespace Database\Seeders;

use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\PaymentStatus;
use App\Models\Enums\SubscriptionStatus;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(PlansSeeder::class);

        $tenant = Tenant::create([
            'name' => 'RumiStar E.I.R.L.',
            'slug' => 'rumistar',
            'system_prompt' => 'Eres el asistente virtual de iTrade, un software de comercio exterior desarrollado por RumiStar. Ayudas a prospectos interesados en el sistema respondiendo consultas sobre funcionalidades, precios, y beneficios. Eres amable, profesional y conoces a fondo el producto.',
            'default_ai_provider' => 'openai',
            'default_ai_model' => 'gpt-4o-mini',
            'timezone' => 'America/Lima',
            'locale' => 'es',
            'is_platform_owner' => true,
            'settings' => [],
        ]);

        $superAdmin = User::create([
            'name' => 'Rumibot Admin',
            'email' => 'rumibot8@gmail.com',
            'password' => bcrypt('Rumi2026$'),
            'email_verified_at' => now(),
            'is_super_admin' => true,
            'current_tenant_id' => $tenant->id,
            'locale' => 'es',
        ]);

        app()[PermissionRegistrar::class]->setPermissionsTeamId($tenant->id);

        $superAdmin->assignRole('tenant_owner');

        $tenant->users()->attach($superAdmin->id, [
            'role' => 'tenant_owner',
            'is_default' => true,
        ]);

        $plan = Plan::where('slug', 'rumibot')->first();
        $price = PlanPrice::where('plan_id', $plan->id)
            ->where('billing_interval', 'annual')
            ->first();

        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'plan_price_id' => $price->id,
            'status' => SubscriptionStatus::Active,
            'payment_provider' => PaymentProviderType::Manual,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addYear(),
        ]);

        PaymentHistory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'payment_provider' => PaymentProviderType::Manual,
            'status' => PaymentStatus::Completed,
            'amount' => $price->price_amount,
            'currency' => 'USD',
            'description' => 'Platform owner - Annual subscription',
        ]);
    }
}
