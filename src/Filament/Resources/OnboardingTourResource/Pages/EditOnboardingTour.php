<?php

namespace Kraftdo\Shared\Filament\Resources\OnboardingTourResource\Pages;

use Kraftdo\Shared\Filament\Resources\OnboardingTourResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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
