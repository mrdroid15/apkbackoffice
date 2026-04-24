<?php

namespace App\Filament\Resources\Apkads\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ApkadsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('packagename')
                    ->required(),
                FileUpload::make('image')
                    ->image()
                    ->disk('s3')
                    ->directory('apkads/images')
                    ->visibility('private')
                    ->required(),
                TextInput::make('link')
                    ->required(),
            ]);
    }
}
