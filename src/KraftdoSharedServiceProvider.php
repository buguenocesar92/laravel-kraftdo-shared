<?php

namespace Kraftdo\Shared;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Kraftdo\Shared\Persona\PersonaResolverInterface;
use Kraftdo\Shared\Persona\Resolvers\ApiPersonaResolver;
use Kraftdo\Shared\Persona\Resolvers\LocalPersonaResolver;
use Kraftdo\Shared\Privacidad\BitacoraEnBaseDeDatos;
use Kraftdo\Shared\Privacidad\Console\AplicarRetencionCommand;
use Kraftdo\Shared\Privacidad\Console\CifrarTextoLibreCommand;
use Kraftdo\Shared\Privacidad\Console\DiagnosticoCommand;
use Kraftdo\Shared\Privacidad\Console\ExportarRatCommand;
use Kraftdo\Shared\Privacidad\Contratos\RegistroDeEvidencia;
use Kraftdo\Shared\Privacidad\Contratos\ResuelveTitularesVencidos;
use Kraftdo\Shared\Privacidad\NingunTitularVencido;
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
        $this->mergeConfigFrom(__DIR__.'/../config/privacidad.php', 'privacidad');

        // Resolver de personas: `api` consulta el maestro; `local`, la base propia.
        $this->app->bind(PersonaResolverInterface::class, function () {
            return config('kraftdo-shared.personas.driver') === 'api'
                ? $this->app->make(ApiPersonaResolver::class)
                : $this->app->make(LocalPersonaResolver::class);
        });

        // Enlaces por defecto del módulo de privacidad: un sistema que ya tenga
        // su propia trazabilidad puede sustituirlos sin tocar el módulo.
        $this->app->bind(RegistroDeEvidencia::class, BitacoraEnBaseDeDatos::class);
        $this->app->bind(ResuelveTitularesVencidos::class, NingunTitularVencido::class);
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

            $this->publishes([
                __DIR__.'/../config/privacidad.php' => config_path('privacidad.php'),
            ], 'privacidad-config');

            $this->commands([
                AplicarRetencionCommand::class,
                CifrarTextoLibreCommand::class,
                DiagnosticoCommand::class,
                ExportarRatCommand::class,
            ]);
        }

        $this->agendarRetencion();
    }

    /**
     * Agenda la retención SOLO si el sistema declaró la hora.
     *
     * El paquete la agenda en vez de dejarlo escrito en el README porque eso
     * ya se probó y no funcionó: el módulo quedaba instalado y migrado, y
     * `schedule:list` no listaba el comando. La obligación legal de suprimir
     * dependía de que alguien se acordara de tipearlo.
     *
     * Y hace falta una clave de configuración en vez de agendarlo siempre:
     * instalar un paquete no puede poner a correr un destructivo en cuatro
     * sistemas. Sin `privacidad.retencion.hora` no se agenda nada.
     *
     * `withoutOverlapping()` impide que la corrida de mañana entre encima de
     * la de hoy si no terminó; `onOneServer()` impide que dos servidores del
     * mismo despliegue la corran a la vez.
     */
    private function agendarRetencion(): void
    {
        // callAfterResolving y no `app(Schedule::class)`: resolver el scheduler
        // acá adentro lo construiría en toda petición web, donde no se usa. La
        // hora se lee DENTRO del callback: leerla en el boot la congela antes
        // de que un adoptante (o una prueba) pueda ajustar la configuración.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $hora = trim((string) config('privacidad.retencion.hora', ''));

            if ($hora === '') {
                return;
            }

            $schedule->command(AplicarRetencionCommand::class, ['--ejecutar'])
                ->dailyAt($hora)
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
