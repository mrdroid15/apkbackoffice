<?php

namespace App\Filament\Resources\Apkads;

use App\Filament\Resources\Apkads\Pages\CreateApkads;
use App\Filament\Resources\Apkads\Pages\EditApkads;
use App\Filament\Resources\Apkads\Pages\ListApkads;
use App\Filament\Resources\Apkads\Schemas\ApkadsForm;
use App\Filament\Resources\Apkads\Tables\ApkadsTable;
use App\Models\Apkads;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApkadsResource extends Resource
{
    protected static ?string $model = Apkads::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ApkadsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApkadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApkads::route('/'),
            'create' => CreateApkads::route('/create'),
            'edit' => EditApkads::route('/{record}/edit'),
        ];
    }
}
