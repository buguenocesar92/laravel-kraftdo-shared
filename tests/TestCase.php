<?php

namespace Kraftdo\Shared\Tests;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kraftdo\Shared\KraftdoSharedServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

/**
 * Base de las pruebas del paquete.
 *
 * Registra el service provider, que es lo que hace que las pruebas ejerciten lo
 * mismo que un sistema real: los comandos, los bindings y las migraciones del
 * módulo de privacidad existen porque el provider los registra, no porque la
 * prueba los arme a mano.
 */
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            KraftdoSharedServiceProvider::class,
            // Permission es dependencia opcional (ver "suggest" en composer.json):
            // RolePolicy no la necesita para autocargar, solo para probarse.
            PermissionServiceProvider::class,
            ActivitylogServiceProvider::class,
        ];
    }

    /**
     * ¿Hay un MariaDB a mano para correr la suite contra el motor de producción?
     *
     * La suite corre en SQLite en memoria y para casi todo alcanza. No alcanza
     * para el guardia de inmutabilidad (`InmutabilidadEnBaseDeDatos`), cuyo SQL
     * es distinto por driver: un trigger que funciona en SQLite y no en MariaDB
     * declara cerrado un agujero que en producción sigue abierto. Por eso la
     * conexión se puede apuntar a un motor real con `tools/pest-mariadb.sh`, o a
     * mano:
     *
     *   KD_MARIADB_HOST=127.0.0.1 KD_MARIADB_PORT=33061 vendor/bin/pest
     *
     * Sin la variable no cambia nada: SQLite, como siempre. El prefijo es
     * `KD_MARIADB_`, no `KRAFTDO_MARIADB_`: es el que trae ya derivado
     * `tools/pest-mariadb.sh` y los tests de Privacidad portados (el mapa de
     * derivación reemplaza `MUNI_MARIADB_` por `KD_MARIADB_`), y las dos
     * variables tienen que coincidir para que el guardia realmente se pruebe.
     */
    public static function hayMariaDb(): bool
    {
        return getenv('KD_MARIADB_HOST') !== false;
    }

    protected function defineEnvironment($app): void
    {
        // El módulo de privacidad cifra el texto libre con la APP_KEY del
        // sistema, y el esqueleto de Testbench no trae ninguna. Una clave por
        // proceso alcanza: la base es en memoria y muere con él.
        $app['config']->set('app.key', 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC')));
        $app['config']->set('app.cipher', 'AES-256-CBC');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', self::hayMariaDb()
            ? [
                'driver' => 'mariadb',
                'host' => getenv('KD_MARIADB_HOST'),
                'port' => getenv('KD_MARIADB_PORT') ?: '3306',
                'database' => getenv('KD_MARIADB_DATABASE') ?: 'prueba',
                'username' => getenv('KD_MARIADB_USERNAME') ?: 'root',
                'password' => getenv('KD_MARIADB_PASSWORD') ?: 'secret',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]
            : [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $fixtures = __DIR__.'/Privacidad/Fixtures/migrations';

        if (is_dir($fixtures)) {
            $this->loadMigrationsFrom($fixtures);
        }
    }
}
