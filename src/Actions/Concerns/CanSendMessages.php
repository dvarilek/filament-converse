<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions\Concerns;

use Closure;
use Dvarilek\FilamentConverse\Schemas\Components\AttachmentArea;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Actions;

trait CanSendMessages
{
    protected ?Closure $getAttachmentAreaComponentUsing = null;

    protected ?Closure $getTextareaComponentUsing = null;

    protected ?Closure $getUploadAttachmentActionUsing = null;

    protected ?Closure $getSendMessageActionUsing = null;

    protected ?Closure $modifyAttachmentAreaComponentUsing = null;

    protected ?Closure $modifyTextareaComponentUsing = null;

    protected ?Closure $modifyUploadAttachmentActionUsing = null;

    protected ?Closure $modifySendMessageActionUsing = null;

    public function getAttachmentAreaComponentUsing(?Closure $callback = null): static
    {
        $this->getAttachmentAreaComponentUsing = $callback;

        return $this;
    }

    public function getTextareaComponentUsing(?Closure $callback = null): static
    {
        $this->getTextareaComponentUsing = $callback;

        return $this;
    }

    public function getUploadAttachmentActionUsing(?Closure $callback = null): static
    {
        $this->getUploadAttachmentActionUsing = $callback;

        return $this;
    }

    public function getSendMessageActionUsing(?Closure $callback = null): static
    {
        $this->getSendMessageActionUsing = $callback;

        return $this;
    }

    public function modifyAttachmentAreaComponentUsing(?Closure $callback = null): static
    {
        $this->modifyAttachmentAreaComponentUsing = $callback;

        return $this;
    }

    public function modifyTextareaComponentUsing(?Closure $callback = null): static
    {
        $this->modifyTextareaComponentUsing = $callback;

        return $this;
    }

    public function modifyUploadAttachmentActionUsing(?Closure $callback = null): static
    {
        $this->modifyUploadAttachmentActionUsing = $callback;

        return $this;
    }

    public function modifySendMessageActionUsing(?Closure $callback = null): static
    {
        $this->modifySendMessageActionUsing = $callback;

        return $this;
    }

    public function getAttachmentAreaComponent(): ?Field
    {
        $component = $this->evaluate($this->getAttachmentAreaComponentUsing);

        if ($this->modifyAttachmentAreaComponentUsing) {
            $component = $this->evaluate($this->modifyAttachmentAreaComponentUsing, [
                'component' => $component,
            ], [
                AttachmentArea::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getTextareaComponent(): ?Field
    {
        $component = $this->evaluate($this->getTextareaComponentUsing);

        if ($this->modifyTextareaComponentUsing) {
            $component = $this->evaluate($this->modifyTextareaComponentUsing, [
                'component' => $component,
            ], [
                Textarea::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getUploadAttachmentAction(): ?Action
    {
        $action = $this->evaluate($this->getUploadAttachmentActionUsing);

        if ($this->modifyUploadAttachmentActionUsing) {
            $action = $this->evaluate($this->modifyUploadAttachmentActionUsing, [
                'action' => $action,
            ], [
                Actions::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function getSendMessageAction(): ?Action
    {
        $action = $this->evaluate($this->getSendMessageActionUsing);

        if ($this->modifySendMessageActionUsing) {
            $action = $this->evaluate($this->modifySendMessageActionUsing, [
                'action' => $action,
            ]) ?? $action;
        }

        return $action;
    }
}
