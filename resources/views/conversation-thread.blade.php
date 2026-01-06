@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
    use Dvarilek\FilamentConverse\View\Components\ConversationMessageComponent;
    use Dvarilek\FilamentConverse\View\Components\NewMessagesDividerComponent;
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Filament\Schemas\Components\Icon;
    use Filament\Support\View\Components\ModalComponent\IconComponent;
    use Illuminate\Contracts\Support\Htmlable;
    use Illuminate\Support\Collection;
    use Illuminate\View\ComponentAttributeBag;

    $id = $getId();
    $fieldWrapperView = $getFieldWrapperView();
    $extraAttributeBag = $getExtraAttributeBag();
    $key = $getKey();
    $statePath = $getStatePath();

    /* @var Conversation | null $conversation */
    $conversation = $getActiveConversation();
    $conversationKey = $conversation?->getKey();
    $hasConversation = filled($conversation);

    $isDisabled = $isDisabled();
    $hasFileAttachments = $hasFileAttachments();
    $canUploadFileAttachments = $hasConversation && $hasFileAttachments && ! $isDisabled;
    $uploadedFileAttachments = $canUploadFileAttachments ? array_reverse($getUploadedFileAttachments()) : [];

    /* @var Collection<int, Message> $messages */
    $messages = $getMessagesQuery()?->get()?->reverse() ?? [];
    $totalMessagesCount = $getMessagesQuery(shouldPaginate: false)?->count() ?? 0;
    /* @var ComponentAttributeBag $extraMessageAttributeBag */
    $extraMessageAttributeBag = $getExtraMessageAttributeBag();

    $headerActions = array_filter(
        $getChildComponents(ConversationThread::HEADER_ACTIONS_KEY),
        static fn (Action | ActionGroup $action) => $action->isVisible()
    );

    $messageActions = $getChildComponents(ConversationThread::MESSAGE_ACTIONS_KEY);
@endphp

<div
    wire:key="conversation-thread-{{ $conversationKey }}"
    @if ($hasConversation)
        @php
            $fileAttachmentsAcceptedFileTypes = $getFileAttachmentsAcceptedFileTypes();
            $fileAttachmentsMaxSize = $getFileAttachmentsMaxSize();
            $maxFileAttachments = $getMaxFileAttachments();
        @endphp

        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('conversation-thread', 'dvarilek/filament-converse') }}"
        x-data="conversationThread({
                    key: @js($key),
                    conversationKey: @js($conversationKey),
                    statePath: @js($statePath),
                    autoScrollOnForeignMessagesThreshold: @js($getAutoScrollOnForeignMessagesThreshold()),
                    shouldDispatchUserTypingEvent: @js($shouldDispatchUserTypingEvent()),
                    userTypingIndicatorTimeout: @js($getUserTypingIndicatorTimeout()),
                    userTypingEventDispatchThreshold: @js($getUserTypingEventDispatchThreshold()),
                    userTypingTranslations: @js($getUserTypingTranslations()),
                    fileAttachmentAcceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),
                    fileAttachmentMaxSize: @js($fileAttachmentsMaxSize),
                    maxFileAttachments: @js($maxFileAttachments),
                    fileAttachmentsAcceptedFileTypesValidationMessage: @js($getAttachmentsAcceptedFileTypesValidationMessage($fileAttachmentsAcceptedFileTypes)),
                    fileAttachmentsMaxSizeValidationMessage: @js($getAttachmentsMaxFileSizeValidationMessage($fileAttachmentsMaxSize)),
                    maxFileAttachmentsValidationMessage: @js($getMaxFileAttachmentsValidationMessage($maxFileAttachments)),
                    $wire,
                })"
        {{
            $getExtraAttributeBag()
                ->class([
                    'fi-converse-conversation-thread',
                ])
        }}
        x-bind:class="{
            'fi-converse-highlight-conversation-thread': isDraggingFileAttachment,
        }"
    @endif
