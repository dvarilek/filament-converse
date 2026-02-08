<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Dvarilek\FilamentConverse\Actions\UpdateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\Components\BadgeComponent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Closure;

class ManageConversationAction extends Action
{
    use Concerns\CanManageConversation;

    protected ?Closure $editConversationUsing = null;

    protected ?Closure $modifyConversationUpdatedNotificationUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'editConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.actions.manage.label'));

        $this->modalHeading(static fn (ConversationManager $livewire, Conversation $conversation) => __('filament-converse::conversation-thread.actions.manage.modal-heading', [
            'name' => $livewire->getConversationSchema()->getConversationName($conversation),
        ]));

        $this->modalSubmitActionLabel(__('filament-converse::conversation-thread.actions.manage.modal-submit-action-label'));

        $this->iconButton();

        $this->color('gray');

        $this->icon(Heroicon::OutlinedCog6Tooth);

        $this->modalWidth(Width::Large);

        $this->size(Size::ExtraLarge);

        $this->fillForm(static function (Conversation $conversation) {
            /* @var list<string> $otherParticipantIds */
            $otherParticipantIds = $conversation
                ->participations
                ->reject(static fn (ConversationParticipation $participation) => $participation->participant_id === auth()->id())
                ->pluck('participant_id')
                ->toArray();

            return [
                'participants' => $otherParticipantIds,
                'name' => $conversation->name,
                'description' => $conversation->description,
                'image' => $conversation->image,
            ];
        });

        $this->schema(static fn (ManageConversationAction $action) => [
            $action->getParticipantSelectComponent(),
            $action->getConversationNameComponent(),
            $action->getConversationDescriptionComponent(),
            $action->getConversationImageComponent(),
        ]);

        $this->editConversationUsing(static function (array $data, Conversation $conversation) {
            $user = auth()->user();

            /* @var Collection<int, Model&Authenticatable>|(Model&Authenticatable) $otherParticipants */
            $otherParticipants = $user::query()->whereIn($user->getKeyName(), $data['participants'])->get();

            app(UpdateConversation::class)->handle(
                $conversation,
                $otherParticipants,
                [
                    'name' => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                    'image' => $data['image'] ?? null,
                ]
            );
        });

        $this->action(static function (ManageConversationAction $action, Conversation $conversation) {
            if (! $action->editConversationUsing) {
                return;
            }

            $action->evaluate($action->editConversationUsing);

            unset($livewire->conversations);
            $action->getConversationUpdatedNotification()?->send();
        });
    }

    public function editConversationUsing(?Closure $callback = null): static
    {
        $this->editConversationUsing = $callback;

        return $this;
    }

    public function conversationUpdatedNotification(?Closure $callback): static
    {
        $this->modifyConversationUpdatedNotificationUsing = $callback;

        return $this;
    }

    public function getConversationUpdatedNotification(): ?Notification
    {
        $notification = Notification::make('conversationUpdated')
            ->success()
            ->title(__('filament-converse::conversation-thread.actions.manage.notifications.conversation-updated-title'));

        if ($this->modifyConversationUpdatedNotificationUsing) {
            $notification = $this->evaluate($this->modifyConversationUpdatedNotificationUsing, [
                'notification' => $notification,
            ], [
                Notification::class => $notification,
            ]);
        }

        return $notification;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'conversation',
            'activeConversation' => [$this->getRecord()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            Conversation::class => [$this->getRecord()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }
}
