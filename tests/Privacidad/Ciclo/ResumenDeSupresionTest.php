<?php

use Kraftdo\Shared\Privacidad\Ciclo\ResumenDeSupresion;
use Kraftdo\Shared\Privacidad\EvaluacionDeSupresion;
use Kraftdo\Shared\Privacidad\ResultadoDePropagacion;
use Kraftdo\Shared\Privacidad\ResultadoDeSupresion;
use Kraftdo\Shared\Privacidad\ResultadoDesvinculacion;

function evaluacionQueProcede(): EvaluacionDeSupresion
{
    return new EvaluacionDeSupresion(impiden: [], cesan: []);
}

it('una supresión total con el maestro conforme se cuenta como cierre', function () {
    $resumen = ResumenDeSupresion::de(new ResultadoDeSupresion(
        total: true,
        evaluacion: evaluacionQueProcede(),
        barrido: new ResultadoDesvinculacion(filas: 3, archivosSuprimidos: 2, archivosNoEncontrados: 0),
        propagacion: ResultadoDePropagacion::aceptada(),
    ));

    expect($resumen->total)->toBeTrue()
        ->and($resumen->salioDelEcosistema)->toBeTrue()
        ->and($resumen->esAdvertencia())->toBeFalse()
        ->and($resumen->cuerpo())->toContain('2 documento(s)');
});

it('una total sin propagación aceptada es una advertencia, no un listo', function () {
    $resumen = ResumenDeSupresion::de(new ResultadoDeSupresion(
        total: true,
        evaluacion: evaluacionQueProcede(),
        barrido: new ResultadoDesvinculacion(filas: 1, archivosSuprimidos: 1, archivosNoEncontrados: 0),
        propagacion: null,
    ));

    expect($resumen->salioDelEcosistema)->toBeFalse()
        ->and($resumen->esAdvertencia())->toBeTrue()
        ->and($resumen->titulo())->toContain('sigue')
        ->and($resumen->cuerpo())->toContain('todavía no confirmó la baja');
});

it('una ruta sin archivo se avisa: el documento puede estar en otro disco', function () {
    $resumen = ResumenDeSupresion::de(new ResultadoDeSupresion(
        total: true,
        evaluacion: evaluacionQueProcede(),
        barrido: new ResultadoDesvinculacion(filas: 1, archivosSuprimidos: 0, archivosNoEncontrados: 3),
        propagacion: ResultadoDePropagacion::aceptada(),
    ));

    expect($resumen->archivosNoEncontrados)->toBe(3)
        ->and($resumen->cuerpo())->toContain('otro disco');
});

it('una acogida parcial no se anuncia con el mismo listo que una total', function () {
    $resumen = ResumenDeSupresion::de(new ResultadoDeSupresion(
        total: false,
        evaluacion: evaluacionQueProcede(),
    ));

    expect($resumen->total)->toBeFalse()
        ->and($resumen->cuerpo())->not->toContain('quedó anonimizado')
        ->and($resumen->cuerpo())->toContain('parte');
});