>
    @if ($canUploadFileAttachments)
        <input
            type="file"
            @if (count($uploadedFileAttachments) !== 1)
                multiple
            @endif
            x-ref="fileInput"
            class="hidden"
            x-on:change="handleAttachmentUpload($event.target.files)"
        />
        <div
            x-cloak
            x-show="isDraggingFileAttachment"
            class="fi-converse-attachment-modal-overlay"
        >
            <div class="fi-converse-attachment-modal-backdrop"></div>

            <div
                x-cloak
                x-show="isDraggingFileAttachment"
                class="fi-converse-attachment-modal"
            >
                <div class="fi-converse-attachment-modal-header">
                    <div
                        {{ (new ComponentAttributeBag)->color(IconComponent::class, $getAttachmentModalIconColor(), 'primary')->class(['fi-converse-attachment-modal-icon-bg']) }}
                    >
                        {{ \Filament\Support\generate_icon_html($getAttachmentModalIcon(), size: \Filament\Support\Enums\IconSize::Large) }}
                    </div>
                </div>
                <div class="fi-converse-attachment-modal-content">
                    <h2 class="fi-converse-attachment-modal-heading">
                        {{ $getAttachmentModalHeading() }}
                    </h2>
                    @if (filled($attachmentModalDescription = $getAttachmentModalDescription()))
                        <p class="fi-converse-attachment-modal-description">
                            {{ $attachmentModalDescription }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

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

        @if (count($headerActions))
            <div class="fi-converse-conversation-thread-header-actions">
                @foreach ($headerActions as $action)
                    {{ $action }}
                @endforeach
            </div>
        @endif
    </div>

    <div
        @if ($hasConversation && count($messages))
            x-ref="conversationThreadContent"
            wire:key="fi-converse-conversation-thread-content-{{ $id }}-{{ $key }}"
            x-init="scrollToBottom()"
        @endif
        @class([
            'fi-converse-conversation-thread-content',
            'fi-converse-relative' => $canUploadFileAttachments,
        ])
    >
        @if ($canUploadFileAttachments)
            <div
                x-cloak
                x-show="fileAttachmentUploadValidationMessage"
                class="fi-converse-conversation-thread-upload-validation-message-container"
            >
                <p x-text="fileAttachmentUploadValidationMessage"></p>

                <x-filament::icon-button
                    color="gray"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                    icon-size="md"
                    :label="__('filament-converse::conversation-thread.attachments.validation-message-close-button-label')"
                    tabindex="-1"
                    x-on:click="fileAttachmentUploadValidationMessage = null"
                    class="fi-converse-conversation-thread-upload-validation-message-close-button"
                />
            </div>
        @endif

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

                    /* @var Collection<int, ConversationParticipation> $readByParticipations */
                    $readByParticipations = collect($messageReadsMap[$message->getKey()]['readBy'] ?? []);
                    /* @var Collection<int, ConversationParticipation> $readByParticipationsAsLastMessage */
                    $readByParticipationsAsLastMessage = collect($messageReadsMap[$message->getKey()]['readByAsLastMessage'] ?? []);
                    $showReadReceipt = $shouldShowReadReceipts($message, $readByParticipations, $readByParticipationsAsLastMessage, $messages);

                    $isMessageUnread = $unreadMessages->contains(static fn (Message $msg) => $msg->getKey() === $message->getKey());
                    $markConversationAsRead = $message->getKey() === $latestMessage->getKey() && $isMessageUnread && $shouldMarkConversationAsRead;
                    $newMessagesDividerContent = $getNewMessagesDividerContent($message, $messageAuthor, $messages, $unreadMessages);

                    $filteredMessageActions = array_filter(
                        $messageActions,
                        static function (Action | ActionGroup $action) use ($message) {
                            $action->record($message)->arguments(['record' => $message->getKey()]);

                            return $action->isVisible();
                        }
                    );
                @endphp

                <div
                    @if ($markConversationAsRead)
                        x-intersect.threshold.50.once="await $wire.callSchemaComponentMethod(@js($key), 'markCurrentConversationAsRead')"
                    @endif
                    {{
                        $extraMessageAttributeBag
                            ->class([
                                'fi-converse-conversation-thread-message-container',
                                'fi-converse-conversation-thread-message-container-reversed' => $isAuthoredByAuthenticatedUser,
                            ])
                    }}
                >
                    @if (filled($newMessagesDividerContent) && $shouldShowNewMessagesDivider($message, $messageAuthor, $messages, $unreadMessages))
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
                                                @endphp

                                                <x-filament-converse::conversation-attachment
                                                    :has-image-mime-type="$hasImageMimeType"
                                                    :file-attachment-name="$getMessageFileAttachmentName($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message, $messageAuthor, $messages)"
                                                    :file-attachment-toolbar="$getMessageFileAttachmentToolbar($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message, $messageAuthor, $messages)"
                                                    :should-show-only-image-attachment="$data['shouldShowOnlyMessageImageAttachment']"
                                                    :file-attachment-url="$hasImageMimeType ? $getFileAttachmentUrl($attachmentPath) : null"
                                                    :should-preview-image-attachment="$shouldPreviewMessageImageAttachment($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message, $messageAuthor, $messages)"
                                                    :file-attachment-icon="$getMessageFileAttachmentIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message, $messageAuthor, $messages)"
                                                    :mime-type-badge-label="$getMessageFileAttachmentMimeTypeBadgeLabel($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message, $messageAuthor, $messages)"
                                                    :mime-type-badge-icon="$getMessageFileAttachmentMimeTypeBadgeIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message, $messageAuthor, $messages)"
                                                    :mime-type-badge-color="$getMessageFileAttachmentMimeTypeBadgeColor($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message, $messageAuthor, $messages)"
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

                                @if (count($filteredMessageActions))
                                    <div
                                        class="fi-converse-conversation-thread-message-actions"
                                    >
                                        @foreach ($filteredMessageActions as $action)
                                            {{ $action }}
                                        @endforeach
                                    </div>
                                @endif
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
        @php
            $fieldWrapperView = $getFieldWrapperView();
            $extraAttributeBag = $getExtraAttributeBag();
            $isConcealed = $isConcealed();
            $rows = $getRows();
            $maxHeight = $getMaxHeight();
            $placeholder = $getPlaceholder();
            $shouldAutosize = $shouldAutosize();
            $placeholder = $getPlaceholder();

            $initialHeight = (($rows ?? 2) * 1.5) + 0.75;
        @endphp

        <x-dynamic-component
            :component="$fieldWrapperView"
            :field="$field"
            class="fi-converse-conversation-thread-footer fi-fo-textarea-wrp"
        >
            <x-filament::input.wrapper
                :disabled="$isDisabled"
                :valid="! $errors->has($statePath)"
                :attributes="
                    \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
                        ->class([
                            'fi-fo-textarea fi-converse-conversation-thread-message-input ',
                            'fi-autosizable' => $shouldAutosize,
                        ])
                "
            >
                @if ($hasFileAttachments)
                    <div
                        wire:key="fi-converse-conversation-thread-attachment-area-{{ $id }}-{{ $key }}-{{ count($uploadedFileAttachments) }}"
                        class="fi-converse-attachment-area"
                        x-bind:class="{
                            'fi-converse-attachment-area-has-content':
                                isUploadingFileAttachment() || @js(count($uploadedFileAttachments) > 0),
                        }"
                    >
                        <template
                            x-for="file in uploadingFileAttachments.reverse()"
                        >
                            <div
                                x-bind:class="
                                    file.type.startsWith('image/')
                                        ? 'fi-converse-image-attachment-container'
                                        : 'fi-converse-generic-attachment-container fi-converse-attachment-adaptable-width'
                                "
                            >
                                <div
                                    x-cloak
                                    x-show="file.type.startsWith('image/')"
                                    class="fi-converse-image-attachment-skeleton"
                                >
                                    {{ Icon::make(\Filament\Support\Icons\Heroicon::OutlinedPhoto)->color('gray')->extraAttributes(['class' => 'fi-size-2xl']) }}
                                </div>

                                <div
                                    x-cloak
                                    x-show="! file.type.startsWith('image/')"
                                    class="fi-converse-generic-attachment-skeleton"
                                ></div>

                                <div
                                    x-cloak
                                    x-show="! file.type.startsWith('image/')"
                                    class="fi-converse-attachment-information-container-skeleton"
                                >
                                    <div
                                        class="fi-converse-attachment-name-skeleton"
                                    ></div>
                                    <div
                                        class="fi-converse-attachment-mime-type-badge-skeleton"
                                    ></div>
                                </div>
                            </div>
                        </template>

                        @foreach ($uploadedFileAttachments as $fileAttachment)
                            @php
                                $hasImageMimeType = $isImageMimeType($fileAttachment->getMimeType());
                            @endphp

                            <x-filament-converse::conversation-attachment
                                :has-image-mime-type="$hasImageMimeType"
                                :file-attachment-name="$getUploadedFileAttachmentName($fileAttachment)"
                                :file-attachment-toolbar="$getUploadedFileAttachmentToolbar($fileAttachment)"
                                :should-show-only-image-attachment="$shouldShowOnlyUploadedImageAttachment($fileAttachment)"
                                :file-attachment-url="$hasImageMimeType ? $fileAttachment->temporaryUrl() : null"
                                :should-preview-image-attachment="$shouldPreviewUploadedImageAttachment($fileAttachment)"
                                :file-attachment-icon="$getUploadedFileAttachmentIcon($fileAttachment)"
                                :mime-type-badge-label="$getUploadedFileAttachmentMimeTypeBadgeLabel($fileAttachment)"
                                :mime-type-badge-icon="$getUploadedFileAttachmentMimeTypeBadgeIcon($fileAttachment)"
                                :mime-type-badge-color="$getUploadedFileAttachmentMimeTypeBadgeColor($fileAttachment)"
                                :is-removable="true"
                                file-attachment-remove-handler="$wire.removeUpload('componentFileAttachments.{{ $statePath }}.{{ $conversationKey }}', '{{ $fileAttachment->getFilename() }}')"
                                :generic-attachment-container-extra-attributes-bag="
                                    (new ComponentAttributeBag)
                                        ->class(['fi-converse-attachment-adaptable-width'])
                                "
                            />
                        @endforeach
                    </div>
                @endif

                <div
                    @style([
                        'max-height: ' . $maxHeight . 'rem; overflow: auto' => filled($maxHeight),
                    ])
                >
                    <div
                        wire:ignore.self
                        style="height: '{{ $initialHeight . 'rem' }}'"
                    >
                        <textarea
                            x-load
                            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('textarea', 'filament/forms') }}"
                            x-data="textareaFormComponent({
                                        initialHeight: @js($initialHeight),
                                        shouldAutosize: @js($shouldAutosize),
                                        state: $wire.$entangle('{{ $statePath }}'),
                                    })"
                            @if ($shouldAutosize)
                                x-intersect.once="resize()"
                                x-on:resize.window="resize()"
                            @endif
                            x-model="state"
                            @if ($isGrammarlyDisabled())
                                data-gramm="false"
                                data-gramm_editor="false"
                                data-enable-grammarly="false"
                            @endif
                            x-on:keydown="$nextTick(() => fireUserTypingEvent($event))"
                            {{ $getExtraAlpineAttributeBag() }}
                            {{
                                $getExtraInputAttributeBag()
                                    ->merge([
                                        'autocomplete' => $getAutocomplete(),
                                        'autofocus' => $isAutofocused(),
                                        'cols' => $getCols(),
                                        'disabled' => $isDisabled,
                                        'id' => $getId(),
                                        'maxlength' => (! $isConcealed) ? $getMaxLength() : null,
                                        'minlength' => (! $isConcealed) ? $getMinLength() : null,
                                        'placeholder' => filled($placeholder) ? e($placeholder) : null,
                                        'readonly' => $isReadOnly(),
                                        'required' => $isRequired() && (! $isConcealed),
                                        'rows' => $rows,
                                        $applyStateBindingModifiers('wire:model') => $statePath,
                                    ], escape: false)
                            }}
                        ></textarea>
                    </div>
                </div>
                @php
                    $uploadAttachmentAction = $getAction('uploadAttachment');
                    $sendMessageAction = $getAction('sendMessage');
                @endphp

                @if ($uploadAttachmentAction || $sendMessageAction)
                    <div class="fi-converse-message-input-footer">
                        @if ($uploadAttachmentAction)
                            <div
                                class="fi-converse-message-input-footer-left-actions"
                            >
                                {{ $uploadAttachmentAction }}
                            </div>
                        @endif

                        @if ($sendMessageAction)
                            <div
                                class="fi-converse-message-input-footer-right-actions"
                            >
                                {{ $sendMessageAction }}
                            </div>
                        @endif
                    </div>
                @endif
            </x-filament::input.wrapper>
        </x-dynamic-component>
    @endif
</div>
