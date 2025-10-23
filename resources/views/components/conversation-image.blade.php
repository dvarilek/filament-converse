@props([
    'conversation',
    'conversationName',
    'conversationImage' => null,
])

@php
    use Illuminate\Support\Collection;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
@endphp

@if ($conversationImage)
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
        $isGroupConversation = $conversation->isGroup();
        /* @var Collection<int, Message> $latestMessages */
        $latestMessages = $conversation->participations->flatMap->messages->sortByDesc('created_at');
    @endphp

    <div
        @class([
            'fi-converse-conversation-list-item-multiple-avatars' => $isGroupConversation,
            'fi-converse-conversation-list-item-image-wrapper',
        ])
    >
        @if ($isGroupConversation)
            @php
                // todo: This is shit


                if ($latestMessages->isEmpty() || (! $latestMessage = $latestMessages->first()) || (! $penultimateMessage = $latestMessage->get(1))) {
                    $participations = $conversation->participations->where((new ConversationParticipation)->getKeyName(), '!=', auth()->id());

                    $participantWithLatestMessage = $participations->first()->participant;
                    $participantWithPenultimateMessage = $participations->get(1)?->participant ?? null;
                } else {
                    $participantWithLatestMessage = $latestMessage->author->participant;
                    $participantWithPenultimateMessage = $penultimateMessage->author->participant;
                }
            @endphp

            @if ($participantWithLatestMessage && $participantWithPenultimateMessage)
                <x-filament::avatar
                    class="fi-converse-conversation-list-item-image fi-converse-conversation-list-item-penultimate-avatar"
                    :src="filament()->getUserAvatarUrl($participantWithPenultimateMessage)"
                    :alt="$participantWithPenultimateMessage->getAttributeValue($participantWithPenultimateMessage::getFilamentNameAttribute())"
                    size="md"
                />
                <x-filament::avatar
                    color="primary"
                    class="fi-converse-conversation-list-item-image fi-converse-conversation-list-item-last-avatar"
                    :src="filament()->getUserAvatarUrl($participantWithLatestMessage)"
                    :alt="$participantWithLatestMessage->getAttributeValue($participantWithLatestMessage::getFilamentNameAttribute())"
                    size="md"
                />
            @else
                <x-filament::avatar
                    class="fi-converse-conversation-list-item-image"
                    :src="filament()->getUserAvatarUrl($participantWithLatestMessage)"
                    :alt="$conversationName"
                    size="lg"
                />
            @endif
        @else
            <x-filament::avatar
                class="fi-converse-conversation-list-item-image"
                :src="filament()->getUserAvatarUrl($latestMessages->first()->author->participant)"
                :alt="$conversationName"
                size="lg"
            />
        @endif
    </div>
@endif
