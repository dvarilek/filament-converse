<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Filament\Forms\Components\Concerns\CanBeAutocompleted;
use Filament\Forms\Components\Concerns\CanBeLengthConstrained;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\CanDisableGrammarly;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Contracts\CanBeLengthConstrained as CanBeLengthConstrainedContract;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Concerns\CanStripCharactersFromState;
use Filament\Schemas\Components\Concerns\CanTrimState;
use Filament\Support\Concerns\HasExtraAlpineAttributes;

class MessageInput extends Field implements CanBeLengthConstrainedContract
{
    // TODO: Handle $state in a different way (and everywhere)
    use CanBeAutocompleted;
    use CanBeLengthConstrained;
    use CanBeReadOnly;
    use CanDisableGrammarly;
    use CanStripCharactersFromState;
    use CanTrimState;
    use HasExtraAlpineAttributes;
    use HasExtraInputAttributes;
    use HasPlaceholder;
    /*
     $state = [
            'attachments' => ['path' => 'fileName'],
            'content' => 'message content'
        ];
    */

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::message-input';

    protected int | Closure | null $cols = null;

    protected int | Closure | null $rows = null;

    protected bool | Closure $shouldAutosize = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();

        $this->autofocus();

        $this->autosize();

        $this->rows(3);
    }

    public function autosize(bool | Closure $condition = true): static
    {
        $this->shouldAutosize = $condition;

        return $this;
    }

    public function cols(int | Closure | null $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    public function rows(int | Closure | null $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function getCols(): ?int
    {
        return $this->evaluate($this->cols);
    }

    public function getRows(): ?int
    {
        return $this->evaluate($this->rows);
    }

    public function shouldAutosize(): bool
    {
        return (bool) $this->evaluate($this->shouldAutosize);
    }
}
