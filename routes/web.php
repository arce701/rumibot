<?php

use App\Livewire\ActivityLog\ActivityLogViewer;
use App\Livewire\Billing\PaymentHistory;
use App\Livewire\Billing\SubscriptionManager;
use App\Livewire\Channels\ChannelManager;
use App\Livewire\Conversations\ConversationDetail;
use App\Livewire\Conversations\ConversationList;
use App\Livewire\Dashboard;
use App\Livewire\Escalations\EscalationQueue;
use App\Livewire\Integrations\IntegrationManager;
use App\Livewire\Knowledge\KnowledgeManager;
use App\Livewire\Leads\LeadsList;
use App\Livewire\Platform\PlanManager;
use App\Livewire\Platform\PlatformBilling;
use App\Livewire\Platform\PlatformDashboard;
use App\Livewire\Platform\TenantDetail;
use App\Livewire\Platform\TenantIndex;
use App\Livewire\Prompts\PromptEditor;
use App\Livewire\Team\TeamManager;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('channels', ChannelManager::class)->name('channels');
    Route::get('prompts', PromptEditor::class)->name('prompts');
    Route::get('conversations', ConversationList::class)->name('conversations');
    Route::get('conversations/{conversation}', ConversationDetail::class)->name('conversations.show');
    Route::get('leads', LeadsList::class)->name('leads');
    Route::get('escalations', EscalationQueue::class)->name('escalations');
    Route::get('team', TeamManager::class)->name('team');
    Route::get('knowledge', KnowledgeManager::class)->name('knowledge');
    Route::get('integrations', IntegrationManager::class)->name('integrations');
    Route::get('activity-log', ActivityLogViewer::class)->name('activity-log');
    Route::get('billing', SubscriptionManager::class)->name('billing');
    Route::get('billing/payments', PaymentHistory::class)->name('billing.payments');
});

Route::prefix('platform')->middleware(['auth', 'verified', 'super-admin'])->group(function () {
    Route::get('/', PlatformDashboard::class)->name('platform.dashboard');
    Route::get('tenants', TenantIndex::class)->name('platform.tenants');
    Route::get('tenants/{tenant}', TenantDetail::class)->name('platform.tenants.show');
    Route::get('plans', PlanManager::class)->name('platform.plans');
    Route::get('billing', PlatformBilling::class)->name('platform.billing');
});

require __DIR__.'/settings.php';
