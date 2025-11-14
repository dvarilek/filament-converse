<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use BackedEnum;
use Closure;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\DeleteMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\EditMessageAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\HasFileAttachments;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Renderless;

class ConversationThread extends Component
{
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use HasFileAttachments;
    use HasKey;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const MESSAGE_ACTIONS_KEY = 'message_actions';

    const MESSAGE_INPUT_FIELD_KEY = 'message_input_field';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

    protected ?Closure $modifyEditConversationActionUsing = null;

    protected ?Closure $modifyEditMessageActionUsing = null;

    protected ?Closure $modifyDeleteMessageActionUsing = null;

    protected string | array | Closure | null $attachmentModalIconColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $attachmentModalIcon = null;

    protected string | Htmlable | Closure | null $attachmentModalHeading = null;

    protected string | Htmlable | Closure | null $attachmentModalDescription = null;

    protected string | Htmlable | Closure | null $attachmentsAcceptedFileTypesErrorMessage = null;

    protected string | Htmlable | Closure | null $attachmentsMaxFileSizeErrorMessage = null;

    // TODO: Look into this: + add more validation message, maybe extend from BaseFileUplaod here

    protected ?Closure $modifyMessageInputFieldUsing = null;

    protected ?Closure $afterAttachmentUploaded = null;

    protected ?Closure $afterAttachmentUploadFailed = null;

    protected ?Closure $modifyAttachmentUploadedNotification = null;

    public static function make()
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key('conversation-thread');

        $this->attachmentModalDescription(__('filament-converse::conversation-thread.attachment-modal.description'));

        $this->emptyStateHeading(__('filament-converse::conversation-thread.empty-state.heading'));

        $this->childComponents(fn () => [
            $this->getEditConversationAction(),
        ], static::HEADER_ACTIONS_KEY);

        $this->childComponents(fn () => [
            $this->getEditMessageAction(),
            $this->getDeleteMessageAction(),
        ], static::MESSAGE_ACTIONS_KEY);

        $this->childComponents(fn () => [
            $this->getMessageInputField(),
        ], static::MESSAGE_INPUT_FIELD_KEY);
    }

    public function editConversationAction(?Closure $callback): static
    {
        $this->modifyEditConversationActionUsing = $callback;

        return $this;
    }

    public function editMessageAction(?Closure $callback): static
    {
        $this->modifyEditMessageActionUsing = $callback;

        return $this;
    }

    public function deleteMessageAction(?Closure $callback): static
    {
        $this->modifyDeleteMessageActionUsing = $callback;

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

    public function attachmentsAcceptedFileTypesErrorMessage(string | Htmlable | Closure | null $errorMessage): static
    {
        $this->attachmentsAcceptedFileTypesErrorMessage = $errorMessage;

        return $this;
    }

    public function attachmentsMaxFileSizeErrorMessage(string | Htmlable | Closure | null $errorMessage): static
    {
        $this->attachmentsMaxFileSizeErrorMessage = $errorMessage;

        return $this;
    }

    public function messageInputField(?Closure $callback): static
    {
        $this->modifyMessageInputFieldUsing = $callback;

        return $this;
    }

    public function afterAttachmentUploaded(?Closure $callback): static
    {
        $this->afterAttachmentUploaded = $callback;

        return $this;
    }

    public function afterAttachmentUploadFailed(?Closure $callback): static
    {
        $this->afterAttachmentUploadFailed = $callback;

        return $this;
    }

    protected function getEditConversationAction(): Action
    {
        $action = Action::make('editConversation')
            ->iconButton()
            ->color('gray')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->size(Size::ExtraLarge)
            ->action(fn () => dd($this->getLivewire()->content->getState()));

        if ($this->modifyEditConversationActionUsing) {
            $action = $this->evaluate($this->modifyEditConversationActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getEditMessageAction(): Action
    {
        $action = EditMessageAction::make();

        if ($this->modifyEditMessageActionUsing) {
            $action = $this->evaluate($this->modifyEditMessageActionUsing, [
                'action' => $action,
            ], [
                EditMessageAction::class => $action,
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getDeleteMessageAction(): Action
    {
        $action = DeleteMessageAction::make();

        if ($this->modifyDeleteMessageActionUsing) {
            $action = $this->evaluate($this->modifyDeleteMessageActionUsing, [
                'action' => $action,
            ], [
                DeleteMessageAction::class => $action,
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
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
        $icon = $this->evaluate($this->attachmentModalIcon) ?? Heroicon::OutlinedPaperClip;

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

    public function getAttachmentsAcceptedFileTypesErrorMessage(array $fileAttachmentsAcceptedFileTypes): string | Htmlable
    {
        return $this->evaluate($this->attachmentsAcceptedFileTypesErrorMessage, [
            'fileAttachmentsAcceptedFileTypes' => $fileAttachmentsAcceptedFileTypes,
            'acceptedFileTypes' => $fileAttachmentsAcceptedFileTypes,
        ]) ?? __('filament-converse::conversation-thread.attachment-modal.file-attachments-accepted-file-types-message', ['values' => implode(', ', $fileAttachmentsAcceptedFileTypes)]);
    }

    public function getAttachmentsMaxFileSizeErrorMessage(string $fileAttachmentsMaxSize): string | Htmlable
    {
        return $this->evaluate($this->attachmentsMaxFileSizeErrorMessage, [
            'fileAttachmentMaxSize' => $fileAttachmentsMaxSize,
            'maxSize' => $fileAttachmentsMaxSize,
        ]) ?? trans_choice('filament-converse::conversation-thread.attachment-modal.max-file-size-error-message', $fileAttachmentsMaxSize, ['max' => $fileAttachmentsMaxSize]);
    }

    protected function getMessageInputField(): MessageInput
    {
        $component = MessageInput::make('message_content');

        if ($this->modifyMessageInputFieldUsing) {
            $component = $this->evaluate($this->modifyMessageInputFieldUsing, [
                'component' => $component,
            ], [
                MessageInput::class => $component,
                Component::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function callAfterAttachmentUploaded(): void
    {
        if (! $this->hasFileAttachments()) {
            return;
        }

        $this->getAfterAttachmentUploadedNotification()?->send();

        if ($this->afterAttachmentUploaded) {
            $this->evaluate($this->afterAttachmentUploaded);
        }
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function callAfterAttachmentUploadFailed(): void
    {
        if (! $this->hasFileAttachments()) {
            return;
        }

        if ($this->afterAttachmentUploadFailed) {
            $this->evaluate($this->afterAttachmentUploadFailed);
        }
    }

    protected function getAfterAttachmentUploadedNotification(): ?Notification
    {
        $notification = Notification::make();

        if ($this->modifyAttachmentUploadedNotification) {
            $notification = $this->evaluate($this->modifyAttachmentUploadedNotification, [
                'notification' => $notification,
            ], [
                Notification::class => $notification,
            ]);
        }

        return $notification;
    }
}
