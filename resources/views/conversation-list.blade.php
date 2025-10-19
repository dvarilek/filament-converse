@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
    use Dvarilek\FilamentConverse\Schemas\Components\ConversationList;
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Support\Collection;
    use Illuminate\View\ComponentAttributeBag;

    // TODO: Heading count
    $headingBadgeLabel = $this->conversations->count();
    $headingBadgeColor = 'primary';

    $shouldShowConversationImage = $shouldShowConversationImage();
    $hasConversationImageClosure = $hasConversationImageClosure();
    $hasIsConversationUnreadClosure = $hasIsConversationUnreadClosure();

    /* @var Collection<int, Conversation> $conversations */
    $conversations = $getConversations();
    $activeConversation = $getActiveConversation();

    $headerActions = array_filter(
        $getChildComponents(ConversationList::HEADER_ACTIONS_KEY),
        fn (Action | ActionGroup $action) => $action->isVisible()
    );
@endphp

<div class="fi-converse-conversation-list">
    <div class="fi-converse-conversation-list-header">
        <div class="fi-converse-conversation-list-header-top">
            <div class="fi-converse-conversation-list-header-content">
                <div class="fi-converse-conversation-list-header-title">
                    <h2 class="fi-converse-conversation-list-header-heading">
                        {{ $getHeading() }}
                    </h2>
                    @if (filled($headingBadgeLabel))
                        <x-filament::badge :color="$headingBadgeColor">
                            {{ $headingBadgeLabel }}
                        </x-filament::badge>
                    @endif
                </div>
                @if (filled($description = $getDescription()))
                    <p class="fi-converse-conversation-list-header-description">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @if (count($headerActions))
                <div class="fi-converse-conversation-list-header-actions">
                    @foreach ($headerActions as $action)
                        {{ $action }}
                    @endforeach
                </div>
            @endif
        </div>

        <div class="fi-converse-conversation-list-header-bottom">
            @if ($isSearchable())
                @php
                    $searchPlaceholder = $getSearchPlaceholder();
                    $searchDebounce = $getSearchDebounce();
                    $searchOnBlur = $isSearchOnBlur();

                    $wireModelAttribute = $searchOnBlur ? 'wire:model.blur' : "wire:model.live.debounce.{$searchDebounce}";
                @endphp

                <div
                    x-id="['input']"
                    class="fi-converse-conversation-list-search-field"
                >
                    <label x-bind:for="$id('input')" class="fi-sr-only">
                        {{ __('filament-converse::conversation-list.search.label') }}
                    </label>

                    <x-filament::input.wrapper
                        inline-prefix
                        :prefix-icon="Heroicon::MagnifyingGlass"
                        wire:target="conversationListSearch"
                    >
                        <x-filament::input
                            :attributes="
                                (new ComponentAttributeBag)->merge([
                                    'autocomplete' => 'off',
                                    'inlinePrefix' => true,
                                    'maxlength' => 1000,
                                    'placeholder' => $searchPlaceholder,
                                    'type' => 'search',
                                    'wire:key' => $this->getId() . '.conversationListSearch.field.input',
                                    $wireModelAttribute => 'conversationListSearch',
                                    'x-bind:id' => '$id(\'input\')',
                                    'x-on:keyup' => 'if ($event.key === \'Enter\') { $wire.$refresh() }',
                                ], escape: false)
                            "
                        />
                    </x-filament::input.wrapper>
                </div>
            @endif

            <div class="fi-converse-conversation-list-header-bottom-actions">
                <x-filament::badge>Action1</x-filament::badge>
                <x-filament::badge>Action2</x-filament::badge>
                <x-filament::badge>Action3</x-filament::badge>
            </div>
        </div>
    </div>

    <ul
        @class([
            'fi-converse-conversation-list-overflow' => $shouldConversationListOverflow(),
            'fi-converse-conversation-area',
        ])
    >
        @forelse ($conversations as $conversation)
            <li
                wire:click="updateActiveConversation('{{ $conversationKey = $conversation->getKey() }}')"
                @class([
                    'fi-converse-conversation-list-item-active' => $conversationKey === $activeConversation?->getKey(),
                    'fi-converse-conversation-list-item-unread' => $hasIsConversationUnreadClosure && $isConversationUnread($conversation),
                    'fi-converse-conversation-list-item',
                ])
            >
                @php
                    $conversationName = $getConversationName($conversation);
                @endphp

                @if ($shouldShowConversationImage)
                    @if ($hasConversationImageClosure && filled($conversationImage = $getConversationImage($conversation)))
                        <div
                            class="fi-converse-conversation-list-item-image-wrapper"
                        >
                            <x-filament::avatar
                                class="fi-converse-conversation-list-item-image"
                                :src="$conversationImage"
                                :alt="$conversationName"
                                size="lg"
                            />
                        </div>
                    @else
                        @php
                            $otherParticipations = $conversation->otherParticipations;
                            $hasMultipleAvatarsInConversationImage = $conversation->isGroup() || $otherParticipations->count() >= 2;
                        @endphp

                        <div
                            @class([
                                'fi-converse-conversation-list-item-multiple-avatars' => $hasMultipleAvatarsInConversationImage,
                                'fi-converse-conversation-list-item-image-wrapper',
                            ])
                        >
                            @if ($hasMultipleAvatarsInConversationImage)
                                @php
                                    // TODO: Actually take two latest by messages or two first if no messages
                                    $lastTwoParticipations = $otherParticipations->slice(-2)->values();
                                    $latestParticipant = $lastTwoParticipations->last()->participant;
                                    $penultimateParticipant = $lastTwoParticipations->first()->participant;
                                @endphp

                                <x-filament::avatar
                                    class="fi-converse-conversation-list-item-image fi-converse-conversation-list-item-penultimate-avatar"
                                    :src="filament()->getUserAvatarUrl($penultimateParticipant)"
                                    :alt="$penultimateParticipant->getAttribute($penultimateParticipant::getFilamentNameAttribute())"
                                    size="md"
                                />
                                <x-filament::avatar
                                    color="primary"
                                    class="fi-converse-conversation-list-item-image fi-converse-conversation-list-item-last-avatar"
                                    :src="filament()->getUserAvatarUrl($latestParticipant)"
                                    :alt="$latestParticipant->getAttribute($latestParticipant::getFilamentNameAttribute())"
                                    size="md"
                                />
                            @else
                                <x-filament::avatar
                                    class="fi-converse-conversation-list-item-image"
                                    :src="filament()->getUserAvatarUrl($otherParticipations->first()->participant)"
                                    :alt="$conversationName"
                                    size="lg"
                                />
                            @endif
                        </div>
                    @endif
                @endif

                <div class="fi-converse-conversation-list-item-content">
                    <div class="fi-converse-conversation-list-item-title">
                        <h4 class="fi-converse-conversation-list-item-heading">
                            {{ $conversationName }}
                        </h4>
                        <p
                            class="fi-converse-conversation-list-item-time-indicator"
                        >
                            TODO
                        </p>
                    </div>
                    <p
                        class="fi-converse-conversation-list-item-last-message-description"
                    >
                        TODO: Do this and other stuff in there
                    </p>
                </div>
            </li>
        @empty
            @if ($emptyState = $getEmptyState())
                {{ $emptyState }}
            @else
                <x-filament::empty-state
                    :icon="$getEmptyStateIcon()"
                    :icon-color="$getEmptyStateIconColor()"
                >
                    <x-slot name="heading">
                        {{ $getEmptyStateHeading() }}
                    </x-slot>
                    <x-slot name="description">
                        {{ $getEmptyStateDescription() }}
                    </x-slot>
                </x-filament::empty-state>
            @endif
        @endforelse
    </ul>
</div>
