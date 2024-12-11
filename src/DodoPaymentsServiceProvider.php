<?php
declare(strict_types=1);

namespace Codeplugtech\DodoPayments;

use Codeplugtech\DodoPayments\Console\UpdateCancelledSubscriptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

class DodoPaymentsServiceProvider extends \Illuminate\Support\ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dodo.php', 'dodo'
        );
    }

    public function boot(): void
    {
        $this->bootRoute();
        $this->bootPublishing();
        $this->registerCommands();
        $this->loadFactories();
    }


    protected function bootRoute(): void
    {
        Route::group([
            'prefix' => config('dodo.path'),
            'namespace' => 'Codeplugtech\DodoPayments\Http\Controllers',
            'as' => 'dodo.',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Boot the package's publishable resources.
     *
     * @return void
     */
    protected function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/dodo.php' => $this->app->configPath('dodo.php'),
            ], 'dodo-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'dodo-migrations');
        }
    }

    protected function loadFactories()
    {
        if ($this->app->environment('testing')) {
            Factory::guessFactoryNamesUsing(
                fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
            );
        }
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateCancelledSubscriptions::class,
            ]);
        }
    }

}
