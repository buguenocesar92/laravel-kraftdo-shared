<?php

use Kraftdo\Shared\Rut\RutHelper;

describe('clean', function () {
    it('deja solo dígitos y el verificador, en mayúscula', function () {
        expect(RutHelper::clean('14.523.681-9'))->toBe('145236819');
        expect(RutHelper::clean('12.345.678-k'))->toBe('12345678K');
        expect(RutHelper::clean('  7.654.321-0  '))->toBe('76543210');
    });

    it('devuelve cadena vacía si no hay nada aprovechable', function () {
        expect(RutHelper::clean('sin rut'))->toBe('');
    });
});

describe('normalize', function () {
    it('lleva cualquier formato a cuerpo-guión-dv', function () {
        expect(RutHelper::normalize('14.523.681-9'))->toBe('14523681-9');
        expect(RutHelper::normalize('145236819'))->toBe('14523681-9');
    });

    it('no rompe con entradas demasiado cortas', function () {
        expect(RutHelper::normalize('9'))->toBe('9');
        expect(RutHelper::normalize(''))->toBe('');
    });
});

describe('format', function () {
    it('agrega puntos de miles y el guión', function () {
        expect(RutHelper::format('145236819'))->toBe('14.523.681-9');
        expect(RutHelper::format('12345678K'))->toBe('12.345.678-K');
    });
});

describe('calcularDv', function () {
    it('calcula el dígito verificador con módulo 11', function (string $cuerpo, string $dv) {
        expect(RutHelper::calcularDv($cuerpo))->toBe($dv);
    })->with([
        // cuerpo => dv (verificados contra el algoritmo oficial)
        ['14523681', '9'],
        ['7654321', '6'],
        ['11111111', '1'],
        ['22222222', '2'],
    ]);

    it('devuelve K cuando la diferencia es 10', function () {
        // 12345670 tiene DV K: el caso límite del módulo 11.
        expect(RutHelper::calcularDv('12345670'))->toBe('K');
    });
});

describe('validate', function () {
    it('acepta RUTs con dígito verificador correcto', function (string $rut) {
        expect(RutHelper::validate($rut))->toBeTrue();
    })->with([
        '14.523.681-9',
        '145236819',
        '7.654.321-6',
    ]);

    it('rechaza un dígito verificador incorrecto', function () {
        expect(RutHelper::validate('14.523.681-8'))->toBeFalse();
    });

    it('rechaza cuerpos con longitud fuera de rango', function (string $rut) {
        expect(RutHelper::validate($rut))->toBeFalse();
    })->with([
        '1-9',          // demasiado corto
        '1234567890-1', // demasiado largo
        '',
    ]);

    it('rechaza si el cuerpo no es numérico', function () {
        expect(RutHelper::validate('1K23456-7'))->toBeFalse();
    });
});
