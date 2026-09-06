<?php

namespace Kraftdo\Shared\Onboarding;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;

/**
 * Registro de que un usuario completó (u omitió) un tour.
 *
 * @property int $id
 * @property int $user_id
 * @property int $tour_id
 * @property bool $completed
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OnboardingTour $tour
 */
class OnboardingProgress extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $table = 'onboarding_progress';

    protected $fillable = [
        'user_id',
        'tour_id',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Usuario dueño de este progreso.
     *
     * Resuelve el modelo `User` desde la configuración de autenticación de la
     * app consumidora en vez de asumir uno propio: antes esto era
     * `belongsTo(User::class)` sin importar ninguna clase `User`, así que
     * apuntaba a la clase inexistente `Kraftdo\Shared\Onboarding\User` y
     * reventaba con «Class not found» apenas alguien abría el progreso de un
     * tour en el panel (`ProgressRelationManager` pinta `user.name` y
     * `user.email`). El paquete no puede asumir el FQCN del modelo de cada
     * uno de los cuatro sistemas, así que lo lee de `auth.providers.users.model`,
     * como hace el propio framework.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        /** @var class-string<Model> $modeloUsuario */
        $modeloUsuario = config('auth.providers.users.model', User::class);

        return $this->belongsTo($modeloUsuario);
    }

    /** @return BelongsTo<OnboardingTour, $this> */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(OnboardingTour::class, 'tour_id');
    }
}
