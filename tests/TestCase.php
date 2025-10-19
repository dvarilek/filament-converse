<?php

namespace Dvarilek\FilamentConverse\Tests;

use Dvarilek\FilamentConverse\FilamentConverseServiceProvider;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        (require __DIR__ . '/../database/migrations/1_create_conversations_table.php')->up();
        (require __DIR__ . '/../database/migrations/2_create_conversation_participations_table.php')->up();
        (require __DIR__ . '/../database/migrations/3_create_messages_table.php')->up();
    }

    /**
     * @return list<class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            FilamentConverseServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('filament-converse.user_model', User::class);
    }
}
