<?php

namespace Kraftdo\Shared\Eco;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cliente para hablar con otro sistema del ecosistema KraftDo.
 *
 * Estandariza el patrón que ya usaban por separado el push de eventos al Hub, el
 * catálogo del Sitio y la creación de cuadros del CRM:
 *
 * - Autenticación con token compartido (bearer).
 * - Timeout corto y reintentos acotados.
 * - **Degradación con gracia**: si el sistema destino no está configurado o no
 *   responde, devuelve null en vez de lanzar. Ningún sistema puede tumbar a otro.
 *
 * Regla del ecosistema: una integración NUNCA bloquea el request del usuario.
 * Para efectos secundarios (avisos, feeds) usar además una cola.
 */
class ClienteEcosistema
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
        private readonly int $timeout = 6,
        private readonly int $reintentos = 2,
    ) {}

    /** Construye el cliente desde `config('ecosistema.*')`; null si falta configuración. */
    public static function desdeConfig(string $claveUrl, string $claveToken, int $timeout = 6): ?self
    {
        $url = rtrim((string) config($claveUrl), '/');
        $token = (string) config($claveToken);

        return ($url === '' || $token === '') ? null : new self($url, $token, $timeout);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>|null
     */
    public function post(string $ruta, array $datos = []): ?array
    {
        return $this->enviar('post', $ruta, $datos);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    public function get(string $ruta, array $query = []): ?array
    {
        return $this->enviar('get', $ruta, $query);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>|null
     */
    private function enviar(string $metodo, string $ruta, array $datos): ?array
    {
        try {
            $res = $this->peticion()->{$metodo}($this->baseUrl.'/'.ltrim($ruta, '/'), $datos);

            if ($res->successful()) {
                /** @var array<string, mixed> $cuerpo */
                $cuerpo = $res->json() ?? [];

                return $cuerpo;
            }

            Log::warning('Ecosistema KraftDo: respuesta no exitosa.', [
                'ruta' => $ruta,
                'status' => $res->status(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Ecosistema KraftDo: no se pudo contactar el sistema. '.$e->getMessage(), [
                'ruta' => $ruta,
            ]);
        }

        return null;
    }

    private function peticion(): PendingRequest
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->reintentos, 200);
    }
}
