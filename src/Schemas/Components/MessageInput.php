<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Filament\Forms\Components\MarkdownEditor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
    }

    /**
     * @return list<TemporaryUploadedFile>
     */
    public function getUploadedFileAttachments(): array
    {
        return data_get($this->getLivewire(), "componentFileAttachments.{$this->getStatePath()}");
    }
}
