@php
    use Dvarilek\FilamentConverse\Models\Collections\ConversationParticipationCollection;use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
    use Dvarilek\FilamentConverse\View\Components\ConversationMessageComponent;
    use Dvarilek\FilamentConverse\View\Components\NewMessagesDividerComponent;
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Filament\Schemas\Components\Icon;
    use Illuminate\Contracts\Support\Htmlable;
    use Illuminate\Support\Collection;
    use Illuminate\View\ComponentAttributeBag;

    $id = $getId();
    $fieldWrapperView = $getFieldWrapperView();
    $extraAttributeBag = $getExtraAttributeBag();
    $key = $getKey();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();

    /* @var Conversation | null $conversation */
    $conversation = $getActiveConversation();
    $conversationKey = $conversation?->getKey();
    $hasConversation = filled($conversation);

    /* @var Collection<int, Message> $messages */
    $messages = $getMessagesQuery()?->get()?->reverse() ?? [];
    $totalMessagesCount = $getMessagesQuery(shouldPaginate: false)?->count() ?? 0;
    /* @var ComponentAttributeBag $extraMessageAttributeBag */
    $extraMessageAttributeBag = $getExtraMessageAttributeBag();

    $headerActions = array_filter(
        $getChildComponents(ConversationThread::HEADER_ACTIONS_KEY),
        static fn (Action | ActionGroup $action) => $action->isVisible()
    );
@endphp

<div
    wire:key="conversation-thread-{{ $conversationKey }}"
    @if ($hasConversation)
        x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('conversation-thread', 'dvarilek/filament-converse') }}"
    x-data="conversationThread({
                    key: @js($key),
                    conversationKey: @js($conversationKey),
                    autoScrollOnForeignMessagesThreshold: @js($getAutoScrollOnForeignMessagesThreshold()),
                    shouldDispatchUserTypingEvent: @js($shouldDispatchUserTypingEvent()),
                    userTypingIndicatorTimeout: @js($getUserTypingIndicatorTimeout()),
                    userTypingEventDispatchThreshold: @js($getUserTypingEventDispatchThreshold()),
                    userTypingTranslations: @js($getUserTypingTranslations()),
                    $wire,
                })"
    x-ref="uploadDropZoneRef"
    @endif
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-converse-conversation-thread',
            ])
    }}
