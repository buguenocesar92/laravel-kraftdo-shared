<?php

use Illuminate\Support\Str;
use Kraftdo\Shared\Privacidad\BaseLicitud;
use Kraftdo\Shared\Privacidad\Ciclo\EstadoDePlazo;
use Kraftdo\Shared\Privacidad\Ciclo\PlazoLegal;
use Kraftdo\Shared\Privacidad\EstadoDeSolicitud;
use Kraftdo\Shared\Privacidad\Modelos\Finalidad;
use Kraftdo\Shared\Privacidad\Modelos\Solicitud;
use Kraftdo\Shared\Privacidad\Solicitante;
use Kraftdo\Shared\Privacidad\TipoDeSolicitud;
use Kraftdo\Shared\Tests\Privacidad\Fixtures\PersonaDePrueba;

beforeEach(function () {
    config(['privacidad.sistema' => 'discapacidad']);

    $this->titular = PersonaDePrueba::create([
        'nombre' => 'Rocío Paredes',
        'documento' => '11.111.111-1',
        'fecha_nacimiento' => now()->subYears(40)->toDateString(),
    ]);

    Finalidad::create([
        'sistema' => 'discapacidad', 'codigo' => 'atencion', 'nombre' => 'Atenciones',
        'base_licitud' => BaseLicitud::FuncionLegal, 'norma_habilitante' => 'Ley 20.422',
    ]);

    $this->solicitud = function (EstadoDeSolicitud $estado, int $diasParaVencer): Solicitud {
        return Solicitud::create([
            'sistema' => 'discapacidad',
            'tipo' => TipoDeSolicitud::Acceso,
            'estado' => $estado,
            'titular_type' => $this->titular->getMorphClass(),
            'titular_id' => $this->titular->getKey(),
            'titular_ref' => Str::random(32),
            'detalle' => 'Pide copia de su ficha.',
            'solicitante' => Solicitante::Titular,
            'verificacion_identidad' => ['medio' => 'cedula_presencial'],
            'recibida_en' => now(),
            'vence_en' => now()->addDays($diasParaVencer),
        ]);
    };
});

it('una solicitud resuelta ya no tiene semáforo de plazo', function () {
    $solicitud = ($this->solicitud)(EstadoDeSolicitud::Acogida, -30);

    expect(PlazoLegal::de($solicitud))->toBe(EstadoDePlazo::Resuelta);
});

it('una solicitud pendiente con el plazo cumplido está vencida', function () {
    $solicitud = ($this->solicitud)(EstadoDeSolicitud::EnTramite, -1);

    expect(PlazoLegal::de($solicitud))->toBe(EstadoDePlazo::Vencida);
});

it('el umbral de por vencer es el mismo que usa el scope del modelo', function () {
    $solicitud = ($this->solicitud)(EstadoDeSolicitud::Recibida, PlazoLegal::DIAS_POR_VENCER);

    expect(PlazoLegal::de($solicitud))->toBe(EstadoDePlazo::PorVencer)
        ->and(Solicitud::query()->porVencer()->whereKey($solicitud->getKey())->exists())->toBeTrue();
});

it('con holgura de sobra está en plazo', function () {
    $solicitud = ($this->solicitud)(EstadoDeSolicitud::Recibida, PlazoLegal::DIAS_POR_VENCER + 10);

    expect(PlazoLegal::de($solicitud))->toBe(EstadoDePlazo::EnPlazo);
});

it('cada estado de plazo se nombra en español una sola vez', function () {
    expect(EstadoDePlazo::Resuelta->etiqueta())->toBe('Resuelta')
        ->and(EstadoDePlazo::Vencida->etiqueta())->toBe('Vencida')
        ->and(EstadoDePlazo::PorVencer->etiqueta())->toBe('Por vencer')
        ->and(EstadoDePlazo::EnPlazo->etiqueta())->toBe('En plazo');
});
