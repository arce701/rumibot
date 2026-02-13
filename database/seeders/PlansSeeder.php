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
        $plan = Plan::create([
            'name' => 'Rumibot',
            'slug' => 'rumibot',
            'description' => 'Access to the full Rumibot platform. Channels, AI chatbots, knowledge base, leads, integrations, analytics and exports. Users configure and pay their own AI provider.',
            'sort_order' => 1,
        ]);

        $prices = [
            ['billing_interval' => 'quarterly', 'price_amount' => 3000],
            ['billing_interval' => 'semi_annual', 'price_amount' => 5500],
            ['billing_interval' => 'annual', 'price_amount' => 11000],
        ];

        foreach ($prices as $price) {
            PlanPrice::create([
                'plan_id' => $plan->id,
                'currency' => 'USD',
                ...$price,
            ]);
        }

        $features = [
            ['feature_slug' => 'max_channels', 'value' => 'unlimited'],
            ['feature_slug' => 'max_messages', 'value' => 'unlimited'],
            ['feature_slug' => 'max_documents', 'value' => 'unlimited'],
            ['feature_slug' => 'max_team_members', 'value' => 'unlimited'],
            ['feature_slug' => 'max_integrations', 'value' => 'unlimited'],
            ['feature_slug' => 'analytics', 'value' => '1'],
            ['feature_slug' => 'data_export', 'value' => '1'],
        ];

        foreach ($features as $feature) {
            PlanFeature::create([
                'plan_id' => $plan->id,
                ...$feature,
            ]);
        }
    }
}
