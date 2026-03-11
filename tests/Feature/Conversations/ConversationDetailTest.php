<?php

use App\Jobs\SendWhatsAppMessage;
use App\Livewire\Conversations\ConversationDetail;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();

    $this->channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->owner = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->owner->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->owner->assignRole('tenant_owner');

    $this->conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
        'messages_count' => 0,
    ]);
});

test('sendReply creates human message and pauses AI', function () {
    Queue::fake([SendWhatsAppMessage::class]);

    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->set('replyText', 'Hola, soy un operador humano')
        ->call('sendReply')
        ->assertHasNoErrors();

    $message = Message::withoutGlobalScopes()
        ->where('conversation_id', $this->conversation->id)
        ->where('role', 'assistant')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->toBe('Hola, soy un operador humano');
    expect($message->metadata['provider'])->toBe('human');
    expect($message->metadata['sent_by'])->toBe($this->owner->name);

    $this->conversation->refresh();
    expect($this->conversation->messages_count)->toBe(1);
    expect($this->conversation->ai_paused_until)->not->toBeNull();
    expect($this->conversation->isAiPaused())->toBeTrue();

    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use ($message) {
        return $job->text === 'Hola, soy un operador humano'
            && $job->messageId === $message->id;
    });
});

test('sendReply validates reply text is required', function () {
    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->set('replyText', '')
        ->call('sendReply')
        ->assertHasErrors(['replyText' => 'required']);
});

test('resumeAi clears ai_paused_until', function () {
    $this->conversation->update(['ai_paused_until' => now()->addHours(24)]);

    expect($this->conversation->isAiPaused())->toBeTrue();

    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->call('resumeAi')
        ->assertHasNoErrors();

    $this->conversation->refresh();
    expect($this->conversation->ai_paused_until)->toBeNull();
    expect($this->conversation->isAiPaused())->toBeFalse();
});

test('isAiPaused returns true when paused until future', function () {
    $this->conversation->update(['ai_paused_until' => now()->addHours(1)]);
    $this->conversation->refresh();

    expect($this->conversation->isAiPaused())->toBeTrue();
});

test('isAiPaused returns false when pause has expired', function () {
    $this->conversation->update(['ai_paused_until' => now()->subHour()]);
    $this->conversation->refresh();

    expect($this->conversation->isAiPaused())->toBeFalse();
});

test('isAiPaused returns false when ai_paused_until is null', function () {
    expect($this->conversation->isAiPaused())->toBeFalse();
});

test('sendReply with image uploads to S3 and dispatches job with mediaType image', function () {
    Queue::fake([SendWhatsAppMessage::class]);
    Storage::fake('s3');

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(2048);

    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->set('replyText', 'Check this photo')
        ->set('attachment', $file)
        ->call('sendReply')
        ->assertHasNoErrors();

    $message = Message::withoutGlobalScopes()
        ->where('conversation_id', $this->conversation->id)
        ->where('role', 'assistant')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->toBe('Check this photo');
    expect($message->metadata['media_type'])->toBe('image');
    expect($message->metadata['media_filename'])->toBe('photo.jpg');
    expect($message->metadata['media_path'])->toContain("tenants/{$this->tenant->id}/attachments");

    Storage::disk('s3')->assertExists($message->metadata['media_path']);

    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
        return $job->mediaType === 'image'
            && $job->mediaPath !== null
            && $job->mediaFilename === 'photo.jpg';
    });
});

test('sendReply with document uploads to S3 and dispatches job with mediaType document', function () {
    Queue::fake([SendWhatsAppMessage::class]);
    Storage::fake('s3');

    $file = UploadedFile::fake()->create('report.pdf', 5000, 'application/pdf');

    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->set('replyText', 'Here is the report')
        ->set('attachment', $file)
        ->call('sendReply')
        ->assertHasNoErrors();

    $message = Message::withoutGlobalScopes()
        ->where('conversation_id', $this->conversation->id)
        ->where('role', 'assistant')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->metadata['media_type'])->toBe('document');
    expect($message->metadata['media_filename'])->toBe('report.pdf');

    Storage::disk('s3')->assertExists($message->metadata['media_path']);

    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
        return $job->mediaType === 'document'
            && $job->mediaFilename === 'report.pdf';
    });
});

test('sendReply with attachment and no text does not require replyText', function () {
    Queue::fake([SendWhatsAppMessage::class]);
    Storage::fake('s3');

    $file = UploadedFile::fake()->image('photo.png', 400, 400)->size(1024);

    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->set('replyText', '')
        ->set('attachment', $file)
        ->call('sendReply')
        ->assertHasNoErrors();

    $message = Message::withoutGlobalScopes()
        ->where('conversation_id', $this->conversation->id)
        ->where('role', 'assistant')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->toBe('');
    expect($message->metadata['media_type'])->toBe('image');
});

test('sendReply without attachment still requires text (regression)', function () {
    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->set('replyText', '')
        ->call('sendReply')
        ->assertHasErrors(['replyText' => 'required']);
});

test('sendReply works on escalated conversations', function () {
    Queue::fake([SendWhatsAppMessage::class]);

    $this->conversation->update(['status' => ConversationStatus::Escalated]);

    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->set('replyText', 'We are handling your escalation')
        ->call('sendReply')
        ->assertHasNoErrors();

    $message = Message::withoutGlobalScopes()
        ->where('conversation_id', $this->conversation->id)
        ->where('role', 'assistant')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->toBe('We are handling your escalation');
    expect($message->metadata['provider'])->toBe('human');
});

test('removeAttachment clears the attachment property', function () {
    Storage::fake('s3');

    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    Livewire::actingAs($this->owner)
        ->test(ConversationDetail::class, ['conversation' => $this->conversation])
        ->set('attachment', $file)
        ->assertSet('attachment', fn ($val) => $val !== null)
        ->call('removeAttachment')
        ->assertSet('attachment', null);
});
