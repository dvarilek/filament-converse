@php
    use Carbon\Carbon;
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Illuminate\Support\Collection;
    use Illuminate\View\ComponentAttributeBag;
    use Filament\Support\View\Components\ModalComponent\IconComponent;
    use Illuminate\Contracts\Filesystem\Filesystem;
    use Filament\Schemas\Components\Icon;
    use Illuminate\Support\Facades\Storage;
    use Dvarilek\FilamentConverse\View\Components\ConversationMessageComponent;

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
    $uploadedFileAttachments = $canUploadFileAttachments ? $getUploadedFileAttachments() : [];

    $commonMarkOptions = $getCommonMarkOptions();
    $commonMarkExtensions = $getCommonMarkExtensions();

    /* @var Collection<int, Message> $messages */
    $messages = $getMessagesQuery()?->get()?->reverse() ?? [];
    $totalMessagesCount = $getMessagesQuery(shouldPaginate: false)?->count() ?? 0;
    $messageTimestampGroupingInterval = $getMessageTimestampGroupingInterval();
    /* @var Carbon | null $previousMessageTimestamp */
    $previousMessageTimestamp = null;

    $headerActions = array_filter(
        $getChildComponents(ConversationThread::HEADER_ACTIONS_KEY),
        static fn (Action | ActionGroup $action) => $action->isVisible()
    );

    $messageActions = $getChildComponents(ConversationThread::MESSAGE_ACTIONS_KEY);
@endphp

