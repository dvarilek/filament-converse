<?php

declare(strict_types=1);

use Carbon\Carbon;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationList\CreateConversationAction;

describe('render', function () {
    it('can render conversations', function () {
        $creator = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $thirdUser = User::factory()->create();

        $this->actingAs($creator);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($creator, $firstUser);
        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($creator, $secondUser);

        /* @var Conversation $firstGroupConversation */
        $firstGroupConversation = app(CreateConversation::class)->handle($creator, collect([$firstUser, $secondUser]));
        /* @var Conversation $thirdUserConversation */
        $secondGroupConversation = app(CreateConversation::class)->handle($creator, collect([$firstUser, $secondUser, $thirdUser]));

        $livewire = livewire(ConversationManager::class);

        expect($livewire->instance()->conversations)
            ->toHaveCount(4)
            ->pluck((new Conversation)->getKeyName())
            ->toContain($firstUserConversation->getKey(), $secondUserConversation->getKey(), $firstGroupConversation->getKey(), $secondGroupConversation->getKey())
            ->and($livewire)
            ->assertSeeText($firstUser->name)
            ->assertSeeText($secondUser->name)
            ->assertSeeText($firstUser->name . " & " . $secondUser->name)
            ->assertSeeText($firstUser->name . ', ' . $secondUser->name . ' & ' . $thirdUser->name);
    });

    it('can render conversation name', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($creator, $otherUser);

        livewire(ConversationManager::class)
            ->assertSeeText($otherUser->name);
    });

    it('can render conversation latest messages', function () {
        Carbon::setTestNow();

        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($creator, $otherUser);

        livewire(ConversationManager::class)
            ->assertSeeText(__('filament-converse::conversation-list.latest-message.empty-state'));

        $firstMessage = app(SendMessage::class)->handle($conversation->creator, $conversation, [
            'content' => 'Test message',
        ]);

        livewire(ConversationManager::class)
            ->assertSeeText(__('filament-converse::conversation-list.latest-message.current-user') . ': ' . $firstMessage->content);

        Carbon::setTestNow(now()->addMinutes(5));

        /* @var ConversationParticipation $otherParticipant */
        $otherParticipant = $conversation->otherParticipations()->first();

        $secondMessage = app(SendMessage::class)->handle($otherParticipant, $conversation, [
            'content' => 'Second test message',
        ]);

        livewire(ConversationManager::class)
            ->assertSeeText($otherUser->getAttributeValue($otherUser::getFilamentNameAttribute()) . ': ' . $secondMessage->content);
    });
});

describe('search', function () {
    it('can search conversations by participants', function () {
        $creator = User::factory()->state(['name' => 'creator'])->create();
        $firstUser = User::factory()->state(['name' => 'first'])->create();
        $secondUser = User::factory()->state(['name' => 'second'])->create();

        $this->actingAs($creator);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($creator, $firstUser);
        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($creator, $secondUser);
        /* @var Conversation $groupConversation */
        $groupConversation = app(CreateConversation::class)->handle($creator, collect([$firstUser, $secondUser]));

        $primaryKey = (new Conversation)->getKeyName();
        $livewire = livewire(ConversationManager::class);

        $livewire->set('conversationListSearch', $firstUser->name);

        expect($livewire->instance()->conversations)
            ->toHaveCount(2)
            ->pluck($primaryKey)
            ->toContain($firstUserConversation->getKey(), $groupConversation->getKey())
            ->not->toContain($secondUserConversation->getKey());

        $livewire->set('conversationListSearch', $secondUser->name);

        expect($livewire->instance()->conversations)
            ->toHaveCount(2)
            ->pluck($primaryKey)
            ->toContain($secondUserConversation->getKey(), $groupConversation->getKey())
            ->not->toContain($firstUserConversation->getKey());

        $livewire->set('conversationListSearch', '');

        expect($livewire->instance()->conversations)
            ->toHaveCount(3)
            ->pluck($primaryKey)
            ->toContain($firstUserConversation->getKey(), $secondUserConversation->getKey(), $groupConversation->getKey());
    });

    it('can search conversations by name or description', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'name' => 'Group A',
            'description' => 'First group',
        ]);

        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'name' => 'Group B',
            'description' => 'Second group',
        ]);

        $primaryKey = (new Conversation)->getKeyName();
        $livewire = livewire(ConversationManager::class);

        $livewire->set('conversationListSearch', 'Group A');

        expect($livewire->instance()->conversations)
            ->toHaveCount(1)
            ->pluck($primaryKey)
            ->toContain($firstUserConversation->getKey())
            ->not->toContain($secondUserConversation->getKey());

        $livewire->set('conversationListSearch', 'Second group');

        expect($livewire->instance()->conversations)
            ->toHaveCount(1)
            ->pluck($primaryKey)
            ->toContain($secondUserConversation->getKey())
            ->not->toContain($firstUserConversation->getKey());
    });
});

