<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Contracts;

use Filament\Actions\Action;
use Filament\Forms\Components\Field;

interface HasMessageInput
{
    public function getAttachmentAreaComponent(): ?Field;

    public function getTextareaComponent(): ?Field;

    public function getUploadAttachmentAction(): ?Action;

    public function getSendMessageAction(): ?Action;
}
