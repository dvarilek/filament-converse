@props([
    'hasImageMimeType',
    'fileAttachmentName',
    'fileAttachmentToolbar',
    'shouldShowOnlyUploadedImageAttachment',
    'shouldPreviewImageAttachment',
    'fileAttachmentImageUrl',
    'fileAttachmentIcon',
    'mimeTypeBadgeLabel',
    'mimeTypeBadgeIcon',
    'mimeTypeBadgeColor',
    'isRemovable' => false,
    'fileAttachmentRemoveHandler' => null,
    'isDownloadable' => false,
    'fileAttachmentDownloadHandler' => null,
    'imageAttachmentContainerExtraAttributesBag' => new \Illuminate\View\ComponentAttributeBag,
    'genericAttachmentContainerExtraAttributesBag' => new \Illuminate\View\ComponentAttributeBag,
])

@php
    use Filament\Schemas\Components\Icon;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Contracts\Support\Htmlable;
@endphp

@if ($hasImageMimeType && $shouldShowOnlyUploadedImageAttachment)
    <div
        {{
            $imageAttachmentContainerExtraAttributesBag
                ->class(['fi-converse-image-attachment-container'])
        }}
    >
        <img
            src="{{ $fileAttachmentImageUrl }}"
            alt="{{ $fileAttachmentName }}"
            draggable="false"
            class="fi-converse-image-attachment"
            @if (filled($fileAttachmentToolbar))
                x-tooltip="{
                    content: @js($fileAttachmentToolbar),
                    theme: $store.theme,
                    allowHTML: @js($fileAttachmentToolbar instanceof Htmlable),
                }"
            @endif
        />
        @if ($isRemovable)
            <x-filament::icon-button
                color="gray"
                :icon="Heroicon::OutlinedXMark"
                icon-size="sm"
                class="fi-converse-image-attachment-remove-button"
                :label="__('filament-converse::conversation-thread.attachments.remove-button-label')"
                :x-on:click="$fileAttachmentRemoveHandler"
            />
        @endif
    </div>
@else
    <div
        {{
            $genericAttachmentContainerExtraAttributesBag
                ->class(['fi-converse-generic-attachment-container'])
        }}
    >
        @php
            $hasMimeTypeBadge = filled($mimeTypeBadgeLabel);
        @endphp

        @if ($hasImageMimeType && $shouldPreviewImageAttachment)
            <img
                src="{{ $fileAttachmentImageUrl }}"
                alt="{{ $fileAttachmentName }}"
                draggable="false"
                class="fi-converse-generic-attachment-image"
            />
        @elseif (filled($fileAttachmentIcon))
            @if (! $fileAttachmentIcon instanceof Icon)
                {{ $fileAttachmentIcon }}
            @else
                <div class="fi-converse-attachment-icon">
                    {{ $fileAttachmentIcon }}
                </div>
            @endif
        @endif
        <div
            @class([
                'fi-converse-attachment-information-container',
                'fi-converse-attachment-has-mime-type-name' => $hasMimeTypeBadge,
            ])
            @if (filled($fileAttachmentToolbar))
                x-tooltip="{
                    content: @js($fileAttachmentToolbar),
                    theme: $store.theme,
                    allowHTML: @js($fileAttachmentToolbar instanceof Htmlable),
                }"
            @endif
        >
            <p class="fi-converse-attachment-name">
                {{ $fileAttachmentName }}
            </p>

            @if ($hasMimeTypeBadge)
                <x-filament::badge
                    size="sm"
                    :color="$mimeTypeBadgeColor"
                    :icon="$mimeTypeBadgeIcon"
                    class="fi-converse-attachment-mime-type-badge"
                >
                    {{ $mimeTypeBadgeLabel }}
                </x-filament::badge>
            @endif
        </div>
        @if ($isRemovable)
            <x-filament::icon-button
                color="gray"
                :icon="Heroicon::OutlinedXMark"
                icon-size="sm"
                :label="__('filament-converse::conversation-thread.attachments.remove-button-label')"
                :x-on:click="$fileAttachmentRemoveHandler"
            />
        @endif
    </div>
@endif
