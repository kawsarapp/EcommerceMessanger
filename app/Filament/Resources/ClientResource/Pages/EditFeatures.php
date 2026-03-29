<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Form;
use App\Filament\Resources\ClientResource\Schemas\Tabs\FeaturesTab;

class EditFeatures extends EditRecord
{
    protected static string $resource = ClientResource::class;
    protected static ?string $title = '⚙️ Features & Toggles';
    protected static ?string $navigationIcon = 'heroicon-m-adjustments-horizontal';

    public function form(Form $form): Form
    {
        return $form->schema(
            FeaturesTab::schema()
        );
    }
}
