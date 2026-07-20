<?php

namespace Kraftdo\Shared;

use Illuminate\Support\ServiceProvider;
use Kraftdo\Shared\Persona\PersonaResolverInterface;
use Kraftdo\Shared\Persona\Resolvers\ApiPersonaResolver;
use Kraftdo\Shared\Persona\Resolvers\LocalPersonaResolver;

/**
 * Código compartido por los sistemas del ecosistema KraftDo (NFC, Sitio, CRM,
 * Hub). Lo que vive aquí estaba duplicado en los cuatro repos.
 */
class KraftdoSharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Resolver de personas: `api` consulta el maestro; `local`, la base propia.
        $this->app->bind(PersonaResolverInterface::class, function () {
            return config('kraftdo-shared.personas.driver') === 'api'
                ? $this->app->make(ApiPersonaResolver::class)
                : $this->app->make(LocalPersonaResolver::class);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/kraftdo-shared.php' => config_path('kraftdo-shared.php'),
        ], 'kraftdo-shared-config');

        $this->mergeConfigFrom(__DIR__.'/../config/kraftdo-shared.php', 'kraftdo-shared');
    }
}
