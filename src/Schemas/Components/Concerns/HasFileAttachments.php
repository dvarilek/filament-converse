<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\HasFileAttachments as HasBaseFileAttachments;
use Filament\Schemas\Components\Icon;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait HasFileAttachments
{
    use HasBaseFileAttachments;

    protected int | Closure | null $maxFileAttachments = null;

    protected string | array | Closure | null $attachmentModalIconColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $attachmentModalIcon = null;

    protected string | Htmlable | Closure | null $attachmentModalHeading = null;

    protected string | Htmlable | Closure | null $attachmentModalDescription = null;

    protected string | Closure | null $attachmentsAcceptedFileTypesValidationMessage = null;

    protected string | Closure | null $attachmentsMaxFileSizeValidationMessage = null;

    protected string | Closure | null $maxFileAttachmentsValidationMessage = null;

    protected bool | Closure $shouldShowOnlyUploadedImageAttachment = true;

    protected bool | Closure $shouldPreviewUploadedImageAttachment = true;

    // ------
    protected ?Closure $uploadedFileAttachmentName = null;

    protected ?Closure $uploadedFileAttachmentToolbar = null;

    protected string | BackedEnum | Htmlable | Closure | null $uploadedFileAttachmentIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $uploadedFileAttachmentIconColor
     */
    protected string | array | Closure | null $uploadedFileAttachmentIconColor = 'primary';

    protected ?Closure $uploadedFileAttachmentMimeTypeBadgeLabel = null;

    protected string | BackedEnum | Closure | null $uploadedFileAttachmentMimeTypeBadgeIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $uploadedFileAttachmentMimeTypeBadgeColor
     */
    protected string | array | Closure | null $uploadedFileAttachmentMimeTypeBadgeColor = 'gray';

    protected ?Closure $modifyUploadAttachmentActionUsing = null;

    public function maxFileAttachments(int | Closure | null $maxFileAttachments): static
    {
        $this->maxFileAttachments = $maxFileAttachments;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function attachmentModalIconColor(string | array | Closure | null $color): static
    {
        $this->attachmentModalIconColor = $color;

        return $this;
    }

    public function attachmentModalIcon(string | BackedEnum | Htmlable | Closure | null $icon): static
    {
        $this->attachmentModalIcon = filled($icon) ? $icon : false;

        return $this;
    }

    public function attachmentModalHeading(string | Htmlable | Closure | null $heading): static
    {
        $this->attachmentModalHeading = $heading;

        return $this;
    }

    public function attachmentModalDescription(string | Htmlable | Closure | null $description): static
    {
        $this->attachmentModalDescription = $description;

        return $this;
    }

    public function attachmentsAcceptedFileTypesValidationMessage(string | Closure | null $validationMessage): static
    {
        $this->attachmentsAcceptedFileTypesValidationMessage = $validationMessage;

        return $this;
    }

    public function attachmentsMaxFileSizeValidationMessage(string | Closure | null $validationMessage): static
    {
        $this->attachmentsMaxFileSizeValidationMessage = $validationMessage;

        return $this;
    }

    public function maxFileAttachmentsValidationMessage(string | Closure | null $validationMessage): static
    {
        $this->maxFileAttachmentsValidationMessage = $validationMessage;

        return $this;
    }

    public function showOnlyUploadedImageAttachment(bool | Closure $condition = true): static
    {
        $this->shouldShowOnlyUploadedImageAttachment = $condition;

        return $this;
    }

    public function previewUploadedImageAttachment(bool | Closure $condition = true): static
    {
        $this->shouldPreviewUploadedImageAttachment = $condition;

        return $this;
    }

    public function uploadedFileAttachmentName(?Closure $callback = null): static
    {
        $this->uploadedFileAttachmentName = $callback;

        return $this;
    }

    public function uploadedFileAttachmentToolbar(?Closure $callback = null): static
    {
        $this->uploadedFileAttachmentToolbar = $callback;

        return $this;
    }

    public function uploadedFileAttachmentIcon(string | BackedEnum | Htmlable | Closure | null $icon = null): static
    {
        $this->uploadedFileAttachmentIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function uploadedFileAttachmentIconColor(string | array | Closure | null $color = null): static
    {
        $this->uploadedFileAttachmentIconColor = $color;

        return $this;
    }

    public function uploadedFileAttachmentMimeTypeBadgeLabel(?Closure $callback = null): static
    {
        $this->uploadedFileAttachmentMimeTypeBadgeLabel = $callback;

        return $this;
    }

    public function uploadedFileAttachmentMimeTypeBadgeIcon(string | BackedEnum | Closure | null $icon = null): static
    {
        $this->uploadedFileAttachmentMimeTypeBadgeIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function uploadedFileAttachmentMimeTypeBadgeColor(string | array | Closure | null $color = null): static
    {
        $this->fileAttachmentMimeTypeBadgeColor = $color;

        return $this;
    }

    public function uploadAttachmentAction(?Closure $callback): static
    {
        $this->modifyUploadAttachmentActionUsing = $callback;

        return $this;
    }

    public function getMaxFileAttachments(): ?int
    {
        return $this->evaluate($this->maxFileAttachments);
    }

    /**
     * @return string | array<string>
     */
    public function getAttachmentModalIconColor(): string | array
    {
        return $this->evaluate($this->attachmentModalIconColor) ?? 'primary';
    }

    public function getAttachmentModalIcon(): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate($this->attachmentModalIcon) ?? Heroicon::PaperClip;

        // https://github.com/filamentphp/filament/pull/13512
        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if ($icon === false) {
            return null;
        }

        return $icon;
    }

    public function getAttachmentModalHeading(): string | Htmlable
    {
        return $this->evaluate($this->attachmentModalHeading) ?? __('filament-converse::conversation-thread.attachment-modal.heading');
    }

    public function getAttachmentModalDescription(): string | Htmlable
    {
        return $this->evaluate($this->attachmentModalDescription);
    }

    public function getAttachmentsAcceptedFileTypesValidationMessage(array $fileAttachmentsAcceptedFileTypes): string
    {
        return $this->evaluate($this->attachmentsAcceptedFileTypesValidationMessage, [
            'fileAttachmentsAcceptedFileTypes' => $fileAttachmentsAcceptedFileTypes,
            'acceptedFileTypes' => $fileAttachmentsAcceptedFileTypes,
        ]) ?? __('filament-converse::conversation-thread.attachment-modal.file-attachments-accepted-file-types-validation-message', ['values' => implode(', ', $fileAttachmentsAcceptedFileTypes)]);
    }

    public function getAttachmentsMaxFileSizeValidationMessage(string $fileAttachmentsMaxSize): string
    {
        return $this->evaluate($this->attachmentsMaxFileSizeValidationMessage, [
            'fileAttachmentMaxSize' => $fileAttachmentsMaxSize,
            'maxSize' => $fileAttachmentsMaxSize,
        ]) ?? __('filament-converse::conversation-thread.attachment-modal.file-attachments-max-size-validation-message', ['max' => $fileAttachmentsMaxSize]);
    }

    public function getMaxFileAttachmentsValidationMessage(?int $maxFileAttachments = null): ?string
    {
        if ($maxFileAttachments === null) {
            return null;
        }

        return $this->evaluate($this->maxFileAttachmentsValidationMessage, [
            'maxFileAttachments' => $maxFileAttachments,
            'maxFiles' => $maxFileAttachments,
        ]) ?? trans_choice(
            'filament-converse::conversation-thread.attachment-modal.max-file-attachments-validation-message',
            $maxFileAttachments,
            ['count' => $maxFileAttachments],
        );
    }

    public function shouldShowOnlyUploadedImageAttachment(TemporaryUploadedFile $attachment): bool
    {
        return (bool) $this->evaluate($this->shouldShowOnlyUploadedImageAttachment, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function shouldPreviewUploadedImageAttachment(TemporaryUploadedFile $attachment): bool
    {
        return (bool) $this->evaluate($this->shouldPreviewUploadedImageAttachment, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getUploadedFileAttachmentName(TemporaryUploadedFile $attachment): string | Htmlable | null
    {
        return $this->evaluate($this->uploadedFileAttachmentName, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getUploadedFileAttachmentToolbar(TemporaryUploadedFile $attachment): string | Htmlable | null
    {
        return $this->evaluate($this->uploadedFileAttachmentToolbar, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getUploadedFileAttachmentIcon(TemporaryUploadedFile $attachment): Htmlable | Icon | null
    {
        $icon = $this->evaluate($this->uploadedFileAttachmentIcon, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);

        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if (is_string($icon) || $icon instanceof BackedEnum) {
            $icon = Icon::make($icon);
        }

        if ($icon instanceof Icon) {
            $icon->color($this->getUploadedFileAttachmentIconColor($attachment));
        }

        return $icon;
    }

    public function getUploadedFileAttachmentIconColor(TemporaryUploadedFile $attachment): string | array
    {
        return $this->evaluate($this->uploadedFileAttachmentIconColor, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]) ?? 'primary';
    }

    public function getUploadedFileAttachmentMimeTypeBadgeLabel(TemporaryUploadedFile $attachment): string | Htmlable | null
    {
        return $this->evaluate($this->uploadedFileAttachmentMimeTypeBadgeLabel, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getUploadedFileAttachmentMimeTypeBadgeIcon(TemporaryUploadedFile $attachment): string | BackedEnum | null
    {
        return $this->evaluate($this->uploadedFileAttachmentMimeTypeBadgeIcon, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getUploadedFileAttachmentMimeTypeBadgeColor(TemporaryUploadedFile $attachment): string | array
    {
        return $this->evaluate($this->uploadedFileAttachmentMimeTypeBadgeColor, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]) ?? 'gray';
    }

    public function isImageMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    protected function getUploadAttachmentAction(): Action
    {
        $action = Action::make('uploadAttachment')
            ->label(__('filament-converse::conversation-thread.footer-actions.upload-attachment-label'))
            ->iconButton()
            ->iconSize(IconSize::Large)
            ->icon(Heroicon::PaperClip)
            ->alpineClickHandler('$refs.fileInput.click()');

        if ($this->modifyUploadAttachmentActionUsing) {
            $action = $this->evaluate($this->modifyUploadAttachmentActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    /**
     * @return list<TemporaryUploadedFile>
     */
    public function getUploadedFileAttachments(): array
    {
        return Arr::wrap(data_get($this->getLivewire(), 'componentFileAttachments.' . $this->getStatepath())) ?? [];
    }

    /**
     * @return list<TemporaryUploadedFile>
     */
    public function getValidUploadedFileAttachments(): array
    {
        $attachments = $this->getUploadedFileAttachments();

        if (count($attachments) === 0) {
            return [];
        }

        $maxFileAttachments = $this->getMaxFileAttachments();

        if ($maxFileAttachments && count($attachments) > $maxFileAttachments) {
            return [];
        }

        $maxSize = $this->getFileAttachmentsMaxSize();
        $acceptedFileTypes = $this->getFileAttachmentsAcceptedFileTypes();

        try {
            foreach ($attachments as $attachment) {
                Validator::validate(
                    ['file' => $attachment],
                    rules: [
                        'file' => [
                            'file',
                            ...($maxSize ? ["max:{$maxSize}"] : []),
                            ...($acceptedFileTypes ? ['mimetypes:' . implode(',', $acceptedFileTypes)] : []),
                        ],
                    ],
                );
            }
        } catch (ValidationException $exception) {
            return [];
        }

        return $attachments;
    }
}
