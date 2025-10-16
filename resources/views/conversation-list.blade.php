@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Support\Collection;
    use Illuminate\View\ComponentAttributeBag;

    $heading = $getHeading();
    $description = $getDescription();
    $shouldConversationListOverflow = $shouldConversationListOverflow();

    // TODO: Heading count
    $headingBadgeLabel = $this->conversations->count();
    $headingBadgeColor = 'primary';

    $hasSearch = $isSearchable();
    $searchPlaceholder = $getSearchPlaceholder();
    $searchDebounce = $getSearchDebounce();
    $searchOnBlur = $isSearchOnBlur();

    $emptyState = $getEmptyState();
    $emptyStateHeading = $getEmptyStateHeading();
    $emptyStateDescription = $getEmptyStateDescription();
    $emptyStateIcon = $getEmptyStateIcon();
    $emptyStateIconColor = $getEmptyStateIconColor();

    $shouldShowConversationImage = $shouldShowConversationImage();
    $hasConversationImageClosure = $hasConversationImageClosure();
    $hasIsConversationUnreadClosure = $hasIsConversationUnreadClosure();

    // TODO; use getUserName internally instead of introducing a new method in Conversable trait // FilamentManager class

    /* @var Collection<int, Conversation> $conversations */
    $conversations = $getConversations();
    $activeConversation = $getActiveConversation();
@endphp

<div class="fi-converse-conversation-list">
    <div class="fi-converse-conversation-list-header">
        <div class="fi-converse-conversation-list-header-top">
            <div class="fi-converse-conversation-list-header-content">
                <div class="fi-converse-conversation-list-header-title">
                    <h2 class="fi-converse-conversation-list-header-heading">
                        {{ $heading }}
                    </h2>
                    @if (filled($headingBadgeLabel))
                        <x-filament::badge :color="$headingBadgeColor">
                            {{ $headingBadgeLabel }}
                        </x-filament::badge>
                    @endif
                </div>
                @if (filled($description))
                    <p class="fi-converse-conversation-list-header-description">
                        {{ $description }}
                    </p>
                @endif
            </div>

            TODO: Action
        </div>

        <div class="fi-converse-conversation-list-header-bottom">
            @if ($hasSearch)
                @php
                    $wireModelAttribute = $searchOnBlur ? 'wire:model.blur' : "wire:model.live.debounce.{$searchDebounce}";
                @endphp

                <div
                    x-id="['input']"
                    class="fi-converse-conversation-list-search-field"
                >
                    <label x-bind:for="$id('input')" class="fi-sr-only">
                        {{ __('filament-tables::table.fields.search.label') }}
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

    <ul class="fi-converse-conversation-area">
        @forelse ($conversations as $conversation)
            <li
                @class([
                    'fi-converse-conversation-list-item-active' => $conversation->getKey() === $activeConversation?->getKey(),
                    'fi-converse-conversation-list-item-unread' => $hasIsConversationUnreadClosure && $isConversationUnread($conversation),
                    'fi-converse-conversation-list-overflow' => $shouldConversationListOverflow,
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
                            /* @var Collection<int, ConversationParticipation> $otherParticipations */
                            $otherParticipations = $conversation->otherParticipations()->get();
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
                                    $latestParticipant = $lastTwoParticipations->last();
                                    $penultimateParticipant = $lastTwoParticipations->first();
                                @endphp

                                <x-filament::avatar
                                    class="fi-converse-conversation-list-item-image fi-converse-conversation-list-item-penultimate-avatar"
                                    :src="$penultimateParticipant->participant_avatar_source ?? filament()->getUserAvatarUrl($penultimateParticipant->participant)"
                                    :alt="$penultimateParticipant->participant_name"
                                    size="md"
                                />
                                <x-filament::avatar
                                    color="primary"
                                    class="fi-converse-conversation-list-item-image fi-converse-conversation-list-item-last-avatar"
                                    :src="$latestParticipant->participant_avatar_source ?? filament()->getUserAvatarUrl($latestParticipant->participant)"
                                    :alt="$latestParticipant->participant_name"
                                    size="md"
                                />
                            @else
                                @php
                                    $otherParticipant = $otherParticipations->first();
                                @endphp

                                <x-filament::avatar
                                    class="fi-converse-conversation-list-item-image"
                                    :src="$otherParticipant->participant_avatar_source ?? filament()->getUserAvatarUrl($otherParticipant->participant)"
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
                        <div
                            class="fi-converse-conversation-list-item-indicator"
                        >
                            <p
                                class="fi-converse-conversation-list-item-last-message-time-indicator"
                            >
                                Time
                            </p>
                            <x-filament::badge>+2</x-filament::badge>
                        </div>
                    </div>
                    <p
                        class="fi-converse-conversation-list-item-last-message-description"
                    >
                        TODO: Do this and other stuff in there
                    </p>
                </div>
            </li>
        @empty
            @if ($customEmptyState)
                {{ $customEmptyState }}
            @else
                <x-filament::empty-state
                    :icon="$emptyStateIcon"
                    :icon-color="$emptyStateIconColor"
                >
                    <x-slot name="heading">
                        {{ $emptyStateHeading }}
                    </x-slot>
                    <x-slot name="description">
                        {{ $emptyStateDescription }}
                    </x-slot>
                </x-filament::empty-state>
            @endif
        @endforelse
    </ul>
</div>
