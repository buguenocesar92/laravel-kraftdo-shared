<?php

namespace Kraftdo\Shared;

use Illuminate\Support\ServiceProvider;
use Kraftdo\Shared\Persona\PersonaResolverInterface;
use Kraftdo\Shared\Persona\Resolvers\ApiPersonaResolver;
use Kraftdo\Shared\Persona\Resolvers\LocalPersonaResolver;
use Kraftdo\Shared\Seguridad\CredencialesDePlantilla;

/**
 * Código compartido por los sistemas del ecosistema KraftDo (NFC, Sitio, CRM,
 * Hub). Lo que vive aquí estaba duplicado en los cuatro repos.
 */
class KraftdoSharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // La configuración tiene que estar completa antes de que alguien
        // resuelva un servicio: por eso el merge va en register() y no en boot().
        $this->mergeConfigFrom(__DIR__.'/../config/kraftdo-shared.php', 'kraftdo-shared');
        $this->mergeConfigFrom(__DIR__.'/../config/credenciales-de-plantilla.php', 'credenciales-de-plantilla');

        // Resolver de personas: `api` consulta el maestro; `local`, la base propia.
        $this->app->bind(PersonaResolverInterface::class, function () {
            return config('kraftdo-shared.personas.driver') === 'api'
                ? $this->app->make(ApiPersonaResolver::class)
                : $this->app->make(LocalPersonaResolver::class);
        });
    }

    public function boot(): void
    {
        // Va lo primero, antes de cualquier otra cosa del arranque: si las
        // credenciales de plantilla llegaron a producción, no seguimos. Se
        // llama sola —el sistema no escribe ni una línea— porque es el paso que
        // nadie escribe. Ver el docblock de la clase.
        CredencialesDePlantilla::comprobar();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kraftdo-shared.php' => config_path('kraftdo-shared.php'),
            ], 'kraftdo-shared-config');

            $this->publishes([
                __DIR__.'/../config/credenciales-de-plantilla.php' => config_path('credenciales-de-plantilla.php'),
            ], 'credenciales-de-plantilla-config');
        }
    }
}
