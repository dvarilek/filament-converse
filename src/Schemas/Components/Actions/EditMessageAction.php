<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\Concerns\CanSendMessages;
use Dvarilek\FilamentConverse\Schemas\Components\Configurations\MessageInputSchemaConfiguration;
use Dvarilek\FilamentConverse\Schemas\Components\Contracts\HasMessageInput;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditMessageAction extends Action implements HasMessageInput
{
    use CanSendMessages;

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

        $this->fillForm(static fn ($livewire, ?Message $message): array => [
            'messageContent' => $message?->content,
            'test' => $livewire->getConversationSchema()->getConversationThread()->getMessageAttachmentData($message, $message->author->participant, collect([]))
        ]);

        $this->schema(static fn (Schema $schema, EditMessageAction $action): Schema => MessageInputSchemaConfiguration::configure($schema, $action));

        $this->extraModalWindowAttributes([
            'x-ref' => 'uploadDropZoneRef',
        ]);
        // TODO: getMessageAttachmentDataUsing
        $this->updateMessageUsing(static function (array $data, Message $message, Conversation $conversation, $livewire): bool {
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

    public function updateMessageUsing(?Closure $callback = null): static
    {
        $this->updateMessageUsing = $callback;

        return $this;
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
