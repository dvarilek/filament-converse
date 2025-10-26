@props([
    'conversation',
    'conversationName',
    'conversationImage' => null,
])

@php
    use Illuminate\Support\Collection;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
    use Dvarilek\FilamentConverse\Models\Conversation;

    /* @var Conversation $conversation*/
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
        $hasMultipleAvatarsInConversationImage = $conversation->isGroup() && ! $conversation->participations->count() <= 2;
        $otherConversationParticipations = $conversation->participations->where('participant_id', '!=', auth()->id());
    @endphp

    <div
        @class([
            'fi-converse-conversation-list-item-multiple-avatars' => $hasMultipleAvatarsInConversationImage,
            'fi-converse-conversation-list-item-image-wrapper',
        ])
    >
        @if ($hasMultipleAvatarsInConversationImage && false)
            @php
                /* @var Collection<int, Message> $latestMessages */
                $latestMessages = $conversationParticipations
                    ->pluck('latestMessage')
                    ->filter()
                    ->sortByDesc('created_at');

                $latestMessage = $latestMessages->first();
                $primaryKey = (new ConversationParticipation)->getKeyName();

                $participantWithLatestMessage = $conversation
                    ->participations
                    ->firstWhere($primaryKey, $latestMessage?->author_id)
                    ?->participant;

                if ($latestMessages->isEmpty()) {
                    $fallbackParticipants = $conversation
                            ->participations
                            ->where('participant_id', '!=', auth()->id())
                            ->pluck('participant')
                            ->filter()
                            ->values();

                        $participantWithLatestMessage = $fallbackParticipants->get(0);
                        $participantWithPenultimateMessage = $fallbackParticipants->get(1);
                } else {
                    $secondParticipantLatestMessage = $latestMessages
                        ->where('author_id', '!=', $latestMessage?->author_id)
                        ->first();

                    $participantWithPenultimateMessage = $conversation
                        ->participations
                        ->firstWhere($primaryKey, $secondParticipantLatestMessage?->author_id)
                        ?->participant;

                    if (!$participantWithLatestMessage || !$participantWithPenultimateMessage) {
                        $fallbackParticipants = $conversation
                            ->participations
                            ->pluck('participant')
                            ->filter()
                            ->where('id', '!=', auth()->id())
                            ->whereNotIn('id', array_filter([
                                $participantWithLatestMessage?->getKey(),
                                $participantWithPenultimateMessage?->getKey(),
                            ]))
                            ->values();

                        $participantWithLatestMessage ??= $fallbackParticipants->get(0);
                        $participantWithPenultimateMessage ??= $fallbackParticipants->get(1);
                    }
                }
            @endphp

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
                :src="filament()->getUserAvatarUrl($otherConversationParticipations->first()->participant)"
                :alt="$conversationName"
                size="lg"
            />
        @endif
    </div>
@endif
