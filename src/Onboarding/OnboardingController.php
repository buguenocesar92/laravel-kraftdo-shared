<?php

namespace Kraftdo\Shared\Onboarding;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Endpoints del módulo de onboarding guiado.
 *
 * No extiende el Controller base de la app: es un controlador del paquete y
 * cada proyecto tiene el suyo. Solo usa `$request->validate()`, que vive en el
 * Request, no en el Controller.
 */
class OnboardingController
{
    /**
     * Devuelve el tour aplicable al usuario autenticado para un sistema dado,
     * junto con sus pasos y si ya lo completó. El administrador puede pedir un
     * tour arbitrario por `tour_id` (modo previsualización desde el panel).
     */
    public function tour(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 401);

        $system = $request->query('system');
        $surface = $request->query('surface'); // 'app' | 'panel'
        $tourId = $request->query('tour_id');
        $tourId = is_array($tourId) ? null : $tourId;

        $tour = null;

        // Previsualización: solo administradores pueden previsualizar tours directamente por ID.
        if ($tourId && $user->can('view_any_onboarding::tour')) {
            $tour = OnboardingTour::with('steps')->find($tourId);
        }

        // Resolución normal: tour activo del sistema y la superficie actual.
        if (! $tour && $system) {
            $roles = $this->rolesDelUsuario($user);

            $base = fn () => OnboardingTour::query()
                ->with('steps')
                ->where('system', $system)
                ->where('active', true)
                ->when($surface, fn ($q) => $q->where(
                    fn ($w) => $w->where('surface', $surface)->orWhereNull('surface')
                ));

            // 1º: tour cuyo rol posee el usuario.
            $tour = $base()->whereIn('role', $roles)->first();

            // 2º: fallback a cualquier tour general activo para esta superficie
            if (! $tour && in_array($surface, ['app', 'hub'], true)) {
                $tour = $base()->first();
            }
        }

        if (! $tour) {
            return response()->json(['tour' => null]);
        }

        $completed = OnboardingProgress::query()
            ->where('user_id', $user->id)
            ->where('tour_id', $tour->id)
            ->where('completed', true)
            ->exists();

        return response()->json([
            'tour' => [
                'id' => $tour->id,
                'name' => $tour->name,
                'system' => $tour->system,
                'role' => $tour->role,
            ],
            'completed' => $completed,
            // Título y descripción terminan en `innerHTML` (driver.js no tiene
            // opción para desactivarlo): se escapan acá, en el borde del API, para
            // que un paso guardado desde el panel con `<script>` no se ejecute en
            // el navegador de todos los que vean el tour.
            'steps' => $tour->steps->map(fn (OnboardingStep $s): array => [
                'order' => $s->order,
                'title' => e($s->title),
                'description' => e($s->description),
                'element_selector' => $s->element_selector,
                'pre_action_selector' => $s->pre_action_selector,
                'position' => $s->position,
            ])->values(),
        ]);
    }

    /**
     * Marca el tour como completado para el usuario autenticado.
     */
    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tour_id' => ['required', 'integer', 'exists:onboarding_tours,id'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 401);

        OnboardingProgress::updateOrCreate(
            ['user_id' => $user->id, 'tour_id' => $data['tour_id']],
            ['completed' => true, 'completed_at' => now()],
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Resetea el progreso para volver a ver el tour.
     */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tour_id' => ['required', 'integer', 'exists:onboarding_tours,id'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 401);

        $targetUserId = $user->id;
        // Permitir a usuarios privilegiados resetear tours de otros
        if (isset($data['user_id']) && $user->can('view_any_onboarding::tour')) {
            $targetUserId = $data['user_id'];
        }

        OnboardingProgress::query()
            ->where('user_id', $targetUserId)
            ->where('tour_id', $data['tour_id'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Roles del usuario autenticado, si el modelo los expone.
     *
     * Normalmente vía `Spatie\Permission\Traits\HasRoles::getRoleNames()`, pero
     * `spatie/laravel-permission` no es una dependencia declarada de este
     * paquete: los cuatro sistemas la traen hoy, pero un consumidor nuevo
     * podría no tenerla todavía. Antes esto llamaba `$user->getRoleNames()`
     * directo y reventaba con un error fatal de método indefinido apenas
     * alguien sin ese trait entraba; ahora degrada a «sin roles» y sigue con
     * el fallback de tour general para la superficie.
     *
     * @return Collection<int, string>
     */
    private function rolesDelUsuario(Authenticatable $user): Collection
    {
        if (! method_exists($user, 'getRoleNames')) {
            return collect();
        }

        // Invocación indirecta a propósito: el método no existe en el
        // contrato `Authenticatable` (lo aporta un trait opcional de la app
        // consumidora), así que no se puede tipar de forma estática.
        $obtenerRoles = [$user, 'getRoleNames'];

        /** @var Collection<int, string> $roles */
        $roles = $obtenerRoles();

        return $roles;
    }
}
