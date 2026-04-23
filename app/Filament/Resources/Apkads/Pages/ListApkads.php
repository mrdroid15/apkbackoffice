<?php

namespace App\Filament\Resources\Apkads\Pages;

use App\Filament\Resources\Apkads\ApkadsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApkads extends ListRecords
{
    protected static string $resource = ApkadsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
