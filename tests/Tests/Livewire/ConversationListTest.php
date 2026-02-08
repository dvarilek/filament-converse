<?php

declare(strict_types=1);

use Carbon\Carbon;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\CreateConversationAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

describe('render', function () {
    it('can render conversations', function () {
        $owner = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $thirdUser = User::factory()->create();

        $this->actingAs($owner);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($owner, $firstUser);
        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($owner, $secondUser);

        /* @var Conversation $firstGroupConversation */
        $firstGroupConversation = app(CreateConversation::class)->handle($owner, collect([$firstUser, $secondUser]));
        /* @var Conversation $thirdUserConversation */
        $secondGroupConversation = app(CreateConversation::class)->handle($owner, collect([$firstUser, $secondUser, $thirdUser]));

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
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        app(CreateConversation::class)->handle($owner, $otherUser);

        livewire(ConversationManager::class)
            ->assertSeeText($otherUser->name);
    });

    it('can render conversation latest messages', function () {
        Carbon::setTestNow();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($owner, $otherUser);

        livewire(ConversationManager::class)
            ->assertSeeText(__('filament-converse::conversation-list.latest-message.empty-state'));

        $firstMessage = app(SendMessage::class)->handle($conversation->owner, $conversation, [
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
        $owner = User::factory()->state(['name' => 'owner'])->create();
        $firstUser = User::factory()->state(['name' => 'first'])->create();
        $secondUser = User::factory()->state(['name' => 'second'])->create();

        $this->actingAs($owner);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($owner, $firstUser);
        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($owner, $secondUser);
        /* @var Conversation $groupConversation */
        $groupConversation = app(CreateConversation::class)->handle($owner, collect([$firstUser, $secondUser]));

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
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($owner, $otherUser, [
            'name' => 'Group A',
            'description' => 'First group',
        ]);

        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($owner, $otherUser, [
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
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

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
            ->participations->pluck('participant_id')->toContain($owner->getKey(), $otherUser->getKey())
            ->owner->participant->getKey()->toBe($owner->getKey())
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
        $owner = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($owner);

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
            ->participations->pluck('participant_id')->toContain($owner->getKey(), $firstUser->getKey(), $secondUser->getKey())
            ->owner->participant->getKey()->toBe($owner->getKey())
            ->and($livewire->instance())
            ->conversations->toHaveCount(1)
            ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('can create a new group conversation through action with additional data', function () {
        $owner = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($owner);

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
            ->participations->pluck('participant_id')->toContain($owner->getKey(), $firstUser->getKey(), $secondUser->getKey())
            ->owner->participant->getKey()->toBe($owner->getKey())
            ->and($livewire->instance())
            ->conversations->toHaveCount(1)
            ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('cannot create a duplicate direct conversation with the same participant', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);
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
            ->assertHasFormErrors(['participants' => __('filament-converse::actions.schema.participants.validation.direct-conversation-exists')]);

        expect(Conversation::query()->count())->toBe(1);
    });

    it('can create multiple group conversations with the same participants', function () {
        $owner = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($owner);
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
