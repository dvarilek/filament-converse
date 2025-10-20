<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Tests\Tests;

use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return Panel::make()
            ->id('default');
    }
}
