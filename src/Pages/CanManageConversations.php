<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Pages;

use Dvarilek\FilamentConverse\Livewire\ConversationListLivewireComponent;
use Dvarilek\FilamentConverse\Livewire\ConversationThreadLivewireComponent;;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Dvarilek\FilamentConverse\Components\ConversationList;
use Dvarilek\FilamentConverse\Components\ConversationThread;
use Filament\Schemas\Schema;

/**
 * @mixin Page
 */
trait CanManageConversations
{
    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return [
            'xl' => 6,
            'lg' => 6,
            'md' => 6,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getConversationComponents()
            ]);
    }

    protected function getConversationComponents(): Component
    {
        return Grid::make($this->getColumns())
            ->schema([
                $this->conversationList($this->getConversationListComponent()),
                $this->conversationThread($this->getConversationThreadComponent()),
            ]);
    }

    protected function conversationList(ConversationList $component): ConversationList
    {
        return $component;
    }

    protected function conversationThread(ConversationThread $component): ConversationThread
    {
        return $component;
    }

    protected function getConversationListComponent(): ConversationList
    {
        return ConversationList::make()
            ->columnSpan([
                'xl' => 2,
                'lg' => 3,
                'md' => 3,
            ]);
    }

    protected function getConversationThreadComponent(): ConversationThread
    {
        return ConversationThread::make()
            ->columnSpan([
                'xl' => 4,
                'lg' => 3,
                'md' => 3,
            ]);
    }
}
