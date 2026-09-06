<?php

namespace Kraftdo\Shared\Onboarding;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un recorrido guiado para un (sistema, rol). Agrupa pasos ordenados.
 *
 * @property int $id
 * @property string $name
 * @property string $system
 * @property string $role
 * @property string|null $surface
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, OnboardingStep> $steps
 * @property-read Collection<int, OnboardingProgress> $progress
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
