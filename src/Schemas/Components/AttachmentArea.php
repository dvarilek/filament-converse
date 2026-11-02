<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use BackedEnum;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AttachmentArea extends Field
{
    use Concerns\HasFileAttachments;
    use HasKey;

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::attachment-area';

    protected string | Closure | null $uploadDropZoneRef = 'uploadDropZoneRef';

    /**
     * @var string | array<string> | Closure | null
     */
    protected string | array | Closure | null $uploadModalIconColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $uploadModalIcon = null;

    protected string | Htmlable | Closure | null $uploadModalHeading = null;

    protected string | Htmlable | Closure | null $uploadModalDescription = null;

    protected bool | Closure $isMultiple = true;

    protected int | Closure | null $maxFiles = null;

    protected int | Closure | null $minFiles = null;

    /**
     * @var array<string> | Arrayable | Closure | null
     */
    protected array | Arrayable | Closure | null $acceptedFileTypes = [
        'image/png',
        'image/jpeg',
        'audio/mpeg',
        'video/mp4',
        'video/mpeg',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    protected int | Closure | null $maxSize = 12288;

    protected int | Closure | null $minSize = null;

    protected string | Closure | null $directory = null;

    protected string | Closure | null $diskName = null;

    protected string | Closure | null $visibility = null;

    protected ?Closure $getUploadedFileNameForStorageUsing = null;

    protected ?Closure $saveUploadedFileUsing = null;

    protected ?Closure $getSavedFileAttachmentUrlUsing = null;

    protected bool | Closure $isRemovable = true;

    /**
     * @var array<string>
     */
    protected const ARRAY_VALIDATION_RULES = [
        'filled',
        'prohibited',
        'prohibited_if',
        'prohibited_unless',
        'required_if',
        'required_if_accepted',
        'required_if_declined',
        'required_unless',
        'required_with',
        'required_with_all',
        'required_without',
        'required_without_all',
    ];

    protected function setUp(): void
    {
        $this->uploadModalDescription(__('filament-converse::conversation-thread.upload-modal.description'));

        $this->beforeStateDehydrated(static function (AttachmentArea $component): void {
            $component->saveUploadedFiles();
            $component->deleteRemovedFiles();
        }, shouldUpdateValidatedStateAfter: true);

        $this->getUploadedFileNameForStorageUsing(static function (AttachmentArea $component, TemporaryUploadedFile $file) {
            return Str::ulid() . '.' . $file->getClientOriginalExtension();
        });

        $this->saveUploadedFileUsing(static function (AttachmentArea $component, TemporaryUploadedFile $file): ?string {
            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (UnableToCheckFileExistence $exception) {
                return null;
            }

            $storeMethod = $component->getVisibility() === 'public' ? 'storePubliclyAs' : 'storeAs';
            $uploadedFileNameForStorage = $component->getUploadedFileNameForStorage($file);

            $file->{$storeMethod}(
                $component->getDirectory(),
                $uploadedFileNameForStorage,
                $component->getDiskName(),
            );

            return $uploadedFileNameForStorage;
        });
    }

    public function uploadDropZoneRef(string | Closure | null $ref): static
    {
        $this->uploadDropZoneRef = $ref;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function uploadModalIconColor(string | array | Closure | null $color): static
    {
        $this->uploadModalIconColor = $color;

        return $this;
    }

    public function uploadModalIcon(string | BackedEnum | Htmlable | Closure | null $icon): static
    {
        $this->uploadModalIcon = filled($icon) ? $icon : false;

        return $this;
    }

    public function uploadModalHeading(string | Htmlable | Closure | null $heading): static
    {
        $this->uploadModalHeading = $heading;

        return $this;
    }

    public function uploadModalDescription(string | Htmlable | Closure | null $description): static
    {
        $this->uploadModalDescription = $description;

        return $this;
    }

    public function multiple(bool | Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function maxFiles(int | Closure | null $maxFiles): static
    {
        $this->maxFiles = $maxFiles;

        return $this;
    }

    public function minFiles(int | Closure | null $count): static
    {
        $this->minFiles = $count;

        return $this;
    }

    /**
     * @param  array<string> | Arrayable | Closure  $types
     */
    public function acceptedFileTypes(array | Arrayable | Closure $types): static
    {
        $this->acceptedFileTypes = $types;

        $this->rule(static function (AttachmentArea $component) {
            $types = implode(',', ($component->getAcceptedFileTypes() ?? []));

            return "mimetypes:{$types}";
        });

        return $this;
    }

    public function maxSize(int | Closure | null $size): static
    {
        $this->maxSize = $size;

        $this->rule(static function (AttachmentArea $component): string {
            $size = $component->getMaxSize();

            return "max:{$size}";
        });

        return $this;
    }

    public function minSize(int | Closure | null $size): static
    {
        $this->minSize = $size;

        $this->rule(static function (AttachmentArea $component): string {
            $size = $component->getMinSize();

            return "min:{$size}";
        });

        return $this;
    }

    public function directory(string | Closure | null $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function diskName(string | Closure | null $diskName): static
    {
        $this->diskName = $diskName;

        return $this;
    }

    public function visibility(string | Closure | null $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getUploadedFileNameForStorageUsing(?Closure $callback): static
    {
        $this->getUploadedFileNameForStorageUsing = $callback;

        return $this;
    }

    public function saveUploadedFileUsing(?Closure $callback): static
    {
        $this->saveUploadedFileUsing = $callback;

        return $this;
    }

    public function getSavedFileAttachmentUrlUsing(?Closure $callback): static
    {
        $this->getSavedFileAttachmentUrlUsing = $callback;

        return $this;
    }

    public function removable(bool | Closure $condition = true): static
    {
        $this->isRemovable = $condition;

        return $this;
    }

    public function getUploadDropZoneRef(): ?string
    {
        return $this->evaluate($this->uploadDropZoneRef);
    }

    /**
     * @return string | array<string>
     */
    public function getUploadModalIconColor(): string | array
    {
        return $this->evaluate($this->uploadModalIconColor) ?? 'primary';
    }

    public function getUploadModalIcon(): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate($this->uploadModalIcon) ?? Heroicon::PaperClip;

        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if ($icon === false) {
            return null;
        }

        return $icon;
    }

    public function getUploadModalHeading(): string | Htmlable
    {
        return $this->evaluate($this->uploadModalHeading)
            ?? __('filament-converse::conversation-thread.upload-modal.heading');
    }

    public function getUploadModalDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->uploadModalDescription);
    }

    public function getMaxFiles(): ?int
    {
        return $this->evaluate($this->maxFiles);
    }

    public function getMinFiles(): ?int
    {
        return $this->evaluate($this->minFiles);
    }

    public function getDirectory(): ?string
    {
        return $this->evaluate($this->directory);
    }

    public function getDisk(): Filesystem
    {
        return Storage::disk($this->getDiskName());
    }

    public function getDiskName(): string
    {
        $name = $this->evaluate($this->diskName);

        if (filled($name)) {
            return $name;
        }

        $defaultName = config('filament.default_filesystem_disk');

        if (
            ($defaultName === 'public')
            && ($this->getCustomVisibility() === 'private')
        ) {
            return 'local';
        }

        return $defaultName;
    }

    public function getVisibility(): string
    {
        $visibility = $this->getCustomVisibility();

        if (filled($visibility)) {
            return $visibility;
        }

        return ($this->getDiskName() === 'public') ? 'public' : 'private';
    }

    public function getCustomVisibility(): ?string
    {
        return $this->evaluate($this->visibility);
    }

    public function getUploadedFileNameForStorage(TemporaryUploadedFile $file): string
    {
        return $this->evaluate($this->getUploadedFileNameForStorageUsing, [
            'file' => $file,
        ]);
    }

    /**
     * @return array<string> | null
     */
    public function getAcceptedFileTypes(): ?array
    {
        $types = $this->evaluate($this->acceptedFileTypes);

        if ($types instanceof Arrayable) {
            $types = $types->toArray();
        }

        return $types;
    }

    public function getMaxSize(): ?int
    {
        return $this->evaluate($this->maxSize);
    }

    public function getMinSize(): ?int
    {
        return $this->evaluate($this->minSize);
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function getValidationRules(): array
    {
        $rules = [
            $this->getRequiredValidationRule(),
            'array',
        ];

        if (! $this->isMultiple()) {
            $rules[] = 'max:1';
        } elseif (filled($count = $this->getMaxFiles())) {
            $rules[] = "max:{$count}";
        }

        if (filled($count = $this->getMinFiles())) {
            $rules[] = "min:{$count}";
        }

        $arrayRules = [];
        $fileRules = [];

        foreach (parent::getValidationRules() as $rule) {
            if ($this->isArrayValidationRule($rule)) {
                $arrayRules[] = $rule;
            } else {
                $fileRules[] = $rule;
            }
        }

        $rules = [
            ...$rules,
            ...$arrayRules,
        ];

        $rules[] = function (string $attribute, array $value, Closure $fail) use ($fileRules): void {
            $files = array_filter($value, fn (TemporaryUploadedFile | string $file): bool => $file instanceof TemporaryUploadedFile);

            $name = $this->getName();

            $validationMessages = $this->getValidationMessages();

            $validator = Validator::make(
                [$name => $files],
                ["{$name}.*" => ['file', ...$fileRules]],
                $validationMessages ? ["{$name}.*" => $validationMessages] : [],
                ["{$name}.*" => $this->getValidationAttribute()],
            );

            if (! $validator->fails()) {
                return;
            }

            $fail($validator->errors()->first());
        };

        return $rules;
    }

    protected function isArrayValidationRule(mixed $rule): bool
    {
        if (! is_string($rule)) {
            return false;
        }

        $ruleName = strtolower(explode(':', $rule)[0]);

        return in_array($ruleName, static::ARRAY_VALIDATION_RULES, strict: true);
    }

    public function getSavedFileAttachmentUrl(mixed $file): ?string
    {
        if ($this->getSavedFileAttachmentUrlUsing) {
            return $this->evaluate($this->getSavedFileAttachmentUrlUsing, [
                'file' => $file,
            ]);
        }

        /** @var FilesystemAdapter $storage */
        $storage = $this->getDisk();

        try {
            if (! $storage->exists($file)) {
                return null;
            }
        } catch (UnableToCheckFileExistence $exception) {
            return null;
        }

        if ($this->getVisibility() === 'private') {
            try {
                return $storage->temporaryUrl(
                    $file,
                    now()->addMinutes(30)->endOfHour(),
                );
            } catch (Throwable $exception) {
                // This driver does not support creating temporary URLs.
            }
        }

        return $storage->url($file);
    }

    public function isRemovable(string | TemporaryUploadedFile $fileAttachment): bool
    {
        return (bool) $this->evaluate($this->isRemovable, [
            'fileAttachment' => $fileAttachment,
        ]);
    }

    public function saveUploadedFiles(): void
    {
        $rawState = Arr::wrap($this->getRawState());

        if (blank($rawState)) {
            $this->rawState([]);

            return;
        }

        $uploadedFiles = [];

        foreach ($rawState as $key => $fileAttachment) {
            if (is_string($key) && is_string($fileAttachment)) {
                $uploadedFiles[$key] = $fileAttachment;

                continue;
            }

            $callback = $this->saveUploadedFileUsing;

            if (! $callback) {
                $fileAttachment->delete();
                $uploadedFiles[$this->getUploadedFileNameForStorage($fileAttachment)] = $fileAttachment;

                continue;
            }

            $storedFileName = $this->evaluate($callback, [
                'file' => $fileAttachment,
            ]);

            if (blank($storedFileName)) {
                continue;
            }

            $fileAttachment->delete();
            $uploadedFiles[$storedFileName] = $fileAttachment;
        }

        $this->rawState($uploadedFiles);
        $this->callAfterStateUpdated();
    }

    public function deleteRemovedFiles(): void
    {
        /* @var ?Model $record */
        $record = $this->getRecord();

        if (! $record instanceof Model) {
            return;
        }

        $disk = $this->getDisk();
        $rawState = $this->getRawState();

        foreach (Arr::wrap($record->getAttribute($this->getName())) as $savedAttachmentName) {
            if (array_key_exists($savedAttachmentName, $rawState)) {
                continue;
            }

            if (! $this->isRemovable($savedAttachmentName)) {
                continue;
            }

            if ($disk->exists($savedAttachmentName)) {
                $disk->delete($savedAttachmentName);
            }
        }
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function removeUploadedFile(string $attachmentName): string | TemporaryUploadedFile | null
    {
        if ($this->isDisabled()) {
            return null;
        }

        $fileAttachment = null;
        $rawState = Arr::wrap($this->getRawState());

        foreach ($rawState as $key => $fileAttachment) {
            $isTemporaryUploadedFile = $fileAttachment instanceof TemporaryUploadedFile;

            if ($isTemporaryUploadedFile ? $fileAttachment->getFilename() !== $attachmentName : $key !== $attachmentName) {
                continue;
            }

            if (! $this->isRemovable($isTemporaryUploadedFile ? $fileAttachment : $key)) {
                continue;
            }

            if ($fileAttachment instanceof TemporaryUploadedFile) {
                $fileAttachment->delete();
            }

            unset($rawState[$key]);
        }

        $this->rawState($rawState);
        $this->callAfterStateUpdated();

        return $fileAttachment;
    }
}
