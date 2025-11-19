<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Filament\Forms\Components\Contracts\CanBeLengthConstrained as CanBeLengthConstrainedContract;
use Filament\Forms\Components\MarkdownEditor;

class MessageInput extends MarkdownEditor implements CanBeLengthConstrainedContract
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::message-input';

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();

        $this->autofocus();

        $this->minHeight('2rem');

        $this->fileAttachments(false);

        $this->disableToolbarButtons([
            'codeBlock',
        ]);
    }
}
