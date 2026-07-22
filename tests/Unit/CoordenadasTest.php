<?php

use Kraftdo\Shared\Support\Coordenadas;

describe('parse', function () {
    it('extrae lat/lng de una URL de Google Maps con /@', function () {
        expect(Coordenadas::parse('https://maps.google.com/@-34.5880,-70.9080,15z'))
            ->toBe(['lat' => -34.5880, 'lng' => -70.9080]);
    });

    it('extrae de un parámetro q=lat,lng', function () {
        expect(Coordenadas::parse('https://maps.google.com/?q=-33.4489,-70.6693'))
            ->toBe(['lat' => -33.4489, 'lng' => -70.6693]);
    });

    it('extrae de los parámetros de lugar !3d!4d', function () {
        expect(Coordenadas::parse('.../data=!3d-34.1701!4d-70.7406'))
            ->toBe(['lat' => -34.1701, 'lng' => -70.7406]);
    });

    it('acepta texto simple "lat, lng"', function () {
        expect(Coordenadas::parse('-34.5880, -70.9080'))
            ->toBe(['lat' => -34.5880, 'lng' => -70.9080]);
    });

    it('normaliza el signo menos tipográfico (U+2212)', function () {
        expect(Coordenadas::parse("\u{2212}34.5880, \u{2212}70.9080"))
            ->toBe(['lat' => -34.5880, 'lng' => -70.9080]);
    });

    it('devuelve null para entradas vacías o nulas', function (?string $entrada) {
        expect(Coordenadas::parse($entrada))->toBeNull();
    })->with([null, '', '   ', 'no hay coordenadas aquí']);

    it('rechaza coordenadas fuera de rango', function (string $entrada) {
        expect(Coordenadas::parse($entrada))->toBeNull();
    })->with([
        '91.0, 0.0',    // lat > 90
        '0.0, 181.0',   // lng > 180
        '-91.0, 0.0',   // lat < -90
    ]);
});
