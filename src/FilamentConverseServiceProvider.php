<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentConverseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-converse')
            ->hasViews('filament-converse')
            ->hasTranslations()
            ->hasMigrations(
                '1_create_conversations_table',
                '2_create_conversation_participants_table',
                '3_create_messages_table'
            )
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('dvarilek/filament-converse');
            });
    }
}
