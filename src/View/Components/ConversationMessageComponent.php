<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\View\Components;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\Contracts\HasColor;

class ConversationMessageComponent implements HasColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = FilamentColor::getColor('gray');

        ksort($color);

        foreach (array_keys($color) as $shade) {
            if (Color::isNonTextContrastRatioAccessible('oklch(1 0 0)', $color[$shade])) {
                $text = $shade;

                break;
            }
        }

        $text ??= 900;

        krsort($color);

        $lightestDarkGrayBg = $gray[800];

        foreach (array_keys($color) as $shade) {
            if ($shade > 600) {
                continue;
            }

            if (Color::isNonTextContrastRatioAccessible($lightestDarkGrayBg, $color[$shade])) {
                $darkText = $shade;

                break;
            }
        }

        $darkText ??= 200;

        return [
            'text' => $text,
            'dark:text' => $darkText,
        ];
    }
}
