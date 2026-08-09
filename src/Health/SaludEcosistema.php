<?php

namespace Kraftdo\Shared\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Chequeo de salud compartido del ecosistema KraftDo.
 *
 * Existe porque `/up` no sirve para vigilar: responde 200 con solo bootear Laravel,
 * así que el `hub:vigilar` daba por "arriba" un sistema con la base de datos caída.
 * Esto reporta las dependencias que TODOS los sistemas comparten —base de datos,
 * cola y disco— en un formato normalizado, para que el Hub lea estado REAL.
 *
 * Cada sistema suma los suyos (OCR, Reverb, catálogo…) con `conExtra()`; el núcleo
 * se mantiene idéntico en los cuatro.
 *
 *     Route::get('/salud', function () {
 *         $reporte = (new SaludEcosistema)->conExtra('ocr', $chequeo)->reporte();
 *
 *         return response()->json($reporte, $reporte['estado'] === 'ok' ? 200 : 503);
 *     });
 *
 * @phpstan-type Chequeo array{estado: string, detalle: string|null, ms: int}
 */
class SaludEcosistema
{
    /** @var array<string, callable(): bool> */
    private array $extra = [];

    /**
     * Añade un chequeo propio del sistema. El cierre devuelve true (arriba) o false.
     */
    public function conExtra(string $nombre, callable $chequeo): static
    {
        $this->extra[$nombre] = $chequeo;

        return $this;
    }

    /**
     * Ejecuta todos los chequeos y devuelve el reporte normalizado.
     *
     * @return array{sistema: string, estado: string, ts: string, checks: array<string, Chequeo>}
     */
    public function reporte(): array
    {
        $checks = [
            'base_datos' => $this->medir(fn (): bool => $this->baseDatosOk()),
            'cola' => $this->medir(fn (): bool => $this->colaOk()),
            'disco' => $this->chequearDisco(),
        ];

        foreach ($this->extra as $nombre => $chequeo) {
            $checks[$nombre] = $this->medir($chequeo);
        }

        $global = collect($checks)->every(fn (array $c): bool => $c['estado'] === 'ok')
            ? 'ok'
            : 'degradado';

        return [
            'sistema' => (string) config('app.name', 'sistema'),
            'estado' => $global,
            'ts' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
     * Corre un chequeo midiendo su latencia y capturando errores.
     *
     * @param  callable(): bool  $chequeo
     * @return Chequeo
     */
    private function medir(callable $chequeo): array
    {
        $t0 = microtime(true);

        try {
            $ok = (bool) $chequeo();

            return [
                'estado' => $ok ? 'ok' : 'caido',
                'detalle' => null,
                'ms' => $this->transcurrido($t0),
            ];
        } catch (Throwable $e) {
            return [
                'estado' => 'caido',
                // Solo la clase del error: el mensaje puede traer la cadena de
                // conexión con credenciales, y este endpoint es público.
                'detalle' => class_basename($e),
                'ms' => $this->transcurrido($t0),
            ];
        }
    }

    private function transcurrido(float $t0): int
    {
        return (int) round((microtime(true) - $t0) * 1000);
    }

    private function baseDatosOk(): bool
    {
        DB::connection()->select('select 1');

        return true;
    }

    /** Cola sana = la profundidad de la cola por defecto no está desbocada. */
    private function colaOk(): bool
    {
        try {
            return (int) Redis::connection()->llen('queues:default') < 500;
        } catch (Throwable) {
            // Sin Redis (cola sync en dev/tests): se considera sano.
            return true;
        }
    }

    /**
     * Disco: un sistema sin espacio deja de escribir logs, sesiones y subidas, y
     * lo hace en silencio. Se reporta el porcentaje libre para no tener que
     * entrar al servidor a averiguar cuánto queda.
     *
     * @return Chequeo
     */
    private function chequearDisco(): array
    {
        $libre = null;
        $minimo = (float) config('kraftdo-shared.salud.disco_min_libre_pct', 10);

        $chequeo = $this->medir(function () use (&$libre, $minimo): bool {
            $libre = $this->porcentajeLibreDisco();

            return $libre >= $minimo;
        });

        if ($chequeo['estado'] !== 'ok' && $libre !== null) {
            $chequeo['detalle'] = round($libre).'% libre';
        }

        return $chequeo;
    }

    /** Porcentaje libre de la partición donde vive `storage/`. */
    protected function porcentajeLibreDisco(): float
    {
        $ruta = storage_path();
        $libre = disk_free_space($ruta);
        $total = disk_total_space($ruta);

        if ($libre === false || $total === false || $total <= 0) {
            // No se pudo medir: no se inventa una avería.
            return 100.0;
        }

        return ($libre / $total) * 100;
    }
}
