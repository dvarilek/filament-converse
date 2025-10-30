<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Livewire\Livewire;

class FilamentConverseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-converse')
            ->hasViews('filament-converse')
            ->hasConfigFile('filament-converse')
            ->hasTranslations()
            ->hasMigrations(
                '1_create_conversations_table',
                '2_create_conversation_participations_table',
                '3_create_messages_table'
            )
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishMigrations()
                    ->publishConfigFile()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('dvarilek/filament-converse');
            });
    }

    public function packageBooted(): void
    {
        Livewire::component('filament-converse.livewire.conversation-manager', ConversationManager::class);
    }

    /**
     * @return class-string<Authenticatable & Model>
     */
    public static function getFilamentConverseUserModel(): string
    {
        /* @var class-string<Authenticatable & Model> $model */
        $model = config('filament-converse.user_model');

        if (! is_subclass_of($model, Model::class)) {
            throw new Exception('The user model must be an instance of [Illuminate\Database\Eloquent\Model].');
        }

        if (! is_subclass_of($model, Authenticatable::class)) {
            throw new Exception('The user model must be an instance of [Illuminate\Contracts\Auth\Authenticatable].');
        }

        if (! in_array(Conversable::class, class_uses_recursive($model))) {
            FilamentConverseException::throwInvalidConversableUserException($model);
        }

        return $model;
    }
}
