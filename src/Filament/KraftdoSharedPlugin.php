<?php

namespace Kraftdo\Shared\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kraftdo\Shared\Filament\Resources\ActivityResource;
use Kraftdo\Shared\Filament\Resources\OnboardingTourResource;

/**
 * Registra en el panel los recursos Filament comunes a los sistemas del
 * ecosistema: bitácora de actividad y tours de onboarding.
 *
 * Se activa en el panel del proyecto:
 *
 *     ->plugin(\Kraftdo\Shared\Filament\KraftdoSharedPlugin::make())
 *
 * NO incluye `UserResource`: aunque hoy es idéntico en los 4 proyectos, depende
 * del modelo `User` de cada app, que sí difiere (el de kraftdo-nfc tiene las
 * relaciones de productos y órdenes). Ese recurso se queda en cada proyecto.
 */
class KraftdoSharedPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'kraftdo-shared';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ActivityResource::class,
            OnboardingTourResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
