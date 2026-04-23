<?php

namespace App\Filament\Resources\Apkads\Pages;

use App\Filament\Resources\Apkads\ApkadsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApkads extends EditRecord
{
    protected static string $resource = ApkadsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
