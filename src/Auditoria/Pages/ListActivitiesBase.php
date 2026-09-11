<?php

namespace Kraftdo\Shared\Auditoria\Pages;

use Filament\Resources\Pages\ListRecords;

/**
 * Página base del listado de auditoría del panel (`spatie/laravel-activitylog`
 * mostrado como Resource de Filament).
 *
 * Es abstracta y no fija `$resource`: cada sistema apunta a SU
 * `ActivityResource`, con sus propias columnas de tabla, que no es portable.
 * Esta clase solo entrega el sitio único al que un comportamiento común
 * (filtros, orden, exportación) podría llegar mañana sin editar N copias.
 *
 * Adopción, por sistema:
 *
 * ```php
 * namespace App\Filament\Resources\ActivityResource\Pages;
 *
 * use App\Filament\Resources\ActivityResource;
 * use Kraftdo\Shared\Auditoria\Pages\ListActivitiesBase;
 *
 * class ListActivities extends ListActivitiesBase
 * {
 *     protected static string $resource = ActivityResource::class;
 * }
 * ```
 */
abstract class ListActivitiesBase extends ListRecords {}
