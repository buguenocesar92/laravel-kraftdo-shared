<?php

namespace Kraftdo\Shared\Filament\Resources\OnboardingTourResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kraftdo\Shared\Filament\Resources\OnboardingTourResource;

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
