<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\AttachmentArea;
use Dvarilek\FilamentConverse\Schemas\Components\Configurations\MessageInputSchemaConfiguration;
use Dvarilek\FilamentConverse\Schemas\Components\Contracts\HasMessageInput;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use League\Csv\Exception;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditMessageAction extends Action implements HasMessageInput
{
    protected ?Closure $getAttachmentAreaComponentUsing = null;

    protected ?Closure $getTextareaComponentUsing = null;

    protected ?Closure $getUploadAttachmentActionUsing = null;

    protected ?Closure $getSendMessageActionUsing = null;

    protected ?Closure $modifyAttachmentAreaComponentUsing = null;

    protected ?Closure $modifyTextareaComponentUsing = null;

    protected ?Closure $modifyUploadAttachmentActionUsing = null;

    protected ?Closure $modifySendMessageActionUsing = null;
    
    protected ?Closure $updateMessageUsing = null;

    public static function getDefaultName(): string
    {
        return 'editMessage';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.message-actions.edit.label'));

        $this->modalHeading(__('filament-converse::conversation-thread.message-actions.edit.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::conversation-thread.message-actions.edit.submit-label'));

        $this->successNotificationTitle(__('filament-converse::conversation-thread.message-actions.edit.success'));

        $this->color('primary');

        $this->icon(Heroicon::OutlinedPencil);

        $this->modalWidth(Width::Large);

        $this->model(Message::class);

        $this->record(
            static fn (Conversation $conversation, array $arguments): ?Message => $conversation
                ->messages()
                ->find($arguments['recordKey'] ?? null)
        );

        $this->visible(
            static fn (ConversationManager $livewire, ?Message $message): bool => $message?->author_id === $livewire->getActiveConversationAuthenticatedUserParticipation()->getKey()
        );

        $this->fillForm(static fn (Message $message): array => [
            'messageContent' => $message->content,
            'attachments' => array_combine($message->attachments, $message->attachment_file_names),
        ]);

        $this->schema(static fn (Schema $schema, EditMessageAction $action): Schema => MessageInputSchemaConfiguration::configure($schema, $action));

        $this->extraModalWindowAttributes([
            'x-ref' => 'uploadDropZoneRef',
        ]);

        $this->updateMessageUsing(static function (array $data, Message $message, Conversation $conversation): bool {
            $messageContent = $data['messageContent'] ?? null;
            $uploadedAttachments = $data['attachments'] ?? [];

            if (blank($messageContent) && blank($uploadedAttachments)) {
                return false;
            }

            $attachments = $attachmentFileNames = [];

            foreach ($uploadedAttachments as $storedFileName => $attachment) {
                $attachments[] = $storedFileName;
                $attachmentFileNames[] = $attachment instanceof TemporaryUploadedFile ? $attachment->getClientOriginalName() : $attachment;
            }

            if ($message->content === $messageContent && $message->attachments === $attachments && $message->attachment_file_names === $attachmentFileNames) {
                return false;
            }

            return $message->updateMessage($conversation, [
                'content' => $messageContent,
                'attachments' => $attachments,
                'attachment_file_names' => $attachmentFileNames,
            ]);
        });

        $this->action(static function (array $data, EditMessageAction $action): void {
            if (! $action->updateMessageUsing) {
                return;
            }

            $result = $action->evaluate($action->updateMessageUsing);

            if (! $result) {
                $action->failure();

                return;
            }

            $action->success();
        });

        $this->modalFooterActions([]);
    }

    public function getAttachmentAreaComponentUsing(?Closure $callback = null): static
    {
        $this->getAttachmentAreaComponentUsing = $callback;

        return $this;
    }

    public function getTextareaComponentUsing(?Closure $callback = null): static
    {
        $this->getTextareaComponentUsing = $callback;

        return $this;
    }

    public function getUploadAttachmentActionUsing(?Closure $callback = null): static
    {
        $this->getUploadAttachmentActionUsing = $callback;

        return $this;
    }

    public function getSendMessageActionUsing(?Closure $callback = null): static
    {
        $this->getSendMessageActionUsing = $callback;

        return $this;
    }

    public function modifyAttachmentAreaComponentUsing(?Closure $callback = null): static
    {
        $this->modifyAttachmentAreaComponentUsing = $callback;

        return $this;
    }

    public function modifyTextareaComponentUsing(?Closure $callback = null): static
    {
        $this->modifyTextareaComponentUsing = $callback;

        return $this;
    }

    public function modifyUploadAttachmentActionUsing(?Closure $callback = null): static
    {
        $this->modifyUploadAttachmentActionUsing = $callback;

        return $this;
    }

    public function modifySendMessageActionUsing(?Closure $callback = null): static
    {
        $this->modifySendMessageActionUsing = $callback;

        return $this;
    }
    
    public function updateMessageUsing(?Closure $callback = null): static
    {
        $this->updateMessageUsing = $callback;

        return $this;
    }

    public function getReplyToMessageBanner(): null
    {
        return null;
    }
    
    public function getAttachmentAreaComponent(): ?Field
    {
        $component = $this->evaluate($this->getAttachmentAreaComponentUsing);

        if ($this->modifyAttachmentAreaComponentUsing) {
            $component = $this->evaluate($this->modifyAttachmentAreaComponentUsing, [
                'component' => $component,
            ], [
                AttachmentArea::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getTextareaComponent(): ?Field
    {
        $component = $this->evaluate($this->getTextareaComponentUsing);

        if ($this->modifyTextareaComponentUsing) {
            $component = $this->evaluate($this->modifyTextareaComponentUsing, [
                'component' => $component,
            ], [
                Textarea::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getUploadAttachmentAction(): ?Action
    {
        $action = $this->evaluate($this->getUploadAttachmentActionUsing);

        if (! $action instanceof UploadAttachmentAction) {
            throw new Exception(
                'The action must be of type [' . UploadAttachmentAction::class . '], [' . get_debug_type($action) . '] given.'
            );
        }

        $action->attachmentAreaComponent(schemaName: static fn (ConversationManager $livewire) => $livewire->getMountedActionSchemaName());

        if ($this->modifyUploadAttachmentActionUsing) {
            $action = $this->evaluate($this->modifyUploadAttachmentActionUsing, [
                'action' => $action,
            ], [
                Actions::class => $action,
            ]) ?? $action;
        }

        return $action;
    }
    
    public function getSendMessageAction(): ?Action
    {
        $action = $this->evaluate($this->getSendMessageActionUsing);

        if ($action instanceof Action) {
            $action
                ->label(__('filament-converse::conversation-thread.message-input.actions.update-message-label'))
                ->icon(Heroicon::OutlinedCheck)
                ->action($this->getLivewireCallMountedActionName());
        }

        if ($this->modifySendMessageActionUsing) {
            $action = $this->evaluate($this->modifySendMessageActionUsing, [
                'action' => $action,
            ]) ?? $action;
        }

        return $action;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'conversation',
            'activeConversation' => [$this->getLivewire()->getActiveConversation()],
            'message' => [$this->getRecord()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            Conversation::class => [$this->getLivewire()->getActiveConversation()],
            Message::class => [$this->getRecord()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }
}
