<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Kraftdo\Shared\Health\SaludEcosistema;

/**
 * El vigilante del Hub (`hub:vigilar`) daba por "arriba" cualquier respuesta 2xx, y
 * apuntaba a `/up`, que responde 200 con solo bootear Laravel: un sistema con la base
 * de datos caída se reportaba sano. Estos tests fijan el contrato del reporte que
 * reemplaza a ese 200 vacío.
 */
it('reporta el contrato completo con los tres chequeos del núcleo', function () {
    $reporte = (new SaludEcosistema)->reporte();

    expect($reporte)->toHaveKeys(['sistema', 'estado', 'ts', 'checks'])
        ->and($reporte['checks'])->toHaveKeys(['base_datos', 'cola', 'disco'])
        ->and($reporte['checks']['base_datos'])->toHaveKeys(['estado', 'detalle', 'ms'])
        ->and($reporte['ts'])->toMatch('/^\d{4}-\d{2}-\d{2}T/');
});

it('está ok cuando todos los chequeos pasan', function () {
    $reporte = (new SaludEcosistema)->reporte();

    expect($reporte['estado'])->toBe('ok')
        ->and($reporte['checks']['base_datos']['estado'])->toBe('ok');
});

it('degrada el estado global cuando un chequeo propio falla', function () {
    $reporte = (new SaludEcosistema)
        ->conExtra('ocr', fn (): bool => false)
        ->reporte();

    expect($reporte['estado'])->toBe('degradado')
        ->and($reporte['checks']['ocr']['estado'])->toBe('caido');
});

it('suma los chequeos propios del sistema sin tocar el núcleo', function () {
    $reporte = (new SaludEcosistema)
        ->conExtra('reverb', fn (): bool => true)
        ->reporte();

    expect($reporte['checks'])->toHaveKeys(['base_datos', 'cola', 'disco', 'reverb'])
        ->and($reporte['estado'])->toBe('ok');
});

it('no filtra el mensaje del error, solo la clase', function () {
    $reporte = (new SaludEcosistema)
        ->conExtra('secreto', function (): bool {
            throw new RuntimeException('mysql://usuario:contrasena@host/base');
        })
        ->reporte();

    expect($reporte['checks']['secreto']['estado'])->toBe('caido')
        ->and($reporte['checks']['secreto']['detalle'])->toBe('RuntimeException')
        ->and(json_encode($reporte))->not->toContain('contrasena');
});

it('reporta la base de datos caída en vez de lanzar', function () {
    DB::shouldReceive('connection->select')->andThrow(new PDOException('sin conexión'));

    $reporte = (new SaludEcosistema)->reporte();

    expect($reporte['checks']['base_datos']['estado'])->toBe('caido')
        ->and($reporte['estado'])->toBe('degradado');
});

it('degrada cuando queda poco espacio en disco', function () {
    $salud = new class extends SaludEcosistema
    {
        protected function porcentajeLibreDisco(): float
        {
            return 3.0;
        }
    };

    $reporte = $salud->reporte();

    expect($reporte['checks']['disco']['estado'])->toBe('caido')
        ->and($reporte['checks']['disco']['detalle'])->toBe('3% libre')
        ->and($reporte['estado'])->toBe('degradado');
});

it('respeta el umbral de disco configurado', function () {
    config()->set('kraftdo-shared.salud.disco_min_libre_pct', 25);

    $salud = new class extends SaludEcosistema
    {
        protected function porcentajeLibreDisco(): float
        {
            return 20.0;
        }
    };

    expect($salud->reporte()['checks']['disco']['estado'])->toBe('caido');
});

it('considera sana la cola cuando no hay Redis configurado', function () {
    // En tests la cola es sync y no hay Redis: eso no es una avería del sistema.
    expect((new SaludEcosistema)->reporte()['checks']['cola']['estado'])->toBe('ok');
});

it('mide la latencia de cada chequeo', function () {
    $reporte = (new SaludEcosistema)->reporte();

    expect($reporte['checks']['base_datos']['ms'])->toBeInt()->toBeGreaterThanOrEqual(0);
});

/**
 * Los microservicios del ecosistema (ocr, docintel, vision, chatbot-guard) exponen
 * todos `GET /health` devolviendo `{"ok": true}`. El chequeo es el mismo en los
 * cuatro sistemas: vive acá para no repetirlo en cada routes/web.php.
 */
it('da por sano un microservicio que no está activo', function () {
    Http::fake();

    expect(SaludEcosistema::microOk(false, 'http://ocr:8000/ocr'))->toBeTrue();

    Http::assertNothingSent();
});

it('da por sano un microservicio activo sin url configurada', function () {
    expect(SaludEcosistema::microOk(true, ''))->toBeTrue();
});

it('consulta /health y acepta ok true', function () {
    Http::fake(['*/health' => Http::response(['ok' => true])]);

    expect(SaludEcosistema::microOk(true, 'http://ocr:8000/ocr'))->toBeTrue();
});

it('rechaza el microservicio que responde error', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect(SaludEcosistema::microOk(true, 'http://ocr:8000/ocr'))->toBeFalse();
});

it('rechaza el microservicio que responde ok false', function () {
    Http::fake(['*/health' => Http::response(['ok' => false])]);

    expect(SaludEcosistema::microOk(true, 'http://ocr:8000/ocr'))->toBeFalse();
});

it('deriva /health de la url del microservicio sin importar su ruta', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    SaludEcosistema::microOk(true, 'http://ocr:8000/ocr');

    Http::assertSent(fn ($peticion): bool => $peticion->url() === 'http://ocr:8000/health');
});
