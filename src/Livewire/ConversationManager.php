<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire;

use Dvarilek\FilamentConverse\Livewire\Concerns\InteractsWithConversationManager;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConversationManager extends Component implements HasActions, HasConversationSchema, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithConversationManager;
    use InteractsWithSchemas;

    public ?array $data = [];

    #[Locked]
    public ?string $conversationSchemaConfiguration = null;

    public function mount(?string $conversationSchemaConfiguration = null): void
    {
        $this->conversationSchemaConfiguration = $conversationSchemaConfiguration;

        $this->content->fill();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->makeConversationSchema(),
            ])
            ->statePath('data');
    }

    public function _finishUpload($name, $tmpPath, $isMultiple)
    {
        if (FileUploadConfiguration::shouldCleanupOldUploads()) {
            $this->cleanupOldUploads();
        }

        if ($isMultiple) {
            $file = collect($tmpPath)->map(function ($i) {
                return TemporaryUploadedFile::createFromLivewire($i);
            })->toArray();
            $this->dispatch('upload:finished', name: $name, tmpFilenames: collect($file)->map->getFilename()->toArray())->self();
        } else {
            $file = TemporaryUploadedFile::createFromLivewire($tmpPath[0]);
            $this->dispatch('upload:finished', name: $name, tmpFilenames: [$file->getFilename()])->self();
        }

        $file = array_merge($this->getPropertyValue($name) ?? [], $file);

        app('livewire')->updateProperty($this, $name, $file);
    }

    public function render(): View
    {
        return view('filament-converse::livewire.conversation-manager');
    }
}
