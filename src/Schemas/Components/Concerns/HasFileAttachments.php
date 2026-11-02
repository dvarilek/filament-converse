<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use BackedEnum;
use Closure;
use Filament\Schemas\Components\Icon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\HtmlString;

trait HasFileAttachments
{
    protected bool | Closure $shouldShowOnlyImageAttachment = true;

    protected bool | Closure $shouldPreviewImageAttachment = true;

    protected ?Closure $getFileAttachmentNameUsing = null;

    protected ?Closure $getFileAttachmentToolbarUsing = null;

    protected string | BackedEnum | Htmlable | Closure | null $fileAttachmentIcon = null;

    protected string | array | Closure | null $fileAttachmentIconColor = 'primary';

    protected ?Closure $fileAttachmentMimeTypeBadgeLabel = null;

    protected string | BackedEnum | Closure | null $fileAttachmentMimeTypeBadgeIcon = null;

    /**
     * @var string | array<string> | Closure | null
     */
    protected string | array | Closure | null $fileAttachmentMimeTypeBadgeColor = 'gray';

    public function showOnlyImageAttachment(bool | Closure $condition = true): static
    {
        $this->shouldShowOnlyImageAttachment = $condition;

        return $this;
    }

    public function previewImageAttachment(bool | Closure $condition = true): static
    {
        $this->shouldPreviewImageAttachment = $condition;

        return $this;
    }

    public function getFileAttachmentNameUsing(?Closure $callback): static
    {
        $this->getFileAttachmentNameUsing = $callback;

        return $this;
    }

    public function getFileAttachmentToolbarUsing(?Closure $callback): static
    {
        $this->getFileAttachmentToolbarUsing = $callback;

        return $this;
    }

    public function fileAttachmentIcon(string | BackedEnum | Htmlable | Closure | null $icon = null): static
    {
        $this->fileAttachmentIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function fileAttachmentIconColor(string | array | Closure | null $color = null): static
    {
        $this->fileAttachmentIconColor = $color;

        return $this;
    }

    public function fileAttachmentMimeTypeBadgeLabel(?Closure $callback = null): static
    {
        $this->fileAttachmentMimeTypeBadgeLabel = $callback;

        return $this;
    }

    public function fileAttachmentMimeTypeBadgeIcon(string | BackedEnum | Closure | null $icon = null): static
    {
        $this->fileAttachmentMimeTypeBadgeIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function fileAttachmentMimeTypeBadgeColor(string | array | Closure | null $color = null): static
    {
        $this->fileAttachmentMimeTypeBadgeColor = $color;

        return $this;
    }

    public function isImageMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    public function shouldShowOnlyImageAttachment(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): bool
    {
        return (bool) $this->evaluate($this->shouldShowOnlyImageAttachment, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]);
    }

    public function shouldPreviewImageAttachment(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): bool
    {
        return (bool) $this->evaluate($this->shouldPreviewImageAttachment, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]);
    }

    public function getFileAttachmentName(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): string | Htmlable
    {
        return $this->evaluate($this->getFileAttachmentNameUsing, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]) ?? $attachmentOriginalName;
    }

    public function getFileAttachmentToolbar(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): string | Htmlable | null
    {
        return $this->evaluate($this->getFileAttachmentToolbarUsing, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]);
    }

    public function getFileAttachmentIcon(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): Htmlable | Icon | null
    {
        $icon = $this->evaluate($this->fileAttachmentIcon, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]);

        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if (is_string($icon) || $icon instanceof BackedEnum) {
            $icon = Icon::make($icon);
        }

        if ($icon instanceof Icon) {
            $icon->color(
                $this->getFileAttachmentIconColor($attachmentName, $attachmentOriginalName, $attachmentMimeType, $data),
            );
        }

        return $icon;
    }

    public function getFileAttachmentIconColor(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): string | array
    {
        return $this->evaluate($this->fileAttachmentIconColor, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]) ?? 'primary';
    }

    public function getFileAttachmentMimeTypeBadgeLabel(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): string | Htmlable | null
    {
        return $this->evaluate($this->fileAttachmentMimeTypeBadgeLabel, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]);
    }

    public function getFileAttachmentMimeTypeBadgeIcon(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): string | BackedEnum | null
    {
        return $this->evaluate($this->fileAttachmentMimeTypeBadgeIcon, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]);
    }

    /**
     * @return string | array<string>
     */
    public function getFileAttachmentMimeTypeBadgeColor(string $attachmentName, string $attachmentOriginalName, string $attachmentMimeType, array $data): string | array
    {
        return $this->evaluate($this->fileAttachmentMimeTypeBadgeColor, [
            'attachmentName' => $attachmentName,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'data' => $data,
        ]) ?? 'gray';
    }
}
