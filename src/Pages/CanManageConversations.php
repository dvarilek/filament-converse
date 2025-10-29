<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Pages;

use Dvarilek\FilamentConverse\Livewire\Concerns\CanFilterConversations;
use Dvarilek\FilamentConverse\Livewire\Concerns\CanSearchConversations;
use Dvarilek\FilamentConverse\Livewire\Concerns\HasConversations;
use Dvarilek\FilamentConverse\Schemas\Components\Converse;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @mixin Page
 */
trait CanManageConversations
{
    use CanFilterConversations;
    use CanSearchConversations;
    use HasConversations;

    public function getHeading(): Htmlable | string
    {
        return '';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Converse::make(),
            ]);
    }
}
