<?php

namespace Kraftdo\Shared\Filament\Resources\OnboardingTourResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kraftdo\Shared\Filament\Resources\OnboardingTourResource;

class EditOnboardingTour extends EditRecord
{
    protected static string $resource = OnboardingTourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
