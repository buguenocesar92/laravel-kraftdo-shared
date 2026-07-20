<?php

return [
    /*
    | Resolución de personas por RUT.
    | 'local' = base propia del sistema · 'api' = maestro del ecosistema.
    */
    'personas' => [
        'driver' => env('KRAFTDO_PERSONAS_DRIVER', 'local'),
    ],
];
