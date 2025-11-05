<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Filament\Schemas\Components\Component;

class ConversationPanel extends Component
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-panel';

    protected bool | Closure $isLazy = false;

    /**
     * @var array<string, mixed> | Closure
     */
    protected array | Closure $data = [];

    /**
     * @var class-string<HasConversationSchema>|Closure | null
     */
    protected string | Closure $component;

    /**
     * @var class-string|Closure|null
     */
    protected string | Closure | null $configuration;

    /**
     * @param  class-string|Closure|null  $configuration
     */
    final public function __construct(string | Closure | null $configuration, string | Closure $component)
    {
        $this->configuration = $configuration;
        $this->component = $component;
    }

    /**
     * @param  class-string|Closure|null  $configuration
     */
    public static function make(string | Closure | null $configuration = null): static
    {
        $static = app(static::class, [
            'configuration' => $configuration,
            'component' => ConversationManager::class,
        ]);
        $static->configure();

        return $static;
    }

    /**
     * @param  class-string<HasConversationSchema>|Closure  $component
     */
    public function component(string | Closure $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * @return class-string<HasConversationSchema>
     */
    public function getComponent(): string
    {
        return $this->evaluate($this->component) ?? ConversationManager::class;
    }

    /**
     * @param  class-string|Closure|null  $configuration
     */
    public function configuration(string | Closure | null $configuration = null): static
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getConfiguration(): ?string
    {
        return $this->evaluate($this->configuration);
    }

    public function lazy(bool | Closure $condition = true): static
    {
        $this->isLazy = $condition;

        return $this;
    }

    public function isLazy(): bool
    {
        return (bool) $this->evaluate($this->isLazy);
    }

    /**
     * @param  array<string, mixed> | Closure  $data
     */
    public function data(array | Closure $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->evaluate($this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getComponentProperties(): array
    {
        $properties = [];

        if ($configuration = $this->getConfiguration()) {
            $properties['conversationSchemaConfiguration'] = $configuration;
        }

        if ($this->isLazy()) {
            $properties['lazy'] = true;
        }

        return [
            ...$properties,
            ...$this->getData(),
        ];
    }

    public function getId(): ?string
    {
        return $this->getCustomId();
    }
}
