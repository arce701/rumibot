<?php

use App\Exports\ConversationsExport;
use App\Models\Channel;
use App\Models\Conversation;
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

    $this->channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    Conversation::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);

    app(TenantContext::class)->set($this->tenant);
});

test('conversations export generates xlsx', function () {
    Excel::fake();
    Feature::define('data-export', fn () => true);

    $expectedFilename = 'conversations-'.now()->format('Y-m-d').'.xlsx';

    Livewire\Livewire::actingAs($this->user)
        ->test(\App\Livewire\Conversations\ConversationList::class)
        ->call('exportConversations');

    Excel::assertDownloaded($expectedFilename);
});

test('conversations export respects tenant scope', function () {
    $otherTenant = Tenant::factory()->create();
    $otherChannel = Channel::factory()->create(['tenant_id' => $otherTenant->id]);
    Conversation::factory()->create([
        'tenant_id' => $otherTenant->id,
        'channel_id' => $otherChannel->id,
    ]);

    $export = new ConversationsExport;
    $results = $export->query()->get();

    $results->each(function ($conversation) {
        expect($conversation->tenant_id)->toBe($this->tenant->id);
    });
});

test('conversations export is blocked without data-export feature', function () {
    Feature::define('data-export', fn () => false);
    Feature::flushCache();

    Livewire\Livewire::actingAs($this->user)
        ->test(\App\Livewire\Conversations\ConversationList::class)
        ->call('exportConversations')
        ->assertSee(__('Export not available on your plan.'));
});

test('conversations export filters by channel', function () {
    $otherChannel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $otherChannel->id,
    ]);

    $export = new ConversationsExport($this->channel->id);
    $results = $export->query()->get();

    $results->each(function ($conversation) {
        expect($conversation->channel_id)->toBe($this->channel->id);
    });
});
