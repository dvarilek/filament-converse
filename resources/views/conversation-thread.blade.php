@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Illuminate\Support\Collection;
    use Illuminate\View\ComponentAttributeBag;
    use Filament\Support\View\Components\ModalComponent\IconComponent;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
    use Filament\Schemas\Components\Icon;

    $id = $getId();
    $fieldWrapperView = $getFieldWrapperView();
    $extraAttributeBag = $getExtraAttributeBag();
    $key = $getKey();
    $statePath = $getStatePath();

    /* @var Conversation | null $conversation */
    $conversation = $getActiveConversation();
    $hasConversation = filled($conversation);

    $isDisabled = $isDisabled();
    $hasFileAttachments = $hasFileAttachments();
    $canUploadFileAttachments = $hasConversation && $hasFileAttachments && ! $isDisabled;
    $uploadedFileAttachments = $canUploadFileAttachments ? $getUploadedFileAttachments() : [];

    /* @var Collection<int, Message> $messages */
    $messages = $getMessagesQuery()?->get()?->reverse() ?? [];

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
            statePath: @js($statePath),
            fileAttachmentAcceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),
            fileAttachmentMaxSize: @js($fileAttachmentsMaxSize),
            maxFileAttachments: @js($maxFileAttachments),
            fileAttachmentsAcceptedFileTypesValidationMessage: @js($getAttachmentsAcceptedFileTypesValidationMessage($fileAttachmentsAcceptedFileTypes)),
            fileAttachmentsMaxSizeValidationMessage: @js($getAttachmentsMaxFileSizeValidationMessage($fileAttachmentsMaxSize)),
            maxFileAttachmentsValidationMessage: @js($getMaxFileAttachmentsValidationMessage($maxFileAttachments)),
            $wire
        })"
        x-bind:class="{'fi-converse-highlight-conversation-thread': isDraggingFileAttachment}"
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
        >
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
            wire:key="fi-converse-conversation-thread-content-{{ $id }}-{{ $key }}-{{ $conversation->getKey() }}-{{ count($messages) }}"
            x-init="scrollToBottom()"
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

        @forelse ($messages as $message)
            @php
                $messageAuthor = $message->author->participant;
                $messageAuthorName = $messageAuthor->getAttributeValue($messageAuthor::getFilamentNameAttribute());

                $isAuthoredByAuthenticatedUser = $messageAuthor->getKey() === auth()->id();
            @endphp

            <div
                @class([
                    'fi-converse-conversation-thread-message-container-reversed' => $isAuthoredByAuthenticatedUser,
                    'fi-converse-conversation-thread-message-container group',
                ])
            >
                @if (! $isAuthoredByAuthenticatedUser)
                    <x-filament::avatar
                        class="fi-converse-conversation-thread-message-avatar"
                        :src="filament()->getUserAvatarUrl($messageAuthor)"
                        :alt="$messageAuthorName"
                        size="md"
                    />
                @endif

                <div class="fi-converse-conversation-thread-message-content">
                    <div
                        class="fi-converse-conversation-thread-message-details"
                    >
                        @if (! $isAuthoredByAuthenticatedUser)
                            <div
                                class="fi-converse-conversation-thread-message-author"
                            >
                                {{ $messageAuthorName }}
                            </div>
                        @endif

                        <div
                            class="fi-converse-conversation-thread-message-time"
                        >
                            {{ $message->created_at }}
                        </div>
                    </div>

                    <div class="fi-converse-conversation-thread-message-body">
                        <div class="fi-converse-conversation-thread-message">
                            {{ $message->content }}
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
        @empty
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
        @endforelse

        @if ($hasConversation)
            <div x-ref="messageBoxEndMarker" style="height: 0"></div>
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
                    {!! str($getState())->markdown($getCommonMarkOptions(), $getCommonMarkExtensions())->sanitizeHtml() !!}
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
                            x-bind:class="{'fi-converse-attachment-area-has-content': isUploadingFileAttachment() || @js(count($uploadedFileAttachments) > 0) }"
                        >
                            <template x-for="file in uploadingFileAttachments.reverse()">
                                <div x-bind:class="file.type.startsWith('image/') ? 'fi-converse-image-attachment-container' : 'fi-converse-generic-attachment-container'">
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
                                        <div class="fi-converse-attachment-name-skeleton"></div>
                                        <div class="fi-converse-attachment-mime-type-badge-skeleton"></div>
                                    </div>
                                </div>
                            </template>

                            @foreach (array_reverse($uploadedFileAttachments) as $fileAttachment)
                                @php
                                    /* @var TemporaryUploadedFile $fileAttachment */
                                    $fileAttachmentName = $getFileAttachmentName($fileAttachment);
                                    $isImageMimeType = str_starts_with($fileAttachment->getMimeType(), 'image/');

                                    $fileAttachmentToolbar = $getFileAttachmentToolbar($fileAttachment);
                                    $hasToolbar = filled($fileAttachmentToolbar);
                                @endphp

                                @if ($isImageMimeType && $shouldHideAttachmentDetailsForImage($fileAttachment))
                                    <div class="fi-converse-image-attachment-container">
                                        <img
                                            src="{{ $fileAttachment->temporaryUrl() }}"
                                            alt="{{ $fileAttachmentName }}"
                                            draggable="false"
                                            class="fi-converse-image-attachment"
                                            @if ($hasToolbar)
                                                x-tooltip="{
                                                    content: @js($fileAttachmentName),
                                                    theme: $store.theme,
                                                    allowHTML: @js($fileAttachmentName instanceof Htmlable),
                                                }"
                                            @endif
                                        />
                                        <x-filament::icon-button
                                            color="gray"
                                            :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                                            icon-size="sm"
                                            class="fi-converse-image-attachment-remove-button"
                                            :label="__('filament-converse::conversation-thread.attachment-area.remove-button-label')"
                                            x-on:click="$wire.removeUpload('componentFileAttachments.{{ $statePath }}', '{{ $fileAttachment->getFilename() }}')"
                                        />
                                    </div>
                                @else
                                    <div class="fi-converse-generic-attachment-container">
                                        @php
                                            $mimeTypeBadgeLabel = $getFileAttachmentMimeTypeBadgeLabel($fileAttachment);
                                            $hasMimeTypeBadge = filled($mimeTypeBadgeLabel);
                                        @endphp

                                        @if ($isImageMimeType && $shouldPreviewImageAttachment($fileAttachment))
                                            <img
                                                src="{{ $fileAttachment->temporaryUrl() }}"
                                                alt="{{ $fileAttachmentName }}"
                                                draggable="false"
                                                class="fi-converse-generic-attachment-image"
                                            />
                                        @elseif (filled($attachmentIcon = $getFileAttachmentIcon($fileAttachment)))
                                            @if (! $attachmentIcon instanceof Icon)
                                                {{ $attachmentIcon }}
                                            @else
                                                <div
                                                    class="fi-converse-attachment-icon"
                                                >
                                                    {{ $attachmentIcon }}
                                                </div>
                                            @endif
                                        @endif
                                        <div
                                            @class([
                                                'fi-converse-attachment-information-container',
                                                'fi-converse-attachment-has-mime-type-name' => $hasMimeTypeBadge,
                                            ])
                                            @if ($hasToolbar)
                                                x-tooltip="{
                                                    content: @js($fileAttachmentName),
                                                    theme: $store.theme,
                                                    allowHTML: @js($fileAttachmentName instanceof Htmlable),
                                                }"
                                            @endif
                                        >
                                            <p class="fi-converse-attachment-name">
                                                {{ $fileAttachmentName }}
                                            </p>

                                            @if ($hasMimeTypeBadge)
                                                <x-filament::badge
                                                    size="sm"
                                                    :color="$getFileAttachmentMimeTypeBadgeColor($fileAttachment)"
                                                    :icon="$getFileAttachmentMimeTypeBadgeIcon($fileAttachment)"
                                                    class="fi-converse-attachment-mime-type-badge"
                                                >
                                                    {{ $mimeTypeBadgeLabel }}
                                                </x-filament::badge>
                                            @endif
                                        </div>
                                        <x-filament::icon-button
                                            color="gray"
                                            :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                                            icon-size="sm"
                                            :label="__('filament-converse::conversation-thread.attachment-area.remove-button-label')"
                                            x-on:click="$wire.removeUpload('componentFileAttachments.{{ $statePath }}', '{{ $fileAttachment->getFilename() }}')"
                                        />
                                    </div>
                                @endif
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
                                    <div class="fi-converse-message-input-footer-left-actions">
                                        {{ $uploadAttachmentAction }}
                                    </div>
                                @endif
                                @if ($sendMessageAction)
                                    <div class="fi-converse-message-input-footer-right-actions">
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
