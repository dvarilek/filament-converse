<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Filament\Forms\Components\MarkdownEditor;

class MessageInput extends MarkdownEditor
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

        $this->fileAttachmentsAcceptedFileTypes([
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
