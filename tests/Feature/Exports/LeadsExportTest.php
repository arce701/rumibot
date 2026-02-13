<?php

use App\Exports\LeadsExport;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Laravel\Pennant\Feature;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->user->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole('tenant_owner');

    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    Lead::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $conversation->id,
    ]);

    app(TenantContext::class)->set($this->tenant);
});

test('leads export generates xlsx with correct headings', function () {
    Excel::fake();
    Feature::define('data-export', fn () => true);

    $expectedFilename = 'leads-'.now()->format('Y-m-d').'.xlsx';

    Livewire\Livewire::actingAs($this->user)
        ->test(\App\Livewire\Leads\LeadsList::class)
        ->call('exportLeads');

    Excel::assertDownloaded($expectedFilename);
});

test('leads export respects tenant scope', function () {
    $otherTenant = Tenant::factory()->create();
    $otherChannel = Channel::factory()->create(['tenant_id' => $otherTenant->id]);
    $otherConversation = Conversation::factory()->create([
        'tenant_id' => $otherTenant->id,
        'channel_id' => $otherChannel->id,
    ]);

    Lead::factory()->create([
        'tenant_id' => $otherTenant->id,
        'conversation_id' => $otherConversation->id,
    ]);

    $export = new LeadsExport;
    $results = $export->query()->get();

    $results->each(function ($lead) {
        expect($lead->tenant_id)->toBe($this->tenant->id);
    });
});

test('leads export is blocked without data-export feature', function () {
    Feature::define('data-export', fn () => false);
    Feature::flushCache();

    Livewire\Livewire::actingAs($this->user)
        ->test(\App\Livewire\Leads\LeadsList::class)
        ->call('exportLeads')
        ->assertSee(__('Export not available on your plan.'));
});

test('leads export filters by status', function () {
    $export = new LeadsExport('new', '');
    $results = $export->query()->get();

    $results->each(function ($lead) {
        expect($lead->status->value)->toBe('new');
    });
});
