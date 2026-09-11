<?php

use Illuminate\Support\Str;
use Kraftdo\Shared\Privacidad\BaseLicitud;
use Kraftdo\Shared\Privacidad\Ciclo\PreviaDeSupresion;
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

    $this->solicitud = Solicitud::create([
        'sistema' => 'discapacidad',
        'tipo' => TipoDeSolicitud::Supresion,
        'estado' => EstadoDeSolicitud::EnTramite,
        'titular_type' => $this->titular->getMorphClass(),
        'titular_id' => $this->titular->getKey(),
        'titular_ref' => Str::random(32),
        'detalle' => 'Pide que borren sus datos.',
        'solicitante' => Solicitante::Titular,
        'verificacion_identidad' => ['medio' => 'cedula_presencial'],
        'user_registro_id' => 7,
        'recibida_en' => now(),
        'vence_en' => now()->addDays(30),
    ]);
});

it('muestra hasta dónde llega el derecho antes de tocar nada', function () {
    $previa = PreviaDeSupresion::de($this->solicitud);

    expect($previa)->toBeString()->not->toBeEmpty();
});

it('no inventa una previa para un caso anonimizado', function () {
    $this->solicitud->forceFill(['titular_id' => null])->save();

    expect(PreviaDeSupresion::de($this->solicitud->fresh()))->toBeNull();
});

it('la previa NO escribe nada: la solicitud queda en trámite', function () {
    PreviaDeSupresion::de($this->solicitud);

    expect($this->solicitud->fresh()->estado)->toBe(EstadoDeSolicitud::EnTramite)
        ->and(PersonaDePrueba::find($this->titular->getKey()))->not->toBeNull();
});

it('junta el aviso de separación de funciones con la previa', function () {
    $texto = PreviaDeSupresion::antesDeSuprimir($this->solicitud, 7);

    expect($texto)->toContain('Esta solicitud la recibiste tú');
});

it('sin coincidencia de operador, entrega solo la previa', function () {
    $texto = PreviaDeSupresion::antesDeSuprimir($this->solicitud, 9);

    expect($texto)->not->toContain('Esta solicitud la recibiste tú')
        ->and($texto)->not->toBeEmpty();
});