<div
    class="fi-converse-conversation-thread"
    @if ($hasConversation)
        @php
            $fileAttachmentsAcceptedFileTypes = $getFileAttachmentsAcceptedFileTypes();
            $fileAttachmentsMaxSize = $getFileAttachmentsMaxSize();
            $maxFileAttachments = $getMaxFileAttachments();
        @endphp

        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('conversation-thread', 'dvarilek/filament-converse') }}"
        x-data="conversationThread({
                    conversationKey: @js($conversationKey),
                    statePath: @js($statePath),
                    autoScrollOnForeignMessagesThreshold: @js($getAutoScrollOnForeignMessagesThreshold()),
                    fileAttachmentAcceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),
                    fileAttachmentMaxSize: @js($fileAttachmentsMaxSize),
                    maxFileAttachments: @js($maxFileAttachments),
                    fileAttachmentsAcceptedFileTypesValidationMessage: @js($getAttachmentsAcceptedFileTypesValidationMessage($fileAttachmentsAcceptedFileTypes)),
                    fileAttachmentsMaxSizeValidationMessage: @js($getAttachmentsMaxFileSizeValidationMessage($fileAttachmentsMaxSize)),
                    maxFileAttachmentsValidationMessage: @js($getMaxFileAttachmentsValidationMessage($maxFileAttachments)),
                    $wire,
                })"
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
        @if ($hasConversation)
            @php
                $conversationName = $getConversationName($conversation);
            @endphp

            <div class="fi-converse-conversation-thread-header-content">
                <x-filament::icon-button
                    x-cloak
                    color="gray"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedBars3"
                    class="fi-converse-conversation-thread-header-expand-list-button"
                    icon-size="lg"
                    x-on:click="showConversationListSidebar = !showConversationListSidebar"
                />

                <x-filament-converse::conversation-image
                    :conversation="$conversation"
                    :conversation-name="$conversationName"
                    :conversation-image-url="$getConversationImageUrl($conversation)"
                    :get-default-conversation-image-data="$getDefaultConversationImageData"
                />

                <h2 class="fi-converse-conversation-thread-header-heading">
                    {{ $conversationName }}
                </h2>
            </div>

            @if (count($headerActions))
                <div class="fi-converse-conversation-thread-header-actions">
                    @foreach ($headerActions as $action)
                        {{ $action }}
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <div
        @if ($hasConversation)
            x-ref="conversationThreadContent"
            wire:key="fi-converse-conversation-thread-content-{{ $id }}-{{ $key }}-{{ $conversationKey }}"
            x-init="
                scrollToBottom();

                setTimeout(() => scrollToBottom({behaviour: 'smooth'}), 100);
            "
        @endif

        @class([
            "fi-converse-conversation-thread-content",
            "fi-converse-relative" => $canUploadFileAttachments
        ])
    >
        @if ($canUploadFileAttachments)
            <div
                x-cloak
                x-show="fileAttachmentUploadValidationMessage"
                class="fi-converse-conversation-thread-upload-validation-message-container"
            >
                <p x-text="fileAttachmentUploadValidationMessage"></p>
            </div>
        @endif

        @if ($renderedMessagesCount = count($messages))
            @if ($renderedMessagesCount < $totalMessagesCount)
                <div
                    x-cloak
                    x-show="isLoadingMoreMessages"
                    class="fi-converse-conversation-thread-messages-loading-indicator"
                >
                    {{ \Filament\Support\generate_loading_indicator_html() }}
                </div>

                <div x-intersect="loadMoreMessages()" aria-hidden="true"></div>
            @endif

            @foreach ($messages as $message)
                    @php
                        $messageAuthor = $message->author->participant;
                        $messageAuthorName = $messageAuthor->getAttributeValue($messageAuthor::getFilamentNameAttribute());

                        $messageTimestamp = $message->created_at;
                        $formattedMessageTimestamp = $formatMessageTimestamp($messageTimestamp, $message);

                        $isAuthoredByAuthenticatedUser = $messageAuthor->getKey() === auth()->id();
                    @endphp

                    <div
                        @class([
                            'fi-converse-conversation-thread-message-container',
                            'fi-converse-conversation-thread-message-container-reversed' => $isAuthoredByAuthenticatedUser,
                        ])
                    >
                        @if ((! $previousMessageTimestamp || $previousMessageTimestamp->addSeconds($messageTimestampGroupingInterval)->lt($messageTimestamp)) && filled($formattedMessageTimestamp))
                            <div
                                class="fi-converse-conversation-thread-message-timestamp"
                            >
                                {{ $formattedMessageTimestamp }}
                            </div>
                        @endif

                        <div
                            class="fi-converse-conversation-thread-message-layout group"
                        >
                            @if (! $isAuthoredByAuthenticatedUser)
                                <x-filament::avatar
                                    class="fi-converse-conversation-thread-message-avatar"
                                    :src="filament()->getUserAvatarUrl($messageAuthor)"
                                    :alt="$messageAuthorName"
                                    size="md"
                                />
                            @endif

                            <div
                                class="fi-converse-conversation-thread-message-content"
                            >
                                @if (! $isAuthoredByAuthenticatedUser)
                                    <div
                                        class="fi-converse-conversation-thread-message-author-name"
                                    >
                                        {{ $messageAuthorName }}
                                    </div>
                                @endif

                                <div
                                    class="fi-converse-conversation-thread-message-body"
                                >
                                    @php
                                        $hasMessageContent = filled($message->content);
                                    @endphp

                                    <div
                                        {{ (new ComponentAttributeBag())
                                                ->color(ConversationMessageComponent::class, $getMessageColor($message))
                                                ->class([
                                                    "fi-converse-conversation-thread-message",
                                                ]) }}
                                    >
                                        @if ($hasMessageContent)
                                            <p>
                                                {{ $message->content }}
                                            </p>
                                        @endif

                                        @if (count($message->attachments))
                                            @php
                                                $attachmentData = $getMessageAttachmentData($message);

                                                $hasOnlyImageAttachments = collect($attachmentData)
                                                    ->every(static fn(array $data) => $data['hasImageMimeType'] && $data['shouldShowOnlyMessageImageAttachment']);
                                            @endphp
                                            <div
                                                @class([
                                                    "fi-converse-conversation-thread-message-attachments",
                                                    "fi-converse-conversation-thread-message-attachments-with-generic-attachments" => ! $hasOnlyImageAttachments,
                                                    "fi-converse-conversation-thread-message-attachments-without-message-content" => ! $hasMessageContent
                                                ])
                                            >
                                                @foreach ($attachmentData as $attachmentPath => $data)
                                                    @php
                                                        $attachmentOriginalName = $data['attachmentOriginalName'];
                                                        $attachmentMimeType = $data['attachmentMimeType'];
                                                        $hasImageMimeType = $data['hasImageMimeType'];
                                                    @endphp

                                                    <x-filament-converse::conversation-attachment
                                                        :has-image-mime-type="$hasImageMimeType"
                                                        :file-attachment-name="$getMessageFileAttachmentName($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message)"
                                                        :file-attachment-toolbar="$getMessageFileAttachmentToolbar($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message)"
                                                        :should-show-only-image-attachment="$data['shouldShowOnlyMessageImageAttachment']"
                                                        :file-attachment-url="$hasImageMimeType ? $getFileAttachmentUrl($attachmentPath) : null"
                                                        :should-preview-image-attachment="$shouldPreviewMessageImageAttachment($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message)"
                                                        :file-attachment-icon="$getMessageFileAttachmentIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message)"
                                                        :mime-type-badge-label="$getMessageFileAttachmentMimeTypeBadgeLabel($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message)"
                                                        :mime-type-badge-icon="$getMessageFileAttachmentMimeTypeBadgeIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message)"
                                                        :mime-type-badge-color="$getMessageFileAttachmentMimeTypeBadgeColor($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message)"
                                                        :image-attachment-container-extra-attributes-bag="
                                                    (new ComponentAttributeBag())
                                                        ->class(['fi-converse-image-attachment-container-grid'])
                                                "
                                                        :generic-attachment-container-extra-attributes-bag="
                                                    (new ComponentAttributeBag())
                                                        ->class(['fi-converse-generic-attachment-container-grid'])
                                                "
                                                    />
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    @php
                                        $filteredMessageActions = array_filter(
                                            $messageActions,
                                            static function (Action | ActionGroup $action) use ($message) {
                                                $action->record($message)->arguments(['record' => $message->getKey()]);

                                                return $action->isVisible();
                                            }
                                        )
                                    @endphp

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
                    </div>
                    @php
                        $previousMessageTimestamp = $messageTimestamp;
                    @endphp
            @endforeach

            <div x-ref="conversationThreadContentEndMarker" aria-hidden="true"></div>
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
    </div>

    @if ($hasConversation)
        <x-dynamic-component
            :component="$fieldWrapperView"
            :field="$field"
            class="fi-converse-conversation-thread-footer"
        >
            @if ($isDisabled)
                <div
                    id="{{ $id }}"
                    class="fi-converse-conversation-thread-message-input fi-fo-markdown-editor fi-disabled fi-prose"
                >
                    {!! str($getState())->markdown($commonMarkOptions, $commonMarkExtensions)->sanitizeHtml() !!}
                </div>
            @else
                <x-filament::input.wrapper
                    :valid="! $errors->has($statePath)"
                    :attributes="
                        \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
                        ->class(['fi-converse-conversation-thread-message-input fi-fo-markdown-editor'])
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
                                        {{ Icon::make(\Filament\Support\Icons\Heroicon::OutlinedPhoto)->color('gray')->extraAttributes(['class' => "fi-size-2xl"]) }}
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

                            @foreach (array_reverse($uploadedFileAttachments) as $fileAttachment)
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
                                    file-attachment-remove-handler="$wire.removeUpload('componentFileAttachments.{{ $statePath }}', '{{ $fileAttachment->getFilename() }}')"
                                    :generic-attachment-container-extra-attributes-bag="
                                        (new ComponentAttributeBag())
                                            ->class(['fi-converse-attachment-adaptable-width'])
                                    "
                                />
                            @endforeach
                        </div>
                    @endif

                    <div
                        aria-labelledby="{{ $id }}-label"
                        id="{{ $id }}"
                        role="group"
                        x-load
                        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('markdown-editor', 'filament/forms') }}"
                        x-data="markdownEditorFormComponent({
                                    canAttachFiles: false,
                                    isLiveDebounced: @js($isLiveDebounced()),
                                    isLiveOnBlur: @js($isLiveOnBlur()),
                                    liveDebounce: @js($getNormalizedLiveDebounce()),
                                    maxHeight: @js($getMaxHeight()),
                                    minHeight: @js($getMinHeight()),
                                    placeholder: @js($getPlaceholder()),
                                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
                                    toolbarButtons: @js($getToolbarButtons()),
                                    translations: @js(__('filament-forms::components.markdown_editor')),
                                })"
                        wire:ignore
                        {{ $getExtraAlpineAttributeBag() }}
                    >
                        <textarea x-ref="editor" x-cloak></textarea>
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
            @endif
        </x-dynamic-component>
    @endif
</div>
