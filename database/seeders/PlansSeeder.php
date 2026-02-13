<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPrice;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'description' => 'Ideal para emprendedores y pequeños negocios que inician con atención automatizada.',
                'sort_order' => 1,
                'prices' => [
                    ['billing_interval' => 'quarterly', 'price_amount' => 15000],
                    ['billing_interval' => 'semi_annual', 'price_amount' => 27000],
                    ['billing_interval' => 'annual', 'price_amount' => 48000],
                ],
                'features' => [
                    ['feature_slug' => 'max_channels', 'value' => '1'],
                    ['feature_slug' => 'max_messages', 'value' => '500'],
                    ['feature_slug' => 'max_documents', 'value' => '3'],
                    ['feature_slug' => 'max_team_members', 'value' => '1'],
                ],
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'description' => 'Para negocios en crecimiento que necesitan más capacidad y herramientas avanzadas.',
                'sort_order' => 2,
                'prices' => [
                    ['billing_interval' => 'quarterly', 'price_amount' => 45000],
                    ['billing_interval' => 'semi_annual', 'price_amount' => 81000],
                    ['billing_interval' => 'annual', 'price_amount' => 144000],
                ],
                'features' => [
                    ['feature_slug' => 'max_channels', 'value' => '3'],
                    ['feature_slug' => 'max_messages', 'value' => '2000'],
                    ['feature_slug' => 'max_documents', 'value' => '15'],
                    ['feature_slug' => 'max_team_members', 'value' => '5'],
                    ['feature_slug' => 'max_integrations', 'value' => '3'],
                    ['feature_slug' => 'analytics', 'value' => '1'],
                ],
            ],
            [
                'name' => 'Empresa',
                'slug' => 'empresa',
                'description' => 'Para empresas que requieren máxima capacidad, integraciones ilimitadas y soporte prioritario.',
                'sort_order' => 3,
                'prices' => [
                    ['billing_interval' => 'quarterly', 'price_amount' => 120000],
                    ['billing_interval' => 'semi_annual', 'price_amount' => 216000],
                    ['billing_interval' => 'annual', 'price_amount' => 384000],
                ],
                'features' => [
                    ['feature_slug' => 'max_channels', 'value' => '10'],
                    ['feature_slug' => 'max_messages', 'value' => 'unlimited'],
                    ['feature_slug' => 'max_documents', 'value' => 'unlimited'],
                    ['feature_slug' => 'max_team_members', 'value' => '20'],
                    ['feature_slug' => 'max_integrations', 'value' => 'unlimited'],
                    ['feature_slug' => 'analytics', 'value' => '1'],
                    ['feature_slug' => 'data_export', 'value' => '1'],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $prices = $planData['prices'];
            $features = $planData['features'];
            unset($planData['prices'], $planData['features']);

            $plan = Plan::create($planData);

            foreach ($prices as $price) {
                PlanPrice::create([
                    'plan_id' => $plan->id,
                    'currency' => 'PEN',
                    ...$price,
                ]);
            }

            foreach ($features as $feature) {
                PlanFeature::create([
                    'plan_id' => $plan->id,
                    ...$feature,
                ]);
            }
        }
    }
}
