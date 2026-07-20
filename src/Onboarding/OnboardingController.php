<?php

namespace Kraftdo\Shared\Onboarding;

use Kraftdo\Shared\Onboarding\OnboardingProgress;
use Kraftdo\Shared\Onboarding\OnboardingStep;
use Kraftdo\Shared\Onboarding\OnboardingTour;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints del módulo de onboarding guiado.
 */
class OnboardingController extends Controller
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
            $roles = $user->getRoleNames();

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
            'steps' => $tour->steps->map(fn (OnboardingStep $s): array => [
                'order' => $s->order,
                'title' => $s->title,
                'description' => $s->description,
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
}
