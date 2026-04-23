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
					->disk('public')
					->directory('uploads') // Files will be in public/storage/uploads
					->visibility('public'),
                TextInput::make('link')
                    ->required(),
            ]);
    }
}
