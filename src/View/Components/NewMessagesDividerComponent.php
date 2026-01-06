<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\View\Components;

use Filament\Support\View\Components\Contracts\HasColor;

class NewMessagesDividerComponent implements HasColor
{
    /**
     * @param  array<int, string>  $color
     * @return array{}
     */
    public function getColorMap(array $color): array
    {
        return [];
    }
}
