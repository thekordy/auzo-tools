<?php

namespace Kordy\AuzoTools;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Kordy\AuzoTools\Facades\GenerateAbilities as GenerateAbilitiesFacade;
use Kordy\AuzoTools\Services\AuzoToolsMiddleware;
use Kordy\AuzoTools\Services\GenerateAbilities;

class AuzoToolsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap AuzoTools application services.
     *
     * @param Router $router
     */
    public function boot(Router $router)
    {
        // Load auzo middleware
        $router->middleware('auzo.acl', AuzoToolsMiddleware::class);

        // Load auzo validation can rule
        Validator::extend('auzo.can', 'Kordy\AuzoTools\Services\ValidationRules@can');

        // Load auzo language files
        $this->loadTranslationsFrom(__DIR__.'/Translations', 'auzoTools');

        // Publish the configuration file to the application config folder
        $this->publishes([
            __DIR__.'/Config/auzo_tools.php' => config_path('auzo_tools.php'),
        ], 'config');

        // Publish translation files
        $this->publishes([
            __DIR__.'/Translations' => base_path('resources/lang/vendor/auzo-tools'),
        ], 'translations');
    }

    /**
     * Register AuzoTools application services.
     *
     * @return void
     */
    public function register()
    {
        // Default configuration file
        $this->mergeConfigFrom(
            __DIR__.'/Config/auzo_tools.php', 'auzoTools'
        );

        $this->registerModelBindings();
        $this->registerFacadesAliases();
    }

    /**
     * Bind the Services into the IoC.
     */
    protected function registerModelBindings()
    {
        $this->app->bind('GenerateAbilities', GenerateAbilities::class);
    }

    /**
     * Create aliases for the Model Bindings.
     *
     * @see registerModelBindings
     */
    protected function registerFacadesAliases()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('GenerateAbilities', GenerateAbilitiesFacade::class);
    }
}
