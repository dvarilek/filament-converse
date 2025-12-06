<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Carbon\Carbon;
use Closure;
use Dvarilek\FilamentConverse\Events\UserTyping;
use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\DeleteMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\EditMessageAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConversationThread extends Textarea
{
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use Concerns\HasFileAttachments;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const MESSAGE_ACTIONS_KEY = 'message_actions';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

    protected int | Closure | null $maxHeight = 8;

    protected int | Closure | null $defaultLoadedMessagesCount = 15;

    protected int | Closure | null $messagesLoadedPerPage = 15;

    protected int | Closure | null $messageTimestampGroupingInterval = 420;

    protected int | Closure | null $autoScrollOnForeignMessagesThreshold = 300;

    protected bool | Closure $shouldDispatchUserTypingEvent = true;

    protected ?Closure $formatTypingUserNameUsing = null;

    protected int | Closure $userTypingIndicatorTimeout = 3500;

    protected int | Closure | null $userTypingEventDispatchThreshold = 3000;

    /**
     * @var array{single: string, double: string, multiple: string, other: string, others: string}|Closure
     */
    protected array | Closure $userTypingTranslations = [];

    protected ?Closure $formatMessageTimestampUsing = null;

    protected ?Closure $modifyMessagesQueryUsing = null;

    /**
     * @param  string | array<string> | Closure | null  $messageColor
     */
    protected string | array | Closure | null $messageColor = null;

    protected ?Closure $modifyEditConversationActionUsing = null;

    protected ?Closure $modifyEditMessageActionUsing = null;

    protected ?Closure $modifyDeleteMessageActionUsing = null;

    protected ?Closure $modifySendMessageActionUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'conversation_thread';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();

        $this->autosize();

        $this->autofocus();

        $this->maxLength(65535);

        $this->placeholder(__('filament-converse::conversation-thread.placeholder'));

        $this->attachmentModalDescription(__('filament-converse::conversation-thread.attachment-modal.description'));

        $this->emptyStateHeading(__('filament-converse::conversation-thread.empty-state.heading'));

        $this->userTypingTranslations([
            'single' => __('filament-converse::conversation-thread.typing-indicator.single'),
            'double' => __('filament-converse::conversation-thread.typing-indicator.double'),
            'multiple' => __('filament-converse::conversation-thread.typing-indicator.multiple'),
            'other' => __('filament-converse::conversation-thread.typing-indicator.other'),
            'others' => __('filament-converse::conversation-thread.typing-indicator.others'),
        ]);

        $this->formatMessageTimestampUsing(static function (Carbon $timestamp, Message $message): string {
            return match (true) {
                ! $timestamp->isCurrentYear() => $timestamp->isoFormat('L LT'),
                $timestamp->isCurrentWeek() && ! $timestamp->isCurrentDay() => $timestamp->isoFormat('ddd LT'),
                ! $timestamp->isCurrentDay() => $timestamp->isoFormat('D MMMM LT'),
                default => $timestamp->isoFormat('LT'),
            };
        });

        $this->messageColor(static function (Message $message): string {
            return $message->author->participant->getKey() === auth()->id() ? 'primary' : 'danger';
        });

        $this->fileAttachmentsAcceptedFileTypes([
            'image/png',
            'image/jpeg',
            'audio/mpeg',
            'video/mp4',
            'video/mpeg',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        $this->uploadedFileAttachmentName(static function (TemporaryUploadedFile $attachment): ?string {
            return $attachment->getClientOriginalName();
        });

        $this->defaultFileAttachmentIcon(function (string $attachmentMimeType): Heroicon {
            return match ($attachmentMimeType) {
                'image/png',
                'image/jpeg' => Heroicon::OutlinedPhoto,
                'audio/mpeg' => Heroicon::OutlinedSpeakerWave,
                'video/mp4',
                'video/mpeg' => Heroicon::OutlinedVideoCamera,
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => Heroicon::OutlinedDocumentText,
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => Heroicon::OutlinedDocumentCurrencyEuro,
                default => Heroicon::OutlinedDocumentText,
            };
        });

        $this->defaultFileAttachmentIconColor(static function (string $attachmentMimeType): string {
            return match ($attachmentMimeType) {
                'application/pdf', => 'danger',
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'success',
                default => 'primary',
            };
        });

        $this->defaultFileAttachmentMimeTypeBadgeLabel(static function (string $attachmentMimeType): ?string {
            return match ($attachmentMimeType) {
                'image/png',
                'image/jpeg' => __('filament-converse::conversation-thread.attachments.mime-type.image'),
                'audio/mpeg' => __('filament-converse::conversation-thread.attachments.mime-type.audio'),
                'video/mp4',
                'video/mpeg' => __('filament-converse::conversation-thread.attachments.mime-type.video'),
                'application/pdf' => __('filament-converse::conversation-thread.attachments.mime-type.pdf'),
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => __('filament-converse::conversation-thread.attachments.mime-type.document'),
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => __('filament-converse::conversation-thread.attachments.mime-type.spreadsheet'),
                default => null,
            };
        });

        $this->childComponents(static fn (ConversationThread $component) => [
            $component->getEditConversationAction(),
        ], static::HEADER_ACTIONS_KEY);

        $this->childComponents(static fn (ConversationThread $component) => [
            $component->getEditMessageAction(),
            $component->getDeleteMessageAction(),
        ], static::MESSAGE_ACTIONS_KEY);

        $this->registerActions([
            static fn (ConversationThread $component) => $component->getSendMessageAction(),
            static fn (ConversationThread $component) => $component->getUploadAttachmentAction(),
        ]);
    }

    public function maxHeight(int | Closure | null $maxHeight): static
    {
        $this->maxHeight = $maxHeight;

        return $this;
    }

    public function defaultLoadedMessagesCount(int | Closure | null $count): static
    {
        $this->defaultLoadedMessagesCount = $count;

        return $this;
    }

    public function messagesLoadedPerPage(int | Closure | null $count): static
    {
        $this->messagesLoadedPerPage = $count;

        return $this;
    }

    public function messageTimestampGroupingInterval(string | Closure | null $seconds): static
    {
        $this->messageTimestampGroupingInterval = $seconds;

        return $this;
    }

    public function autoScrollOnForeignMessagesThreshold(int | Closure | null $pixels): static
    {
        $this->autoScrollOnForeignMessagesThreshold = $pixels;

        return $this;
    }

    public function dispatchUserTypingEvent(bool | Closure $condition): static
    {
        $this->shouldDispatchUserTypingEvent = $condition;

        return $this;
    }

    public function formatTypingUserNameUsing(?Closure $callback = null): static
    {
        $this->getTypingUserNameUsing = $callback;

        return $this;
    }

    public function userTypingIndicatorTimeout(int | Closure | null $milliseconds): static
    {
        $this->userTypingIndicatorTimeout = $milliseconds;

        return $this;
    }

    public function userTypingEventDispatchThreshold(int | Closure | null $millisecond): static
    {
        $this->userTypingEventDispatchThreshold = $millisecond;

        return $this;
    }

    /**
     * @param  array{single: string, double: string, multiple: string, other: string, others: string}|Closure  $translations
     */
    public function userTypingTranslations(array | Closure $translations): static
    {
        $this->userTypingTranslations = $translations;

        return $this;
    }

    public function formatMessageTimestampUsing(?Closure $callback): static
    {
        $this->formatMessageTimestampUsing = $callback;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function messageColor(string | array | Closure | null $color = null): static
    {
        $this->messageColor = $color;

        return $this;
    }

    public function modifyMessagesQueryUsing(?Closure $callback): static
    {
        $this->modifyMessagesQueryUsing = $callback;

        return $this;
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

    public function sendMessageAction(?Closure $callback): static
    {
        $this->modifySendMessageActionUsing = $callback;

        return $this;
    }

    public function getMaxHeight(): ?int
    {
        return $this->evaluate($this->maxHeight);
    }

    public function getDefaultLoadedMessagesCount(): int
    {
        return $this->evaluate($this->defaultLoadedMessagesCount) ?? 15;
    }

    public function getMessagesLoadedPerPage(): int
    {
        return $this->evaluate($this->messagesLoadedPerPage) ?? 15;
    }

    public function getMessageTimestampGroupingInterval(): int
    {
        return $this->evaluate($this->messageTimestampGroupingInterval) ?? 420;
    }

    public function getAutoScrollOnForeignMessagesThreshold(): int
    {
        return $this->evaluate($this->autoScrollOnForeignMessagesThreshold) ?? 300;
    }

    public function shouldDispatchUserTypingEvent(): bool
    {
        return (bool) $this->evaluate($this->shouldDispatchUserTypingEvent);
    }

    public function getUserTypingIndicatorTimeout(): int
    {
        return $this->evaluate($this->userTypingIndicatorTimeout) ?? 3500;
    }

    public function getUserTypingEventDispatchThreshold(): ?int
    {
        return $this->evaluate($this->userTypingEventDispatchThreshold);
    }

    /**
     * @return array{single: string, double: string, multiple: string, other: string, others: string}
     */
    public function getUserTypingTranslations(): array
    {
        return $this->evaluate($this->userTypingTranslations) ?? [];
    }

    public function formatMessageTimestamp(Carbon $timestamp, Message $message): ?string
    {
        return $this->evaluate($this->formatMessageTimestampUsing, [
            'timestamp' => $timestamp,
            'message' => $message,
        ], [
            Carbon::class => $timestamp,
            Message::class => $message,
        ]);
    }

    public function getMessageColor(Message $message): string | array
    {
        return $this->evaluate($this->messageColor, [
            'message' => $message,
        ], [
            Message::class => $message,
        ]) ?? 'gray';
    }

    /**
     * @return Builder<Message>|null
     */
    public function getMessagesQuery(bool $shouldPaginate = true): ?Builder
    {
        /* @var ConversationManager $livewire */
        $livewire = $this->getLivewire();
        $conversation = $livewire->getActiveConversation();

        if (! $conversation) {
            return null;
        }

        /* @var Builder<Message> $query */
        $query = $conversation->messages()
            ->getQuery()
            ->orderBy('created_at', 'desc');

        if ($shouldPaginate) {
            $limit = $this->getDefaultLoadedMessagesCount()
                + (($livewire->getActiveConversationMessagesPage() - 1) * $this->getMessagesLoadedPerPage());

            $extra = count(array_filter($livewire->messagesCreatedDuringConversationSession, static fn (array $data) => $data['exists'] === true));

            $query->limit($limit + $extra);
        }

        if ($this->modifyMessagesQueryUsing) {
            $query = $this->evaluate($this->modifyMessagesQueryUsing, [
                'query' => $query,
            ], [
                Builder::class => $query,
            ]) ?? $query;
        }

        return $query;
    }

    protected function getEditConversationAction(): Action
    {
        $action = Action::make('editConversation')
            ->iconButton()
            ->color('gray')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->size(Size::ExtraLarge)
            ->action(fn () => dd('editConversation'));

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

    protected function getSendMessageAction(): Action
    {
        $action = Action::make('sendMessage')
            ->label(__('filament-converse::conversation-thread.footer-actions.send-message-label'))
            ->iconButton()
            ->iconSize(IconSize::Large)
            ->icon(Heroicon::PaperAirplane)
            ->keyBindings(['enter'])
            ->action(static function (ConversationThread $component, ConversationManager $livewire) {
                $state = $livewire->content->getState();
                $statePath = $component->getStatePath();

                $message = data_get([$livewire->content->getStatePath() => $state], $statePath);
                $uploadedFileAttachments = $component->getValidUploadedFileAttachments();

                if (blank($message) && blank($uploadedFileAttachments)) {
                    return;
                }

                $attachments = $attachmentFileNames = [];

                foreach ($uploadedFileAttachments as $attachment) {
                    $attachments[] = $component->saveUploadedFileAttachment($attachment);
                    $attachmentFileNames[] = $attachment->getClientOriginalName();
                }

                $activeConversation = $livewire->getActiveConversation();
                $message = $livewire->getActiveConversationAuthenticatedUserParticipation()->sendMessage($activeConversation, [
                    'content' => $message,
                    'attachments' => $attachments,
                    'attachment_file_names' => $attachmentFileNames,
                ]);

                $livewire->content->fill();
                data_set($livewire->componentFileAttachments, $statePath . ".{$activeConversation->getKey()}", []);
                data_set($livewire->cachedUnsendMessages, $statePath . ".{$activeConversation->getKey()}", null);
                $livewire->registerMessageCreatedDuringConversationSession($message->getKey(), auth()->id());
            });

        if ($this->modifySendMessageActionUsing) {
            $action = $this->evaluate($this->modifySendMessageActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
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
            'activeConversation' => [$this->getActiveConversation()],
            default => []
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            Message::class => [$this->getActiveConversation()],
            default => []
        };
    }

    #[Renderless]
    #[ExposedLivewireMethod]
    public function broadcastUserTypingEvent(): void
    {
        if (! $this->shouldDispatchUserTypingEvent()) {
            return;
        }

        /* @var Model & Authenticatable */
        $user = auth()->user();

        if (! in_array(Conversable::class, class_uses_recursive($user))) {
            FilamentConverseException::throwInvalidConversableUserException($user);
        }

        $name = $user->getAttributeValue($user::getFilamentNameAttribute());

        if ($this->formatTypingUserNameUsing) {
            $name = $this->evaluate($this->formatTypingUserNameUsing, [
                'name' => $name,
                'user' => $user,
            ], [
                Authenticatable::class => $user,
                Model::class => $user,
            ]);
        }

        broadcast(new UserTyping($user->getKey(), $name, $this->getActiveConversation()))->toOthers();
    }
}
