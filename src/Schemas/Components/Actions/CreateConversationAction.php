<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CreateConversationAction extends Action
{
    use Concerns\CanManageConversation;

    protected ?Closure $createConversationUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'createConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::actions.create.label'));

        $this->modalHeading(__('filament-converse::actions.create.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::actions.create.modal-submit-action-label'));

        $this->successNotificationTitle(__('filament-converse::actions.create.success-notification-title'));

        $this->icon(Heroicon::Plus);

        $this->modalWidth(Width::Large);

        $this->slideOver();
        
        $this->schema(static fn (CreateConversationAction $action): array => [
            $action->getParticipantSelectComponent(),
            Group::make([
                $action->getConversationNameComponent(),
                $action->getConversationDescriptionComponent(),
                $action->getConversationImageComponent(),
            ])
                ->visibleJs(<<<'JS'
                    $get('participants')?.length > 1
                JS)
        ]);

        $this->createConversationUsing(static function (array $data): Conversation {
            $user = auth()->user();

            /* @var Collection<int, Model&Authenticatable>|(Model&Authenticatable) $otherParticipants */
            $otherParticipants = $user::query()->whereIn($user->getKeyName(), $data['participants'])->get();

            return app(CreateConversation::class)->handle(
                $user,
                $otherParticipants,
                [
                    'name' => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                    'image' => $data['image'] ?? null,
                ]
            );
        });

        $this->action(static function (CreateConversationAction $action, ConversationManager $livewire): void {
            if (! $action->createConversationUsing) {
                return;
            }

            /* @var ?Conversation $conversation */
            $conversation = $action->evaluate($action->createConversationUsing);

            if (! $conversation) {
                $action->failure();

                return;
            }

            $livewire->updateActiveConversation($conversation->getKey());
            unset($livewire->conversations);

            $action->success();
        });
    }

    public function createConversationUsing(?Closure $callback = null): static
    {
        $this->createConversationUsing = $callback;

        return $this;
    }
}
