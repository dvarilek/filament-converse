<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use App\Models\User;
use Closure;
use Dvarilek\FilamentConverse\Actions\TransferConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\Configuration\ParticipationTransferTableSelectConfiguration;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class TransferConversationAction extends Action
{
    protected ?Closure $modifyParticipationSelectComponentUsing = null;

    protected ?Closure $transferConversationUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'transferConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::actions.transfer.label'));

        $this->requiresConfirmation();

        $this->modalHeading(__('filament-converse::actions.transfer.modal-heading'));

        $this->modalheading(static function (Conversation $conversation) {
            $otherParticipations = $conversation->participations->other()->active();

            if ($otherParticipations->count() === 1) {
                $participant = $otherParticipations->first()->participant;

                return __('filament-converse::actions.transfer.modal-heading-single-participant', [
                    'name' => $participant->getAttribute($participant::getFilamentNameAttribute())
                ]);
            }

            return __('filament-converse::actions.transfer.modal-heading');
        });

        $this->modalSubmitActionLabel(__('filament-converse::actions.transfer.modal-submit-action-label'));

        $this->successNotificationTitle(__('filament-converse::actions.transfer.success-notification-title'));

        $this->color('warning');

        $this->icon(Heroicon::OutlinedUsers);

        $this->visible(static fn (ConversationManager $livewire): bool => $livewire->isActiveConversationOwnedByAuthenticatedUser());

        $this->schema(static fn (TransferConversationAction $action, Conversation $conversation): ?array => $conversation->participations->other()->count() === 1
            ? null
            : [
                $action->getParticipationSelectComponent()
            ]
        );

        $this->transferConversationUsing(static function (array $data, Conversation $conversation): bool {
            /* @var (Authenticatable&Model) | null $participant */

            $otherActiveParticipations = $conversation->participations->other()->active();

            if ($otherActiveParticipations->count() === 1) {
                $participant = $otherActiveParticipations->first()->participant;
            } else {
                $participant = ConversationParticipation::query()
                    ->active()
                    ->find($data['participation'] ?? null)
                    ?->participant;
            }

            if (! $participant) {
                return false;
            }

            return app(TransferConversation::class)->handle($conversation, $participant);
        });

        $this->action(static function (TransferConversationAction $action, ConversationManager $livewire): void {
            if (! $action->transferConversationUsing) {
                return;
            }

            $result = $action->evaluate($action->transferConversationUsing);

            if ($result === false) {
                $action->failure();

                return;
            }

            unset($livewire->conversations);
            $action->success();
        });
    }

    public function participationSelectComponent(?Closure $callback = null): static
    {
        $this->modifyParticipationSelectComponentUsing = $callback;

        return $this;
    }

    public function transferConversationUsing(?Closure $callback = null): static
    {
        $this->transferConversationUsing = $callback;

        return $this;
    }

    public function getParticipationSelectComponent(): Component
    {
        $component = TableSelect::make('participation')
            ->label(__('filament-converse::actions.schema.participations.label'))
            ->tableConfiguration(ParticipationTransferTableSelectConfiguration::class)
            ->tableArguments(static fn (Conversation $conversation) => [
                'conversationKey' => $conversation->getKey()
            ])
            ->required()
            ->extraAttributes([
                'class' => 'fi-converse-table-select',
            ]);

        if ($this->modifyParticipationSelectComponentUsing) {
            $component = $this->evaluate($this->modifyParticipationSelectComponentUsing, [
                'component' => $component,
            ], [
                Select::class => $component
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
