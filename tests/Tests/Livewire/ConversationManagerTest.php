<?php

declare(strict_types=1);

use Carbon\Carbon;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationList;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationSchema;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
use Dvarilek\FilamentConverse\Tests\Models\User;

use function Pest\Livewire\livewire;

describe('active conversation', function () {
    test('search does not affect the active conversation', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        $conversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::DIRECT,
        ]);

        $livewire = livewire(ConversationManager::class)
            ->set('conversationListSearch', 'This should not affect the active conversation');

        expect($livewire->instance())
            ->conversations->toHaveCount(0)
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('can configure conversation schema contents', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::DIRECT,
        ]);

        livewire(ConversationManager::class, [
            'conversationSchemaConfiguration' => TestConfiguration::class,
        ])
            ->assertSeeText('Conversation list custom heading')
            ->assertSeeText('Conversation thread custom empty state');
    });

    it('can persist active conversation in session', function () {
        Carbon::setTestNow();

        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        $penultimateConversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::DIRECT,
        ]);

        Carbon::setTestNow(now()->addMinutes(5));

        $latestConversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::GROUP,
        ]);

        livewire(ConversationManager::class)
            ->assertSet('activeConversationKey', $latestConversation->getKey())
            ->call('updateActiveConversation', $penultimateConversation->getKey());

        livewire(ConversationManager::class)
            ->assertSet('activeConversationKey', $latestConversation->getKey());

        $firstLivewireWithConfiguration = livewire(ConversationManager::class, [
            'conversationSchemaConfiguration' => SessionConfiguration::class,
        ])
            ->assertSet('activeConversationKey', $latestConversation->getKey())
            ->call('updateActiveConversation', $penultimateConversation->getKey());

        $secondLivewireWithConfiguration = livewire(ConversationManager::class, [
            'conversationSchemaConfiguration' => SessionConfiguration::class,
        ])
            ->assertSet('activeConversationKey', $penultimateConversation->getKey());

        expect($firstLivewireWithConfiguration->instance()->getActiveConversationSessionKey())
            ->toBe($secondLivewireWithConfiguration->instance()->getActiveConversationSessionKey());
    });

    it('can retrieve authenticated user participation belonging to an active conversation', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        $conversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::DIRECT,
        ]);

        /* @var ConversationManager $livewire */
        $livewire = livewire(ConversationManager::class)->instance();

        expect($livewire->getActiveConversationAuthenticatedUserParticipation())
            ->toBeInstanceOf(ConversationParticipation::class)
            ->getKey()->toBeIn($conversation->participations->pluck((new ConversationParticipation)->getKeyName()))
            ->participant_id->toBe($creator->getKey());
    });
});

class TestConfiguration
{
    public static function configure(ConversationSchema $schema): ConversationSchema
    {
        return $schema
            ->conversationList(fn (ConversationList $conversationList) => $conversationList->heading('Conversation list custom heading'))
            ->conversationThread(fn (ConversationThread $conversationThread) => $conversationThread->emptyStateHeading('Conversation thread custom empty state'));
    }
}

class SessionConfiguration
{
    public static function configure(ConversationSchema $schema): ConversationSchema
    {
        return $schema->persistActiveConversationInSession();
    }
}
