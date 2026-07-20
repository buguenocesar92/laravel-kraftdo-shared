<?php

namespace Kraftdo\Shared\Onboarding;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un recorrido guiado para un (sistema, rol). Agrupa pasos ordenados.
 */
class OnboardingTour extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $fillable = [
        'name',
        'system',
        'role',
        'surface',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Pasos del tour, ya ordenados.
     *
     * @return HasMany<OnboardingStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(OnboardingStep::class, 'tour_id')->orderBy('order');
    }

    /**
     * Progreso de los usuarios sobre este tour.
     *
     * @return HasMany<OnboardingProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(OnboardingProgress::class, 'tour_id');
    }
}
