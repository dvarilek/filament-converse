@php
    use Dvarilek\FilamentConverse\Models\Conversation;

    // TODO:
    $searchDebounce = '500ms';
    $searchOnBlur = true;
    $searchPlaceholder = '';

    $activeConversationKey = '';
    $conversationModelPrimaryKey = (new Conversation)->getKeyName();

    // TODO: Blue left border on unread + highlight, handle trailing, elipsis and min-h
    // TODO: Avatar, if more make it +1 stacked
@endphp

<div class="fi-converse-conversation-list">
    <div class="fi-converse-conversation-list-header">
        <div class="fi-converse-conversation-list-header-top">
            <div class="fi-converse-conversation-list-header-content">
                <div class="fi-converse-conversation-list-header-title">
                    <h2 class="fi-converse-conversation-list-header-heading">
                        Conversations
                    </h2>
                    <x-filament::badge>
                        12
                    </x-filament::badge>
                </div>
                <p class="fi-converse-conversation-list-header-description">
                    Some description
                </p>
            </div>

            <div>
                TODO: Add actions
            </div>
        </div>

        <div class="fi-converse-conversation-list-header-bottom">
            <x-filament-tables::search-field
                :debounce="$searchDebounce"
                :on-blur="$searchOnBlur"
                :placeholder="$searchPlaceholder"
            />
            <div class="fi-converse-conversation-list-header-bottom-actions">
                <x-filament::badge>
                    Action1
                </x-filament::badge>
                <x-filament::badge>
                    Action2
                </x-filament::badge>
                <x-filament::badge>
                    Action3
                </x-filament::badge>
            </div>
        </div>
    </div>

    <ul class="fi-converse-conversation-area">
        @foreach($this->conversations as $conversation)
            <li
                @class([
                    'fi-active' => $conversation[$conversationModelPrimaryKey] !== $activeConversationKey,
                    'fi-unread' => false,
                    'fi-converse-conversation-list-item'
                ])
            >
                <div class="tempavatar">

                </div>

                <div class="fi-ta-converse-conversation-list-item-content">
                    <div class="fi-ta-converse-conversation-list-item-title">
                        <h4 class="fi-ta-converse-conversation-list-item-heading">
                            {{ $conversation->name ?? "Conversation name" }}
                        </h4>
                        <p class="fi-ta-converse-conversation-list-item-last-message-time-indicator">
                            Time
                        </p>
                    </div>
                    <p class="fi-ta-converse-conversation-list-item-last-message-description">
                        Someone: Long message that is very very very very very long long long
                    </p>
                </div>
            </li>
        @endforeach
    </ul>
</div>
