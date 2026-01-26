<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use BackedEnum;
use Filament\Forms\Components\Concerns\HasFileAttachments as BaseHasFileAttachments;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Closure;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AttachmentArea extends Field
{
    use Concerns\HasFileAttachments;
    use BaseHasFileAttachments;
    use HasKey;

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::attachment-area';

    protected  string | Closure | null $attachmentDropZoneRef = 'attachmentDropZoneRef';

    /**
     * @var string | array<string> | Closure | null
     */
    protected string | array | Closure | null $attachmentModalIconColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $attachmentModalIcon = null;

    protected string | Htmlable | Closure | null $attachmentModalHeading = null;

    protected string | Htmlable | Closure | null $attachmentModalDescription = null;

    protected int | Closure | null $maxFileAttachments = null;

    // -> add configuration methods

    protected string | Closure | null $fileAttachmentsDirectory = null;

    protected string | Closure | null $fileAttachmentsDiskName = null;

    protected ?Closure $getFileAttachmentUrlUsing = null;

    protected ?Closure $saveUploadedFileAttachmentUsing = null;

    protected string | Closure | null $fileAttachmentsVisibility = null;

    protected bool | Closure | null $hasFileAttachments = null;

    /**
     * @var array<string> | Arrayable | Closure | null
     */
    protected array | Arrayable | Closure | null $fileAttachmentsAcceptedFileTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    protected int | Closure | null $fileAttachmentsMaxSize = 12288;

    // ->

    // TODO: ->validationMessages() probably is better than having individual properties.

    protected string | Closure | null $attachmentsAcceptedFileTypesValidationMessage = null;

    protected string | Closure | null $attachmentsMaxFileSizeValidationMessage = null;

    protected string | Closure | null $maxFileAttachmentsValidationMessage = null;

    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        $this->statePath('attachment_area');

        $this->attachmentModalDescription(__('filament-converse::conversation-thread.attachment-modal.description'));

        $this->beforeStateDehydrated(static function (AttachmentArea $component): void {
            $component->saveUploadedFiles();
        }, shouldUpdateValidatedStateAfter: true);
    }

    public function attachmentDropZoneRef(string | Closure | null $ref): static
    {
        $this->attachmentDropZoneRef = $ref;

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

    public function maxFileAttachments(int | Closure | null $maxFileAttachments): static
    {
        $this->maxFileAttachments = $maxFileAttachments;

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

    public function getAttachmentDropZoneRef(): ?string
    {
        return $this->evaluate($this->attachmentDropZoneRef);
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

    public function getAttachmentModalDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->attachmentModalDescription);
    }

    public function getMaxFileAttachments(): ?int
    {
        return $this->evaluate($this->maxFileAttachments);
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

    public function getActiveConversation(): Conversation
    {
        return $this->getLivewire()->getActiveConversation();
    }

    /**
     * @return list<TemporaryUploadedFile>
     */
    public function getUploadedFileAttachments(): array
    {
        $livewire = $this->getLivewire();
        $activeConversationKey = $livewire->getActiveConversation()?->getKey();

        if (! $this->hasFileAttachments()) {
            return [];
        }

        if ($this->isDisabled()) {
            return [];
        }

        if (! $activeConversationKey) {
            return [];
        }

            return Arr::wrap(data_get($livewire, 'componentFileAttachments.' . $this->getStatePath() . ".{$activeConversationKey}")) ?? [];
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
