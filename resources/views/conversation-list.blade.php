@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Contracts\Auth\Authenticatable;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\View\ComponentAttributeBag;

    $heading = 'Conversation';
    $description = 'Some description';
    $headingBadgeLabel = $this->conversations->count();
    $headingBadgeColor = 'primary';

    $hasSearch = true;
    $searchDebounce = '500ms';
    $searchOnBlur = true;
    $searchPlaceholder = '';

    $isConversationUnread = fn (Conversation $conversation) => false;
    $shouldOverflowConversationList = false;

    $getConversationParticipantNameUsing = fn (Authenticatable & Model $user) => $user->name;

    $hasAvatar = true;

    $activeConversationKey = '';
    $conversationModelPrimaryKey = (new Conversation)->getKeyName();
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

            {{ $this->createConversation }}
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
                                    'wire:key' => $this->getId() . '.table.conversationListSearch.field.input',
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
        @forelse ($this->conversations as $conversation)
            <li
                @class([
                    'fi-converse-conversation-list-item-active' => $conversation[$conversationModelPrimaryKey] === $activeConversationKey,
                    'fi-converse-conversation-list-item-unread' => $isConversationUnread($conversation),
                    'fi-converse-conversation-list-overflow' => $shouldOverflowConversationList,
                    'fi-converse-conversation-list-item',
                ])
            >
                @php
                    // TODO; getUserName // FilamentManager class
                    /* @var Conversation $conversation */
                    $conversationName = $conversation->getName();

                    $otherParticipations = $conversation->otherParticipations()->get();
                @endphp

                @if ($hasAvatar)
                    @if ($conversation->isDirect())
                        @php
                            $otherParticipant = $otherParticipations->first();
                        @endphp

                        <x-filament::avatar
                            class="fi-ta-converse-conversation-list-item-avatar"
                            :src="$otherParticipant->value('participant_avatar_source') ?: filament()->getUserAvatarUrl($otherParticipant->participant)"
                            :alt="$otherParticipant->value('participant_name')"
                            size="lg"
                        />
                    @else
                    @endif
                @endif

                <div class="fi-ta-converse-conversation-list-item-content">
                    <div class="fi-ta-converse-conversation-list-item-title">
                        <h4
                            class="fi-ta-converse-conversation-list-item-heading"
                        >
                            {{ $conversationName }}
                        </h4>
                        <div
                            class="fi-ta-converse-conversation-list-item-indicator"
                        >
                            <p
                                class="fi-ta-converse-conversation-list-item-last-message-time-indicator"
                            >
                                Time
                            </p>
                            <x-filament::badge>+2</x-filament::badge>
                        </div>
                    </div>
                    <p
                        class="fi-ta-converse-conversation-list-item-last-message-description"
                    >
                        Someone: Long message that is very very very very very
                        long long long
                    </p>
                </div>
            </li>
        @empty
            <div class="fi-converse-conversation-area-empty"></div>
        @endforelse
    </ul>
</div>
