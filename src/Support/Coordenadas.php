<?php

namespace Kraftdo\Shared\Support;

/**
 * Parser de coordenadas pegadas desde Google Maps.
 */
final class Coordenadas
{
    /** @return array{lat: float, lng: float}|null */
    public static function parse(?string $entrada): ?array
    {
        if ($entrada === null) {
            return null;
        }

        $texto = trim($entrada);
        if ($texto === '') {
            return null;
        }

        // Normaliza el signo "menos" tipográfico (−, U+2212) al ASCII '-'.
        $texto = str_replace("\u{2212}", '-', $texto);

        $patrones = [
            '/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',           // URL con /@lat,lng
            '/[?&](?:q|query|ll|destination)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/', // ?q=lat,lng
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',          // Parámetros de lugar !3dLAT!4dLNG
            '/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', // Texto simple "lat, lng"
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $texto, $m)) {
                return self::validar((float) $m[1], (float) $m[2]);
            }
        }

        return null;
    }

    /** @return array{lat: float, lng: float}|null */
    private static function validar(float $lat, float $lng): ?array
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }
}
