<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Dvarilek\FilamentConverse\Actions\LeaveConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Action;
use Closure;
use Filament\Support\Icons\Heroicon;

class LeaveConversationAction extends Action
{
    protected ?Closure $leaveConversationUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'leaveConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::actions.leave.label'));

        $this->requiresConfirmation();

        $this->modalHeading(__('filament-converse::actions.leave.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::actions.leave.modal-submit-action-label'));

        $this->successNotificationTitle(__('filament-converse::actions.leave.success-notification-title'));

        $this->color('danger');

        $this->icon(Heroicon::OutlinedArrowRightStartOnRectangle);

        $this->cancelParentActions();

        $this->hidden(static fn (ConversationManager $livewire): bool => $livewire->isActiveConversationOwnedByAuthenticatedUser());

        $this->leaveConversationUsing(static fn (Conversation $conversation): bool => app(LeaveConversation::class)
            ->handle($conversation, auth()->user())
        );

        $this->action(static function (LeaveConversationAction $action, ConversationManager $livewire): void {
            if (! $action->leaveConversationUsing) {
                return;
            }

            $result = $action->evaluate($action->leaveConversationUsing);

            if ($result === false) {
                $action->failure();

                return;
            }

            unset($livewire->conversations);
            $action->success();
        });
    }

    public function leaveConversationUsing(?Closure $callback): static
    {
        $this->leaveConversationUsing = $callback;

        return $this;
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
