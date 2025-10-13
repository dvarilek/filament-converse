<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Concerns;

use Dvarilek\FilamentConverse\Livewire\ConversationList;
use Dvarilek\FilamentConverse\Livewire\ConversationThread;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;

/**
 * @mixin Page
 */
trait HasMessageComponent
{
    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return [
            'xl' => 3,
            'lg' => 3,
            'md' => 3,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getWidgetsContentComponent()
            ]);
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema([
                Livewire::make(ConversationList::class)
                    ->columnSpan([
                        'xl' => 1,
                        'lg' => 1,
                        'md' => 1,
                    ]),
                Livewire::make(ConversationThread::class)
                    ->columnSpan([
                        'xl' => 2,
                        'lg' => 2,
                        'md' => 2,
                    ]),
            ]);
    }
}