>
    <div class="fi-converse-conversation-thread-header">
        <div class="fi-converse-conversation-thread-header-content">
            <x-filament::icon-button
                x-cloak
                color="gray"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedBars3"
                class="fi-converse-conversation-thread-header-expand-list-button"
                icon-size="lg"
                x-on:click="showConversationListSidebar = !showConversationListSidebar"
            />

            @if ($hasConversation)
                @php
                    $conversationName = $getConversationName($conversation);
                    $shouldShowConversationImage = $shouldShowConversationImage($conversation);
                @endphp

                @if ($shouldShowConversationImage)
                    <x-filament-converse::conversation-image
                        :conversation="$conversation"
                        :conversation-name="$conversationName"
                        :conversation-image-url="$getConversationImageUrl($conversation)"
                        :get-default-conversation-image-data="$getDefaultConversationImageData"
                    />
                @endif

                <h2 class="fi-converse-conversation-thread-header-heading">
                    {{ $conversationName }}
                </h2>
            @endif
        </div>

        @if ($hasConversation && count($headerActions))
            <div class="fi-converse-conversation-thread-header-actions">
                @foreach ($headerActions as $action)
                    {{ $action }}
                @endforeach
            </div>
        @endif
    </div>

    <div
        class='fi-converse-conversation-thread-content'
        @if ($hasConversation && count($messages))
            x-ref="conversationThreadContent"
        wire:key="fi-converse-conversation-thread-content-{{ $id }}-{{ $key }}"
        x-init="scrollToBottom()"
        @endif
    >
        @if ($renderedMessagesCount = count($messages))
            @php
                /* @var ConversationParticipation $currentAuthenticatedUserParticipation */
                $currentAuthenticatedUserParticipation = $this->getActiveConversationAuthenticatedUserParticipation();
                $latestMessage = $messages->last();

                $shouldMarkConversationAsRead = $shouldMarkConversationAsRead();
                /* @var array<string, array{readBy: list<ConversationParticipation>, readByAsLastMessage: list<ConversationParticipation>}> $messageReadsMap */
                $messageReadsMap = $getMessageReadsMap($messages);
                /* @var Collection<int, Message> $unreadMessages */
                $unreadMessages = ($lastReadAt = $currentAuthenticatedUserParticipation->last_read_at)
                    ? $messages->filter(static fn (Message $message) => $lastReadAt->lt($message->created_at))
                    : $messages;
            @endphp

            @if ($renderedMessagesCount < $totalMessagesCount)
                <div
                    x-cloak
                    x-show="isLoadingMoreMessages"
                    class="fi-converse-conversation-thread-messages-load-more-message-loading-indicator"
                >
                    {{ \Filament\Support\generate_loading_indicator_html() }}
                </div>

                <div
                    x-intersect="loadMoreMessages()"
                    aria-hidden="true"
                ></div>
            @endif

            @foreach ($messages as $message)
                @php
                    $messageAuthor = $conversation->participations->firstWhere((new ConversationParticipation)->getKeyName(), $message->author_id)->participant;
                    $isAuthoredByAuthenticatedUser = $messageAuthor->getKey() === auth()->id();
                    $messageAuthorName = $getMessageAuthorName($message, $messageAuthor, $messages);
                    $messageAuthorAvatar = $getMessageAuthorAvatar($message, $messageAuthor, $messages);
                    $messageTimestamp = $getMessageTimestamp($message, $messageAuthor, $messages);
                    $messageContent = $getMessageContent($message, $messageAuthor, $messages);
                    /* @var array{attachmentOriginalName: string, attachmentMimeType: string, hasImageMimeType: bool, shouldShowOnlyMessageImageAttachment: bool} $messageAttachmentData */
                    $messageAttachmentData = $getMessageAttachmentData($message, $messageAuthor, $messages);
                    $messageDividerContent = $getMessageDividerContent($message, $messageAuthor, $messages);
                    $messageColor = $getMessageColor($message, $messageAuthor, $messages);
                    $hasMessageAuthorName = filled($messageAuthorName);
                    $hasMessageTimestamp = filled($messageTimestamp);

                    /* @var ConversationParticipationCollection<int, ConversationParticipation> $readByParticipations */
                    $readByParticipations = new ConversationParticipationCollection($messageReadsMap[$message->getKey()]['readBy'] ?? []);
                    /* @var ConversationParticipationCollection<int, ConversationParticipation> $readByParticipationsAsLastMessage */
                    $readByParticipationsAsLastMessage = new ConversationParticipationCollection($messageReadsMap[$message->getKey()]['readByAsLastMessage'] ?? []);
                    $showReadReceipt = $shouldShowReadReceipts($message, $readByParticipations, $readByParticipationsAsLastMessage, $messages);

                    $isMessageUnread = $unreadMessages->contains(static fn (Message $msg) => $msg->getKey() === $message->getKey());
                    $markConversationAsRead = $message->getKey() === $latestMessage->getKey() && $isMessageUnread && $shouldMarkConversationAsRead;
                    $showNewMessagesDivider = $shouldShowNewMessagesDivider($message, $messageAuthor, $messages, $unreadMessages);
                @endphp

                <div
                    @if ($markConversationAsRead)
                        x-intersect.threshold.50.once="
                            await $wire.callSchemaComponentMethod(@js($key), 'markCurrentConversationAsRead')

                            $wire.$refresh()
                        "
                    @endif
                    {{
                        $extraMessageAttributeBag
                            ->class([
                                'fi-converse-conversation-thread-message-container',
                                'fi-converse-conversation-thread-message-container-reversed' => $isAuthoredByAuthenticatedUser,
                            ])
                    }}
                >
                    @if ($showNewMessagesDivider && filled($newMessagesDividerContent = $getNewMessagesDividerContent($message, $messageAuthor, $messages, $unreadMessages)))
                        @if ($newMessagesDividerContent instanceof Htmlable)
                            {{ $newMessagesDividerContent }}
                        @else
                            @php
                                $newMessagesDividerColor = $getNewMessagesDividerColor($message, $messageAuthor, $messages, $unreadMessages);
                            @endphp

                            <div
                                {{
                                    (new ComponentAttributeBag)
                                        ->color(NewMessagesDividerComponent::class, $newMessagesDividerColor)
                                        ->class([
                                            'fi-converse-conversation-thread-new-messages-divider-content',
                                        ])
                                }}
                            >
                                <div
                                    class="fi-converse-conversation-thread-unread-messages-divider-separator"
                                ></div>
                                <div
                                    class="fi-converse-conversation-thread-unread-messages-divider-text"
                                >
                                    {{ $newMessagesDividerContent }}
                                </div>
                                <div
                                    class="fi-converse-conversation-thread-unread-messages-divider-separator"
                                ></div>
                            </div>
                        @endif
                    @endif

                    @if (filled($messageDividerContent))
                        @if ($messageDividerContent instanceof Htmlable)
                            {{ $messageDividerContent }}
                        @else
                            <div
                                class="fi-converse-conversation-thread-message-divider-content"
                            >
                                {{ $messageDividerContent }}
                            </div>
                        @endif
                    @endif

                    <div
                        class="fi-converse-conversation-thread-message-layout group"
                    >
                        @if (filled($messageAuthorAvatar))
                            @if ($messageAuthorAvatar instanceof Htmlable)
                                {{ $messageAuthorAvatar }}
                            @else
                                <x-filament::avatar
                                    class="fi-converse-conversation-thread-message-avatar"
                                    :src="$messageAuthorAvatar"
                                    :alt="$messageAuthorName"
                                    size="md"
                                />
                            @endif
                        @endif

                        <div
                            class="fi-converse-conversation-thread-message-content"
                        >
                            @if ($hasMessageAuthorName || $hasMessageTimestamp)
                                <div
                                    class="fi-converse-conversation-thread-message-heading"
                                >
                                    @if ($hasMessageAuthorName)
                                        <div
                                            class="fi-converse-conversation-thread-message-author-name"
                                        >
                                            {{ $messageAuthorName }}
                                        </div>
                                    @endif

                                    @if ($hasMessageTimestamp)
                                        <div
                                            class="fi-converse-conversation-thread-message-timestamp"
                                        >
                                            {{ $messageTimestamp }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div
                                class="fi-converse-conversation-thread-message-body-container"
                            >
                                <div
                                    {{
                                        (new ComponentAttributeBag)
                                            ->color(ConversationMessageComponent::class, $messageColor)
                                            ->class([
                                                'fi-converse-conversation-thread-message-body',
                                            ])
                                    }}
                                >
                                    @if (filled($messageContent))
                                        @if ($messageContent instanceof Htmlable)
                                            {{ $messageContent }}
                                        @else
                                            <p>
                                                {{ $messageContent }}
                                            </p>
                                        @endif
                                    @endif

                                    @if (count($messageAttachmentData))
                                        @php
                                            $hasOnlyImageAttachments = collect($messageAttachmentData)
                                                ->every(static fn (array $data) => $data['hasImageMimeType'] && $data['shouldShowOnlyMessageImageAttachment']);
                                        @endphp

                                        <div
                                            @class([
                                                'fi-converse-conversation-thread-message-attachments',
                                                'fi-converse-conversation-thread-message-attachments-with-generic-attachments' => ! $hasOnlyImageAttachments,
                                            ])
                                        >
                                            @foreach ($messageAttachmentData as $attachmentPath => $data)
                                                @php
                                                    $attachmentOriginalName = $data['attachmentOriginalName'];
                                                    $attachmentMimeType = $data['attachmentMimeType'];
                                                    $hasImageMimeType = $data['hasImageMimeType'];
                                                    $data = [
                                                        'message' => $message,
                                                        'messageAuthor' => $messageAuthor,
                                                        'messages' => $messages
                                                    ];
                                                @endphp

                                                <x-filament-converse::conversation-attachment
                                                    :has-image-mime-type="$hasImageMimeType"
                                                    :file-attachment-name="$getFileAttachmentName($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
                                                    :file-attachment-toolbar="$getFileAttachmentToolbar($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
                                                    :should-show-only-image-attachment="$data['shouldShowOnlyMessageImageAttachment'] ?? $shouldShowOnlyImageAttachment($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
                                                    :file-attachment-url="$hasImageMimeType ? $getFileAttachmentUrl($attachmentPath) : null"
                                                    :should-preview-image-attachment="$shouldPreviewImageAttachment($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
                                                    :file-attachment-icon="$getFileAttachmentIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
                                                    :mime-type-badge-label="$getFileAttachmentMimeTypeBadgeLabel($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
                                                    :mime-type-badge-icon="$getFileAttachmentMimeTypeBadgeIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
                                                    :mime-type-badge-color="$getFileAttachmentMimeTypeBadgeColor($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
                                                    :image-attachment-container-extra-attributes-bag="
                                                        (new ComponentAttributeBag)
                                                            ->class(['fi-converse-image-attachment-container-grid'])
                                                    "
                                                    :generic-attachment-container-extra-attributes-bag="
                                                        (new ComponentAttributeBag)
                                                            ->class(['fi-converse-generic-attachment-container-grid'])
                                                    "
                                                />
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div
                                    class="fi-converse-conversation-thread-message-actions"
                                >
                                    {{
                                        $getChildComponents(ConversationThread::MESSAGE_ACTIONS_KEY)[0]
                                            ->record($message)
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($showReadReceipt && ($shortenedReadReceiptMessage = $getShortenedReadReceiptMessage($message, $readByParticipations, $readByParticipationsAsLastMessage, $messages)))
                        @php
                            $showFullReadReceiptMessage = $shouldShowFullReadReceiptMessage($message, $readByParticipations, $readByParticipationsAsLastMessage, $messages);
                        @endphp

                        <div
                            class="fi-converse-conversation-thread-read-receipt"
                            @if ($showFullReadReceiptMessage)
                                x-data="{ expanded: false }"
                                x-on:click="expanded = ! expanded"
                            @endif
                        >
                            @if ($showFullReadReceiptMessage)
                                <span x-show="expanded">
                                    {{ $getFullReadReceiptMessage($message, $readByParticipations, $readByParticipationsAsLastMessage, $messages) }}
                                </span>
                                <span x-show="! expanded">
                                    {{ $shortenedReadReceiptMessage }}
                                </span>
                            @endif

                            <span>
                                {{ $shortenedReadReceiptMessage }}
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            @if ($emptyState = $getEmptyState())
                {{ $emptyState }}
            @else
                <x-filament::empty-state
                    :icon="$getEmptyStateIcon()"
                    :icon-color="$getEmptyStateIconColor()"
                    class="fi-converse-conversation-thread-empty-state"
                >
                    <x-slot name="heading">
                        {{ $getEmptyStateHeading() }}
                    </x-slot>
                    <x-slot name="description">
                        {{ $getEmptyStateDescription() }}
                    </x-slot>
                </x-filament::empty-state>
            @endif
        @endif

        <div class="fi-converse-conversation-thread-messages-content-footer">
            @if ($shouldShowTypingIndicator())
                <div
                    x-cloak
                    x-show="areOtherUsersTyping()"
                    class="fi-converse-conversation-thread-messages-users-typing-loading-indicator"
                >
                    <div class="typing-dots-container">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                    <div
                        x-text="getTypingUsersMessage()"
                        class="typing-dots-container"
                    ></div>
                </div>
            @endif
        </div>

        <div
            x-ref="conversationThreadContentEndMarker"
            aria-hidden="true"
        ></div>
    </div>

    @if ($hasConversation)
        <div class="fi-converse-conversation-thread-footer-wrapper">
            {{ $getChildSchema() }}
        </div>
    @endif
</div>
