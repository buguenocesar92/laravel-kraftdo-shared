<?php

return [
    /*
    | Resolución de personas por RUT.
    | 'local' = base propia del sistema · 'api' = maestro del ecosistema.
    */
    'personas' => [
        'driver' => env('KRAFTDO_PERSONAS_DRIVER', 'local'),
    ],

    /*
    | Endpoint /salud (Kraftdo\Shared\Health\SaludEcosistema).
    | Por debajo de este porcentaje libre en la partición de storage/, el
    | sistema se reporta degradado: sin disco deja de escribir logs, sesiones
    | y subidas, y lo hace en silencio.
    */
    'salud' => [
        'disco_min_libre_pct' => (float) env('KRAFTDO_SALUD_DISCO_MIN_PCT', 10),
    ],
];
