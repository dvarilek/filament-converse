@php
    use Filament\Schemas\Components\Icon;
    use Illuminate\Contracts\Support\Htmlable;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    $id = $getId();
    $fieldWrapperView = $getFieldWrapperView();
    $extraAttributeBag = $getExtraAttributeBag();
    $key = $getKey();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    @if ($isDisabled())
        <div
            id="{{ $id }}"
            class="fi-converse-message-input fi-fo-markdown-editor fi-disabled fi-prose"
        >
            {!! str($getState())->markdown($getCommonMarkOptions(), $getCommonMarkExtensions())->sanitizeHtml() !!}
        </div>
    @else
        <x-filament::input.wrapper
            :valid="! $errors->has($statePath)"
            :attributes="
                \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
                    ->class(['fi-converse-message-input fi-fo-markdown-editor'])
            "
        >
            @if ($hasFileAttachments)
                <div
                    class="fi-converse-attachment-area"
                    x-bind:class="{'fi-converse-attachment-area-has-content': isUploadingFileAttachment() || {{ count($uploadedFileAttachments) }} > 0}"
                >
                    <template x-for="file in uploadingFileAttachments">
                        <div>
                            <div
                                x-cloak
                                x-show="file.type.startsWith('image/')"
                                class="fi-converse-attachment-image-container fi-converse-attachment-skeleton"
                            >
                                Image
                            </div>

                            <div
                                x-cloak
                                x-show="! file.type.startsWith('image/')"
                                class="fi-converse-attachment-item-container fi-converse-attachment-skeleton"
                            >
                                Content
                            </div>
                        </div>
                    </template>

                    @foreach (array_reverse($uploadedFileAttachments) as $fileAttachment)
                        @php
                            /* @var TemporaryUploadedFile $fileAttachment */
                            $mimeType = $fileAttachment->getMimeType();
                            $isImageMimeType = str_starts_with($mimeType, 'image/');

                            $shouldPreviewOnlyImage = true;
                            $shouldPreviewImage = true;
                            $hasToolbar = false;

                            $attachmentOriginalName = $formatFileAttachmentName($fileAttachment->getClientOriginalName());
                        @endphp

                        @if ($isImageMimeType && $shouldPreviewOnlyImage)
                            <div class="fi-converse-attachment-image-container">
                                <img
                                    src="{{ $fileAttachment->temporaryUrl() }}"
                                    alt="{{ $attachmentOriginalName }}"
                                    draggable="false"
                                    class="fi-converse-attachment-image"
                                    @if ($hasToolbar)
                                        x-tooltip="{
                                            content: @js($attachmentOriginalName),
                                            theme: $store.theme,
                                            allowHTML: @js($attachmentOriginalName instanceof Htmlable),
                                        }"
                                    @endif
                                />
                                <x-filament::icon-button
                                    color="gray"
                                    :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                                    icon-size="sm"
                                    class="fi-converse-attachment-image-remove-button"
                                    :label="__('filament-converse::conversation-thread.attachment-area.remove-button-label')"
                                    x-on:click="$wire.removeUpload('componentFileAttachments.{{ $conversationThreadStatePath }}', '{{ $fileAttachment->getFilename() }}')"
                                />
                            </div>
                        @else
                            @php
                                $formattedMimeType = $getAttachmentFormattedMimeType($mimeType);
                                $hasFormattedMimeType = filled($formattedMimeType);
                            @endphp

                            <div class="fi-converse-attachment-item-container">
                                @if ($isImageMimeType && $shouldPreviewImage)
                                    <img
                                        src="{{ $fileAttachment->temporaryUrl() }}"
                                        alt="{{ $attachmentOriginalName }}"
                                        draggable="false"
                                        class="fi-converse-attachment-item-image"
                                    />
                                @elseif (filled($attachmentIcon = $getAttachmentIcon($mimeType)))
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
                                        'fi-converse-attachment-has-formatted-mime-type-name' => $hasFormattedMimeType,
                                    ])
                                    @if ($hasToolbar)
                                        x-tooltip="{
                                            content: @js($attachmentOriginalName),
                                            theme: $store.theme,
                                            allowHTML: @js($attachmentOriginalName instanceof Htmlable),
                                        }"
                                    @endif
                                >
                                    <p class="fi-converse-attachment-name">
                                        {{ $attachmentOriginalName }}
                                    </p>

                                    @if ($hasFormattedMimeType)
                                        @if ($formattedMimeType instanceof Htmlable)
                                            {{ $formattedMimeType }}
                                        @else
                                            <x-filament::badge
                                                size="xd"
                                                color="gray"
                                                class="fi-converse-attachment-formatted-mime-type-badge"
                                            >
                                                {{ $formattedMimeType }}
                                            </x-filament::badge>
                                        @endif
                                    @endif
                                </div>
                                <x-filament::icon-button
                                    color="gray"
                                    :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                                    icon-size="sm"
                                    :label="__('filament-converse::conversation-thread.attachment-area.remove-button-label')"
                                    x-on:click="$wire.removeUpload('componentFileAttachments.{{ $conversationThreadStatePath }}', '{{ $fileAttachment->getFilename() }}')"
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
            <div class="fi-converse-message-input-footer">
                <div class="fi-converse-message-input-footer-left-actions">
                    <x-filament::icon-button
                        icon="heroicon-m-plus"
                        x-on:click="$refs.fileInput.click()"
                    />
                    <x-filament::icon-button icon="heroicon-m-paper-clip" />
                </div>

                <div class="fi-converse-message-input-footer-right-actions">
                    <x-filament::icon-button icon="heroicon-m-paper-airplane" />
                </div>
            </div>
        </x-filament::input.wrapper>
    @endif
</x-dynamic-component>
