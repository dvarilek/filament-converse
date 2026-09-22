<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\EditMessageAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

use function Pest\Livewire\livewire;

test('is only visible to message author', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle($owner, $participant);

    /* @var Message $ownerMessage */
    $ownerMessage = $conversation
        ->participations()
        ->firstWhere('participant_id', $owner->getKey())
        ->sendMessage($conversation, [
            'content' => 'test',
        ]);

    /* @var Message $participantMessage */
    $participantMessage = $conversation
        ->participations()
        ->firstWhere('participant_id', $participant->getKey())
        ->sendMessage($conversation, [
            'content' => 'test',
        ]);

    livewire(ConversationManager::class)
        ->assertActionVisible(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $ownerMessage->getKey(),
                ]),
        )
        ->assertActionHidden(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $participantMessage->getKey(),
                ]),
        );
});

test('can update a message', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle($owner, $participant);
    /* @var Message $message */
    $message = $conversation->participations()->first()->sendMessage($conversation, [
        'content' => 'message',
    ]);

    livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $message->getKey(),
                ]),
            [
                'messageContent' => 'updated message',
            ]
        )
        ->assertHasNoFormErrors();

    expect($message->fresh()->content)->toBe('updated message');
});

test('can update a message with attachments', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    $conversation = app(CreateConversation::class)->handle($owner, $participant);

    $message = $conversation->participations()->first()->sendMessage($conversation, [
        'content' => 'message',
        'attachments' => ['old-file.pdf'],
        'attachment_file_names' => ['old-file.pdf'],
    ]);

    livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $message->getKey(),
                ]),
            [
                'messageContent' => 'message',
                'attachments' => [
                    TemporaryUploadedFile::fake()->create(
                        'new-file.pdf',
                        100,
                        'application/pdf',
                    ),
                ],
            ]
        )
        ->assertHasNoFormErrors();

    /* @var Message $message */
    $message = $message->fresh();

    expect($message->attachments)
        ->toHaveCount(2)
        ->and($message->attachment_file_names)
        ->toHaveCount(2)
        ->toBe([
            'old-file.pdf',
            'new-file.pdf',
        ]);
});

test('can remove previously uploaded attachments', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();
    $this->actingAs($owner);

    $conversation = app(CreateConversation::class)->handle($owner, $participant);

    Storage::fake('local');
    $disk = Storage::disk('local');

    $attachmentToRemove = 'old-file.pdf';
    $attachmentToKeep = 'old-file2.pdf';
    $disk->put($attachmentToRemove, 'fake PDF content');
    $disk->put($attachmentToKeep, 'fake PDF content 2');

    $message = $conversation->participations()->first()->sendMessage($conversation, [
        'content' => 'message',
        'attachments' => [$attachmentToRemove, $attachmentToKeep],
        'attachment_file_names' => [$attachmentToRemove, $attachmentToKeep],
    ]);

    expect($disk->exists($attachmentToRemove))->toBeTrue()
        ->and($disk->exists($attachmentToKeep))->toBeTrue();

    livewire(ConversationManager::class)
        ->mountAction(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $message->getKey(),
                ]),
        )
        ->call(
            'callSchemaComponentMethod',
            'mountedActionSchema0.attachments',
            'removeUploadedFile',
            [$attachmentToRemove],
        )
        ->callMountedAction()
        ->assertHasNoFormErrors();

    $updatedMessage = $message->fresh();

    expect($updatedMessage->attachments)->toBe([$attachmentToKeep])
        ->and($updatedMessage->attachment_file_names)->toBe([$attachmentToKeep])
        ->and($disk->exists($attachmentToRemove))->toBeFalse()
        ->and($disk->exists($attachmentToKeep))->toBeTrue();
});

test('does not update the message updated_at when no changes were made', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    /** @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle($owner, $participant);

    /** @var Message $message */
    $message = $conversation->participations()->first()->sendMessage($conversation, [
        'content' => 'message',
    ]);

    $originalUpdatedAt = $message->fresh()->updated_at;

    livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $message->getKey(),
                ]),
            [
                'messageContent' => 'message',
            ],
        )
        ->assertHasNoFormErrors();

    expect($message->fresh()->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});
