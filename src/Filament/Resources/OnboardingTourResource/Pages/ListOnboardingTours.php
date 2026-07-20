<?php

namespace Kraftdo\Shared\Filament\Resources\OnboardingTourResource\Pages;

use Kraftdo\Shared\Filament\Resources\OnboardingTourResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOnboardingTours extends ListRecords
{
    protected static string $resource = OnboardingTourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
