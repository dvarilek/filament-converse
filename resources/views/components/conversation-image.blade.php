@props([
    'conversation',
    'conversationName',
    'conversationImage' => null,
])

@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Illuminate\Support\Collection;

    /* @var Conversation $conversation */
@endphp

@if ($conversationImage)
    <div class="fi-converse-conversation-image-container">
        <x-filament::avatar
            class="fi-converse-conversation-image-image"
            :src="$conversationImage"
            :alt="$conversationName"
            size="lg"
        />
    </div>
@else
    @php
        $hasMultipleAvatarsInConversationImage = $conversation->isGroup() && $conversation->participations->count() > 2;
        $otherConversationParticipations = $conversation->participations->where('participant_id', '!=', auth()->id());
    @endphp

    <div
        @class([
            'fi-converse-conversation-image-multiple-avatars' => $hasMultipleAvatarsInConversationImage,
            'fi-converse-conversation-image-container',
        ])
    >
        @if ($hasMultipleAvatarsInConversationImage)
            @php
                /* @var Collection<int, Message> $latestMessages */
                $latestMessages = $otherConversationParticipations
                    ->pluck('latestMessage')
                    ->filter()
                    ->sortByDesc('created_at');

                if ($latestMessages->isEmpty()) {
                    [$bottomAvatarParticipant, $topAvatarParticipant] = $otherConversationParticipations->pluck('participant');
                } else {
                    $conversationParticipationPrimaryKey = (new ConversationParticipation)->getKeyName();

                    $firstLatestMessage = $latestMessages->first();
                    $secondLatestMessage = $latestMessages->firstWhere('author_id', '!=', $firstLatestMessage->author_id);

                    $firstParticipationWithLatestMessage = $otherConversationParticipations
                        ->firstWhere($conversationParticipationPrimaryKey, $firstLatestMessage->author_id);

                    $secondParticipationWithLatestMessage = $otherConversationParticipations
                        ->firstWhere($conversationParticipationPrimaryKey, $secondLatestMessage?->author_id ?? $firstParticipationWithLatestMessage->getKey());

                    $bottomAvatarParticipant = $firstParticipationWithLatestMessage->participant;
                    $topAvatarParticipant = $secondParticipationWithLatestMessage->participant;
                }
            @endphp

            <x-filament::avatar
                class="fi-converse-conversation-image-image fi-converse-conversation-image-top-avatar"
                :src="filament()->getUserAvatarUrl($topAvatarParticipant)"
                :alt="$topAvatarParticipant->getAttributeValue($topAvatarParticipant::getFilamentNameAttribute())"
                size="md"
            />
            <x-filament::avatar
                color="primary"
                class="fi-converse-conversation-image-image fi-converse-conversation-image-bottom-avatar"
                :src="filament()->getUserAvatarUrl($bottomAvatarParticipant)"
                :alt="$bottomAvatarParticipant->getAttributeValue($bottomAvatarParticipant::getFilamentNameAttribute())"
                size="md"
            />
        @else
            <x-filament::avatar
                class="fi-converse-conversation-image-image"
                :src="filament()->getUserAvatarUrl($otherConversationParticipations->first()->participant)"
                :alt="$conversationName"
                size="lg"
            />
        @endif
    </div>
@endif
