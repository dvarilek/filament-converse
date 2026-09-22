<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Testing\TestAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ReplyToMessageAction;
use Illuminate\Support\Collection;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationSchema;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\CancelReplyToMessageAction;

use function Pest\Livewire\livewire;

describe('render', function () {})->todo();

describe('message input', function () {
    test('can send message', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($owner, $otherUser);

        livewire(ConversationManager::class)
            ->fillForm([
                'conversation_schema.messageContent' => 'Test message',
            ])
            ->callAction(
                TestAction::make('sendMessage')
                    ->schemaComponent('conversation_schema.conversation_thread')
            )
            ->assertHasNoFormErrors();

        /* @var Collection<int, Message> $messages */
        $messages = $conversation->refresh()->messages;

        expect($messages)
            ->toHaveCount(1)
            ->and($messages->first())
            ->content->toBe('Test message')
            ->attachments->toBe([]);
    });

    test('can send message with attachments', function () {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $this->actingAs($owner);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($owner, $participant);

        Storage::fake('local');
        $disk = Storage::disk('local');

        livewire(ConversationManager::class)
            ->fillForm([
                'conversation_schema.attachments' => [
                    TemporaryUploadedFile::fake()->create(
                        'file1.pdf',
                        100,
                        'application/pdf',
                    ),
                    TemporaryUploadedFile::fake()->create(
                        'file2.pdf',
                        100,
                        'application/pdf',
                    )
                ],
            ])
            ->callAction(
                TestAction::make('sendMessage')
                    ->schemaComponent('conversation_schema.conversation_thread')
            )
            ->assertHasNoFormErrors();

        /* @var Collection<int, Message> $messages */
        $messages = $conversation->refresh()->messages;

        expect($messages)
            ->toHaveCount(1)
            ->and($message = $messages->first())
            ->content->toBeNull()
            ->attachments->toHaveCount(2)
            ->attachment_file_names->toBe([
                'file1.pdf',
                'file2.pdf'
            ])
            ->and($disk->exists($message->attachments[0]))->toBeTrue()
            ->and($disk->exists($message->attachments[1]))->toBeTrue();
    });

    test('cannot send empty message', function () {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $this->actingAs($owner);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($owner, $participant);

        livewire(ConversationManager::class)
            ->callAction(
                TestAction::make('sendMessage')
                    ->schemaComponent('conversation_schema.conversation_thread')
            )
            ->assertHasFormErrors();

        expect($conversation->messages)->count(0);
    });

    test('can reply to a message', function () {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $this->actingAs($owner);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($owner, $participant);
        $originalMessageKey = $conversation->participations()->first()->sendMessage($conversation, [
            'content' => 'original message',
        ])->getKey();

        $livewire = livewire(ConversationManager::class)
            ->callAction(
                TestAction::make(ReplyToMessageAction::getDefaultName())
                    ->schemaComponent('conversation_schema.conversation_thread')
                    ->arguments([
                        'recordKey' => $originalMessageKey,
                    ]),
            );

        expect($livewire->instance()->isReplyingToMessage($originalMessageKey))->toBeTrue();

        $livewire
            ->fillForm([
                'conversation_schema.messageContent' => 'Test message',
            ])
            ->callAction(
                TestAction::make('sendMessage')
                    ->schemaComponent('conversation_schema.conversation_thread')
            )
            ->assertHasNoFormErrors();

        expect($livewire->instance()->isReplyingToMessage($originalMessageKey))->toBeFalse();

        /* @var Collection<int, Message> $messages */
        $messages = $conversation->refresh()->messages;

        expect($messages)
            ->toHaveCount(2)
            ->and($messages->firstWhere((new Message)->getKeyName(), '!=', $originalMessageKey))
            ->content->toBe('Test message')
            ->attachments->toBe([])
            ->reply_to_message_id->toBe($originalMessageKey);
    });

    test('can cancel message reply', function () {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $this->actingAs($owner);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($owner, $participant);
        $originalMessageKey = $conversation->participations()->first()->sendMessage($conversation, [
            'content' => 'original message',
        ])->getKey();

        $livewire = livewire(ConversationManager::class)
            ->callAction(
                TestAction::make(ReplyToMessageAction::getDefaultName())
                    ->schemaComponent('conversation_schema.conversation_thread')
                    ->arguments([
                        'recordKey' => $originalMessageKey,
                    ]),
            )
            ->fillForm([
                'conversation_schema.messageContent' => 'Test message',
            ])
            ->callAction(
                TestAction::make(CancelReplyToMessageAction::getDefaultName())
                    ->schemaComponent('conversation_schema.conversation_thread'),
            )
            ->assertSet('data.conversation_schema.attachments', null)
            ->assertSet('data.conversation_schema.messageContent', null);

        expect($livewire->instance())->isReplyingToMessage($originalMessageKey)->toBeFalse();
    });

    test('can cache unsend messages on conversation change', function () {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $this->actingAs($owner);

        /* @var Conversation $firstConversation */
        $firstConversation = app(CreateConversation::class)->handle($owner, $participant);
        /* @var Conversation $secondConversation */
        $secondConversation = app(CreateConversation::class)->handle($owner, $participant);
        /* @var Conversation $thirdConversation */
        $thirdConversation = app(CreateConversation::class)->handle($owner, $participant);

        $firstMessage = 'First conversation draft';
        $secondMessage = 'Second conversation draft';
        $firstFileName = 'first.pdf';
        $secondFileName = 'second.pdf';

        livewire(ConversationManager::class)
            ->call('updateActiveConversation', $firstConversation->getKey())
            ->fillForm([
                'conversation_schema.messageContent' => $firstMessage,
                'conversation_schema.attachments' => [
                    TemporaryUploadedFile::fake()->create(
                        $firstFileName,
                        100,
                        'application/pdf',
                    ),
                ],
            ])
            ->call('updateActiveConversation', $secondConversation->getKey())
            ->fillForm([
                'conversation_schema.messageContent' => $secondMessage,
                'conversation_schema.attachments' => [
                    TemporaryUploadedFile::fake()->create(
                        $secondFileName,
                        100,
                        'application/pdf',
                    ),
                ],
            ])
            ->call('updateActiveConversation', $thirdConversation->getKey())
            ->assertSet('cachedUnsendMessages.data.conversation_schema.' . $firstConversation->getKey() . '.messageContent', $firstMessage)
            ->assertSet(
                'cachedUnsendMessages.data.conversation_schema.' . $firstConversation->getKey() . '.attachments.0',
                static fn (TemporaryUploadedFile $file) => $file->getClientOriginalName() === $firstFileName,
            )
            ->assertSet('cachedUnsendMessages.data.conversation_schema.' . $secondConversation->getKey() . '.messageContent', $secondMessage)
            ->assertSet(
                'cachedUnsendMessages.data.conversation_schema.' . $secondConversation->getKey() . '.attachments.0',
                static fn (TemporaryUploadedFile $file) => $file->getClientOriginalName() === $secondFileName,
            )
            ->call('updateActiveConversation', $firstConversation->getKey())
            ->assertSet('data.conversation_schema.messageContent', $firstMessage)
            ->assertSet(
                'data.conversation_schema.attachments.0',
                static fn (TemporaryUploadedFile $file) => $file->getClientOriginalName() === $firstFileName,
            )
            ->call('updateActiveConversation', $secondConversation->getKey())
            ->assertSet('data.conversation_schema.messageContent', $secondMessage)
            ->assertSet(
                'data.conversation_schema.attachments.0',
                static fn (TemporaryUploadedFile $file) => $file->getClientOriginalName() === $secondFileName,
            );
    });

    test('sending a message resets cached unsend messages', function () {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $this->actingAs($owner);

        /* @var Conversation $firstConversation */
        $firstConversation = app(CreateConversation::class)->handle($owner, $participant);
        /* @var Conversation $secondConversation */
        $secondConversation = app(CreateConversation::class)->handle($owner, $participant);

        $firstMessage = 'First conversation draft';
        $secondMessage = 'Second conversation draft';
        $firstFileName = 'first.pdf';
        $secondFileName = 'second.pdf';

        livewire(ConversationManager::class)
            ->call('updateActiveConversation', $firstConversation->getKey())
            ->fillForm([
                'conversation_schema.messageContent' => $firstMessage,
                'conversation_schema.attachments' => [
                    TemporaryUploadedFile::fake()->create(
                        $firstFileName,
                        100,
                        'application/pdf',
                    ),
                ],
            ])
            ->call('updateActiveConversation', $secondConversation->getKey())
            ->fillForm([
                'conversation_schema.messageContent' => $secondMessage,
                'conversation_schema.attachments' => [
                    TemporaryUploadedFile::fake()->create(
                        $secondFileName,
                        100,
                        'application/pdf',
                    ),
                ],
            ])
            ->call('updateActiveConversation', $firstConversation->getKey())
            ->callAction(
                TestAction::make('sendMessage')
                    ->schemaComponent('conversation_schema.conversation_thread')
            )
            ->assertHasNoFormErrors()
            ->assertSet('cachedUnsendMessages.data.conversation_schema.' . $firstConversation->getKey() . '.messageContent', null)
            ->assertSet('cachedUnsendMessages.data.conversation_schema.' . $firstConversation->getKey() . '.attachments.0', null);

        $messages = $firstConversation->messages;

        expect($messages)
            ->toHaveCount(1)
            ->and($messages->first())
            ->content->toBe($firstMessage)
            ->attachment_file_names->toBe([$firstFileName]);
    });

    test('caching does not happen when replying to message', function () {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $this->actingAs($owner);

        /* @var Conversation $firstConversation */
        $firstConversation = app(CreateConversation::class)->handle($owner, $participant);
        /* @var Conversation $secondConversation */
        $secondConversation = app(CreateConversation::class)->handle($owner, $participant);

        livewire(ConversationManager::class)
            ->call('updateActiveConversation', $firstConversation->getKey())
            ->callAction(
                TestAction::make(ReplyToMessageAction::getDefaultName())
                    ->schemaComponent('conversation_schema.conversation_thread')
                    ->arguments([
                        'recordKey' => $firstConversation->participations()->first()->sendMessage($firstConversation, [
                            'content' => 'Message'
                        ])->getKey(),
                    ]),
            )
            ->fillForm([
                'conversation_schema.messageContent' => 'Reply',
            ])
            ->call('updateActiveConversation', $secondConversation->getKey())
            ->assertSet('cachedUnsendMessages.data.conversation_schema.' . $firstConversation->getKey() . '.messageContent', null)
            ->assertSet('cachedUnsendMessages.data.conversation_schema.' . $firstConversation->getKey() . '.attachments.0', null);
    });

    test('unsend messages are not cached when they should not', function () {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $this->actingAs($owner);

        /* @var Conversation $firstConversation */
        $firstConversation = app(CreateConversation::class)->handle($owner, $participant);
        /* @var Conversation $secondConversation */
        $secondConversation = app(CreateConversation::class)->handle($owner, $participant);

        livewire(ConversationManager::class, [
            'conversationSchemaConfiguration' => UnsendMessagesCachingConfiguration::class,
        ])
            ->call('updateActiveConversation', $firstConversation->getKey())
            ->fillForm([
                'conversation_schema.messageContent' => 'Test message',
            ])
            ->call('updateActiveConversation', $secondConversation->getKey())
            ->assertSet('cachedUnsendMessages.data.conversation_schema.' . $firstConversation->getKey() . '.messageContent', null)
            ->assertSet('cachedUnsendMessages.data.conversation_schema.' . $firstConversation->getKey() . '.attachments.0', null)
            ->assertSet('data.conversation_schema.messageContent', null)
            ->assertSet('data.conversation_schema.attachments', null);
    });
});

class UnsendMessagesCachingConfiguration
{
    public static function configure(ConversationSchema $schema): ConversationSchema
    {
        return $schema
            ->conversationThread(static fn (ConversationThread $conversationThread) => $conversationThread
                ->cacheUnsendMessagesOnConversationChange(false)
            );
    }
}
