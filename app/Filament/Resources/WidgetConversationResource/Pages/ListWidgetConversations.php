<?php
namespace App\Filament\Resources\WidgetConversationResource\Pages;
use App\Filament\Resources\WidgetConversationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListWidgetConversations extends ListRecords {
    protected static string $resource = WidgetConversationResource::class;

    protected function getHeaderActions(): array {
        return [];
    }
}
