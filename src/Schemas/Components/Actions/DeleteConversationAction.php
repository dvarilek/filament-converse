<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Action;
use Closure;
use Filament\Support\Icons\Heroicon;

class DeleteConversationAction extends Action
{
    protected ?Closure $deleteConversationUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'deleteConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::actions.delete.label'));

        $this->requiresConfirmation();

        $this->modalHeading(__('filament-converse::actions.delete.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::actions.delete.modal-submit-action-label'));

        $this->successNotificationTitle(__('filament-converse::actions.delete.success-notification-title'));

        $this->color('danger');

        $this->icon(Heroicon::OutlinedTrash);

        $this->cancelParentActions();

        $this->visible(static fn (ConversationManager $livewire): bool => $livewire->isActiveConversationOwnedByAuthenticatedUser());

        $this->deleteConversationUsing(static function (Conversation $conversation): bool {
            return (bool) $conversation->delete();
        });

        $this->action(static function (DeleteConversationAction $action, ConversationManager $livewire): void {
            if (! $action->deleteConversationUsing) {
                return;
            }

            $result = $action->evaluate($action->deleteConversationUsing);

            if ($result === false) {
                $action->failure();

                return;
            }

            $livewire->updateActiveConversation($livewire->getConversationSchema()->getDefaultActiveConversation()->getKey());
            unset($livewire->conversations);

            $action->success();
        });
    }

    public function deleteConversationUsing(?Closure $callback): static
    {
        $this->deleteConversationUsing = $callback;

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
