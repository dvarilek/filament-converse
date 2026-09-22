<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Support\Components\ViewComponent;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;

class UploadAttachmentAction extends Action
{
    protected string | Closure $attachmentAreaComponentName = 'attachments';

    protected string | Closure $attachmentAreaComponentSchemaName = 'content';

    protected ?Closure $getAttachmentAreaComponentUsing = null;

    public static function getDefaultName(): string
    {
        return 'uploadAttachment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.footer-actions.upload-attachment-label'));

        $this->icon(Heroicon::PaperClip);

        $this->iconButton();

        $this->iconSize(IconSize::Large);

        $this->getAttachmentAreaComponentUsing(static function (UploadAttachmentAction $action, ConversationManager $livewire): Field {
            $attachmentAreaSchemaName = $action->getAttachmentAreaComponentSchemaName();
            $schema = $livewire->getSchema($attachmentAreaSchemaName);

            if (! $schema) {
                throw new Exception("The schema [$attachmentAreaSchemaName] does not exist on [" . $livewire::class . '].');
            }

            $attachmentAreaComponentName = $action->getAttachmentAreaComponentName();
            /* @var ?Field $attachmentAreaComponent */
            $attachmentAreaComponent = $schema->getComponent(
                static fn (ViewComponent $component) => $component instanceof Field ? $component->getName() === $attachmentAreaComponentName : null
            );

            if (! $attachmentAreaComponent) {
                throw new Exception("The component [$attachmentAreaComponentName] does not exist in [$attachmentAreaSchemaName].");
            }

            return $attachmentAreaComponent;
        });

        $this->alpineClickHandler(static function (UploadAttachmentAction $action): ?string {
            if (! $action->getAttachmentAreaComponentUsing) {
                return null;
            }

            $attachmentAreaComponent = $action->evaluate($action->getAttachmentAreaComponentUsing);

            if (! $attachmentAreaComponent instanceof Field) {
                throw new Exception(
                    'The attachment area component must be of type [' . Field::class . '], [' . get_debug_type($attachmentAreaComponent) . '] given.'
                );
            }

            return "\$dispatch('filament-converse-trigger-file-input', { statePath: '" . $attachmentAreaComponent->getStatePath() . "' })";
        });
    }

    public function attachmentAreaComponent(string | Closure $componentName = 'attachments', string | Closure $schemaName = 'content'): static
    {
        $this->attachmentAreaComponentName = $componentName;
        $this->attachmentAreaComponentSchemaName = $schemaName;

        return $this;
    }

    public function getAttachmentAreaComponentUsing(?Closure $callback = null): static
    {
        $this->getAttachmentAreaComponentUsing = $callback;

        return $this;
    }

    public function getAttachmentAreaComponentName(): string
    {
        return $this->evaluate($this->attachmentAreaComponentName);
    }

    public function getAttachmentAreaComponentSchemaName(): string
    {
        return $this->evaluate($this->attachmentAreaComponentSchemaName);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'conversation',
            'activeConversation' => [$this->getLivewire()->getActiveConversation()],
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
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }
}
