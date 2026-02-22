<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Actions\UpdateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ManageConversationAction extends Action
{
    use Concerns\CanManageConversation;

    protected ?Closure $updateConversationUsing = null;

    protected ?Closure $modifyAdvancedActionsFieldsetUsing = null;

    protected string | Htmlable | Closure | null $modifyAdvancedActionsFieldsetLabel = null;

    protected ?Closure $modifyTransferConversationActionUsing = null;

    protected ?Closure $modifyLeaveConversationActionUsing = null;

    protected ?Closure $modifyDeleteConversationActionUsing = null;

    protected ?Closure $modifyTransferConversationActionTextComponentUsing = null;

    protected ?Closure $modifyLeaveConversationActionTextComponentUsing = null;

    protected ?Closure $modifyDeleteConversationActionTextComponentUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'manageConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::actions.manage.label'));

        $this->modalHeading(static fn (ConversationManager $livewire, Conversation $conversation): string => __(
            $livewire->isActiveConversationOwnedByAuthenticatedUser()
                ? 'filament-converse::actions.manage.modal-heading-edit'
                : 'filament-converse::actions.manage.modal-heading-view',
        ));

        $this->modalSubmitActionLabel(__('filament-converse::actions.manage.modal-submit-action-label'));

        $this->successNotificationTitle(__('filament-converse::actions.manage.success-notification-title'));

        $this->iconButton();

        $this->color('gray');

        $this->icon(Heroicon::OutlinedCog6Tooth);

        $this->modalWidth(Width::Large);

        $this->size(Size::ExtraLarge);

        $this->slideOver();

        $this->advancedActionsFieldsetLabel(__('filament-converse::actions.manage.advanced-actions.label'));

        $this->fillForm(static function (Conversation $conversation): array {
            /* @var list<string> $otherParticipantIds */
            $otherParticipantIds = $conversation
                ->participations
                ->other()
                ->active()
                ->pluck('participant_id')
                ->toArray();

            return [
                'participants' => $otherParticipantIds,
                'name' => $conversation->name,
                'description' => $conversation->description,
                'image' => $conversation->image,
            ];
        });

        $this->modalSubmitAction(
            static fn (Action $action, ConversationManager $livewire, Conversation $conversation) => $action
                ->visible($livewire->isActiveConversationOwnedByAuthenticatedUser())
        );

        $this->registerModalActions([
            static fn (ManageConversationAction $action) => $action->getTransferConversationAction(),
            static fn (ManageConversationAction $action) => $action->getLeaveConversationAction(),
            static fn (ManageConversationAction $action) => $action->getDeleteConversationAction(),
        ]);
        
        $this->schema(
            static fn (Schema $schema, ManageConversationAction $action): Schema => $schema
                ->disabled(static fn (ConversationManager $livewire): bool => ! $livewire->isActiveConversationOwnedByAuthenticatedUser())
                ->schema([
                    $action->getParticipantSelectComponent(),
                    Group::make([
                        $action->getConversationNameComponent(),
                        $action->getConversationDescriptionComponent(),
                        $action->getConversationImageComponent(),
                        Fieldset::make($action->getAdvancedActionsFieldsetLabel())
                            ->columns(1)
                            ->dense()
                            ->extraAttributes([
                                'style' => 'border-color: ' . FilamentColor::getColor('danger')['600'],
                            ])
                            ->schema([
                                Flex::make([
                                    $action->getTransferConversationActionTextComponent(),
                                    $action->getModalAction(TransferConversationAction::getDefaultName()),
                                ])
                                    ->visible(static fn (ConversationManager $livewire): bool => $livewire->isActiveConversationOwnedByAuthenticatedUser()),
                                Flex::make([
                                    $action->getLeaveConversationActionTextComponent(),
                                    $action->getModalAction(LeaveConversationAction::getDefaultName()),
                                ])
                                    ->hidden(static fn (ConversationManager $livewire): bool => $livewire->isActiveConversationOwnedByAuthenticatedUser()),
                                Flex::make([
                                    $action->getDeleteConversationActionTextComponent(),
                                    $action->getModalAction(DeleteConversationAction::getDefaultName()),
                                ])
                                    ->visible(static fn (ConversationManager $livewire): bool => $livewire->isActiveConversationOwnedByAuthenticatedUser()),
                            ]),
                    ]),
                ])
        );

        $this->updateConversationUsing(static function (array $data, Conversation $conversation): Conversation {
            $user = auth()->user();

            /* @var Collection<int, Model&Authenticatable>|(Model&Authenticatable) $otherParticipants */
            $otherParticipants = $user::query()->whereIn($user->getKeyName(), $data['participants'])->get();

            return app(UpdateConversation::class)->handle(
                $conversation,
                [
                    ...$otherParticipants,
                    $user,
                ],
                [
                    'name' => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                    'image' => $data['image'] ?? null,
                ]
            );
        });

        $this->action(static function (ManageConversationAction $action, ConversationManager $livewire): void {
            if (! $action->updateConversationUsing) {
                return;
            }

            $result = $action->evaluate($action->updateConversationUsing);

            if ($result === false) {
                $action->failure();

                return;
            }

            unset($livewire->conversations);
            $action->success();
        });
    }

    public function updateConversationUsing(?Closure $callback = null): static
    {
        $this->updateConversationUsing = $callback;

        return $this;
    }

    public function advancedActionsFieldsetLabel(string | Htmlable | Closure | null $label): static
    {
        $this->modifyAdvancedActionsFieldsetLabel = $label;

        return $this;
    }

    public function modifyTransferConversationAction(?Closure $callback = null): static
    {
        $this->modifyTransferConversationActionUsing = $callback;

        return $this;
    }

    public function modifyLeaveConversationAction(?Closure $callback = null): static
    {
        $this->modifyLeaveConversationActionUsing = $callback;

        return $this;
    }

    public function modifyDeleteConversationAction(?Closure $callback = null): static
    {
        $this->modifyDeleteConversationActionUsing = $callback;

        return $this;
    }

    public function modifyTransferConversationActionTextComponent(?Closure $callback = null): static
    {
        $this->modifyTransferConversationActionTextComponentUsing = $callback;

        return $this;
    }

    public function modifyLeaveConversationActionTextComponent(?Closure $callback = null): static
    {
        $this->modifyLeaveConversationActionTextComponentUsing = $callback;

        return $this;
    }

    public function modifyDeleteConversationActionTextComponent(?Closure $callback = null): static
    {
        $this->modifyDeleteConversationActionTextComponentUsing = $callback;

        return $this;
    }

    public function modifyAdvancedActionsFieldset(?Closure $callback = null): static
    {
        $this->modifyAdvancedActionsFieldsetUsing = $callback;

        return $this;
    }

    public function getAdvancedActionsFieldsetLabel(): string | Htmlable | null
    {
        return $this->evaluate($this->modifyAdvancedActionsFieldsetLabel);
    }

    public function getTransferConversationAction(): Action
    {
        $action = TransferConversationAction::make();

        if ($this->modifyTransferConversationActionUsing) {
            $action = $this->evaluate($this->modifyTransferConversationActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function getLeaveConversationAction(): Action
    {
        $action = LeaveConversationAction::make();

        if ($this->modifyLeaveConversationActionUsing) {
            $action = $this->evaluate($this->modifyLeaveConversationActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function getDeleteConversationAction(): Action
    {
        $action = DeleteConversationAction::make();

        if ($this->modifyDeleteConversationActionUsing) {
            $action = $this->evaluate($this->modifyDeleteConversationActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function getTransferConversationActionTextComponent(): Component
    {
        $component = Text::make(__('filament-converse::actions.manage.advanced-actions.transfer-conversation-text'));

        if ($this->modifyTransferConversationActionTextComponentUsing) {
            $component = $this->evaluate($this->modifyTransferConversationActionTextComponentUsing, [
                'component' => $component,
            ], [
                Text::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getLeaveConversationActionTextComponent(): Component
    {
        $component = Text::make(__('filament-converse::actions.manage.advanced-actions.leave-conversation-text'));

        if ($this->modifyLeaveConversationActionTextComponentUsing) {
            $component = $this->evaluate($this->modifyLeaveConversationActionTextComponentUsing, [
                'component' => $component,
            ], [
                Text::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getDeleteConversationActionTextComponent(): Component
    {
        $component = Text::make(__('filament-converse::actions.manage.advanced-actions.delete-conversation-text'));

        if ($this->modifyDeleteConversationActionTextComponentUsing) {
            $component = $this->evaluate($this->modifyDeleteConversationActionTextComponentUsing, [
                'component' => $component,
            ], [
                Text::class => $component,
            ]) ?? $component;
        }

        return $component;
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