describe('actions', function () {
    it('can create a new direct conversation through action', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        $livewire = livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'), [
                'participants' => [
                    $otherUser->getKey()
                ],
            ])
            ->assertHasNoErrors();

        /* @var Conversation $conversation */
        $conversation = Conversation::query()->first();

        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->name->toBeNull()
            ->description->toBeNull()
            ->image->toBeNull()
            ->participations->toHaveCount(2)
            ->participations->pluck('participant_id')->toContain($creator->getKey(), $otherUser->getKey())
            ->creator->participant->getKey()->toBe($creator->getKey())
            ->and($livewire->instance())
            ->conversations->toHaveCount(1)
            ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('requires a selected participant to create a new direct conversation', function () {
        $this->actingAs(User::factory()->create());

        livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'))
            ->assertHasFormErrors(['participants' => 'required']);
    });

    it('can create a new group conversation through action', function () {
        $creator = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($creator);

        $livewire = livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'), [
                'participants' => [$firstUser->getKey(), $secondUser->getKey()],
            ])
            ->assertHasNoErrors();

        /* @var Conversation $conversation */
        $conversation = Conversation::query()->first();

        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->name->toBeNull()
            ->description->toBeNull()
            ->image->toBeNull()
            ->participations->toHaveCount(3)
            ->participations->pluck('participant_id')->toContain($creator->getKey(), $firstUser->getKey(), $secondUser->getKey())
            ->creator->participant->getKey()->toBe($creator->getKey())
            ->and($livewire->instance())
            ->conversations->toHaveCount(1)
            ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('can create a new group conversation through action with additional data', function () {
        $creator = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($creator);

        $livewire = livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'), [
                'participants' => [$firstUser->getKey(), $secondUser->getKey()],
                'name' => 'Test conversation',
                'description' => 'Test description',
            ])
            ->assertHasNoErrors();

        /* @var Conversation $conversation */
        $conversation = Conversation::query()->first();

        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->name->toBe('Test conversation')
            ->description->toBe('Test description')
            ->image->toBeNull()
            ->participations->toHaveCount(3)
            ->participations->pluck('participant_id')->toContain($creator->getKey(), $firstUser->getKey(), $secondUser->getKey())
            ->creator->participant->getKey()->toBe($creator->getKey())
            ->and($livewire->instance())
            ->conversations->toHaveCount(1)
            ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('cannot create a duplicate direct conversation with the same participant', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);
        $action = TestAction::make(CreateConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list');

        $livewire = livewire(ConversationManager::class)
            ->callAction($action, [
                'participants' => [
                    $otherUser->getKey()
                ],
            ])
            ->assertHasNoErrors();

        expect(Conversation::query()->count())->toBe(1);

        $livewire
            ->callAction($action, [
                'participants' => [
                    $otherUser->getKey()
                ],
            ])
            ->assertHasFormErrors(['participants' => __('filament-converse::conversation-list.actions.create-conversation.schema.participant.validation.direct-conversation-exists')]);

        expect(Conversation::query()->count())->toBe(1);
    });

    it('can create multiple group conversations with the same participants', function () {
        $creator = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($creator);
        $action = TestAction::make(CreateConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list');

        $livewire = livewire(ConversationManager::class)
            ->callAction($action, [
                'participants' => [$firstUser->getKey(), $secondUser->getKey()],
                'name' => 'First group',
            ])
            ->assertHasNoErrors();

        expect(Conversation::query()->count())->toBe(1);

        $livewire
            ->callAction($action, [
                'participants' => [$firstUser->getKey(), $secondUser->getKey()],
                'name' => 'Second group',
            ])
            ->assertHasNoErrors();

        expect(Conversation::query()->count())->toBe(2);

        $livewire
            ->callAction($action, [
                'participants' => [$secondUser->getKey()],
                'name' => 'Third group',
            ])
            ->assertHasNoErrors();

        expect(Conversation::query()->count())->toBe(3);
    });
});

describe('filters', function () {});
