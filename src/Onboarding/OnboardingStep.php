<?php

namespace Kraftdo\Shared\Onboarding;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Paso individual de un tour: elemento a resaltar, texto que se muestra y se
 * lee en voz alta, y posición del popover.
 *
 * @property int $id
 * @property int $tour_id
 * @property int $order
 * @property string $title
 * @property string $description
 * @property string|null $element_selector
 * @property string|null $pre_action_selector
 * @property string $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OnboardingTour $tour
 */
class OnboardingStep extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'order',
        'title',
        'description',
        'element_selector',
        'pre_action_selector',
        'position',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /** @return BelongsTo<OnboardingTour, $this> */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(OnboardingTour::class, 'tour_id');
    }
}
