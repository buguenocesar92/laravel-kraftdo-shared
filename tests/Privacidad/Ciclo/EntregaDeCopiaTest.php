<?php

use Illuminate\Support\Str;
use Kraftdo\Shared\Privacidad\BaseLicitud;
use Kraftdo\Shared\Privacidad\Ciclo\EntregaDeCopia;
use Kraftdo\Shared\Privacidad\EstadoDeSolicitud;
use Kraftdo\Shared\Privacidad\Modelos\Finalidad;
use Kraftdo\Shared\Privacidad\Modelos\Solicitud;
use Kraftdo\Shared\Privacidad\Solicitante;
use Kraftdo\Shared\Privacidad\TipoDeSolicitud;
use Kraftdo\Shared\Tests\Privacidad\Fixtures\PersonaDePrueba;

beforeEach(function () {
    config(['privacidad.sistema' => 'discapacidad']);

    Finalidad::create([
        'sistema' => 'discapacidad', 'codigo' => 'atencion', 'nombre' => 'Atenciones',
        'base_licitud' => BaseLicitud::FuncionLegal, 'norma_habilitante' => 'Ley 20.422',
    ]);

    $this->titular = PersonaDePrueba::create([
        'nombre' => 'Rocío Paredes',
        'documento' => '11.111.111-1',
        'fecha_nacimiento' => now()->subYears(40)->toDateString(),
    ]);

    $this->crear = function (TipoDeSolicitud $tipo, EstadoDeSolicitud $estado, bool $conTitular = true): Solicitud {
        return Solicitud::create([
            'sistema' => 'discapacidad',
            'tipo' => $tipo,
            'estado' => $estado,
            'titular_type' => $this->titular->getMorphClass(),
            'titular_id' => $conTitular ? $this->titular->getKey() : null,
            'titular_ref' => Str::random(32),
            'detalle' => 'Lo que pide.',
            'solicitante' => Solicitante::Titular,
            'verificacion_identidad' => ['medio' => 'cedula_presencial'],
            'recibida_en' => now(),
            'vence_en' => now()->addDays(30),
        ]);
    };
});

it('el acceso y la portabilidad dan derecho a la copia', function (TipoDeSolicitud $tipo) {
    $solicitud = ($this->crear)($tipo, EstadoDeSolicitud::Recibida);

    expect(EntregaDeCopia::procede($solicitud))->toBeTrue()
        ->and(EntregaDeCopia::porQueNo($solicitud))->toBeNull();
})->with([TipoDeSolicitud::Acceso, TipoDeSolicitud::Portabilidad]);

it('una supresión o una oposición no habilitan a llevarse el expediente', function (TipoDeSolicitud $tipo) {
    $solicitud = ($this->crear)($tipo, EstadoDeSolicitud::Recibida);

    expect(EntregaDeCopia::procede($solicitud))->toBeFalse()
        ->and(EntregaDeCopia::porQueNo($solicitud))->toContain('acceso y la portabilidad');
})->with([TipoDeSolicitud::Supresion, TipoDeSolicitud::Oposicion, TipoDeSolicitud::Rectificacion]);

it('sobre una solicitud rechazada no se entrega la copia que se acaba de negar', function () {
    $solicitud = ($this->crear)(TipoDeSolicitud::Acceso, EstadoDeSolicitud::Rechazada);

    expect(EntregaDeCopia::procede($solicitud))->toBeFalse()
        ->and(EntregaDeCopia::porQueNo($solicitud))->toContain('rechazada');
});

it('un caso anonimizado ya no tiene de quién exportar', function () {
    $solicitud = ($this->crear)(TipoDeSolicitud::Acceso, EstadoDeSolicitud::Recibida, conTitular: false);

    expect(EntregaDeCopia::procede($solicitud->fresh()))->toBeFalse()
        ->and(EntregaDeCopia::porQueNo($solicitud->fresh()))->toContain('titular');
});

it('acogida y acogida parcial siguen permitiendo entregar lo pedido', function (EstadoDeSolicitud $estado) {
    $solicitud = ($this->crear)(TipoDeSolicitud::Acceso, $estado);

    expect(EntregaDeCopia::procede($solicitud))->toBeTrue();
})->with([EstadoDeSolicitud::Acogida, EstadoDeSolicitud::AcogidaParcial]);
