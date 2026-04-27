<?php

namespace App\Filament\Resources\Apkads\Schemas;

use App\Services\PrivacyPolicyTemplate;
use App\Models\Apkads;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

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

                // Public URL preview. On edit pages we have a real slug; on
                // create we don't, so we show "Save first to get a URL."
                Placeholder::make('privacy_policy_url')
                    ->label('Privacy policy URL')
                    ->content(function (?Apkads $record): HtmlString {
                        if ($record?->privacy_policy_url === null) {
                            return new HtmlString(
                                '<span class="text-sm text-gray-500">'
                                . 'Save the record once to assign a URL.'
                                . '</span>'
                            );
                        }
                        $url = e($record->privacy_policy_url);
                        return new HtmlString(
                            '<a href="' . $url . '" target="_blank" rel="noopener" '
                            . 'class="text-sm text-primary-600 hover:underline">'
                            . $url . '</a>'
                        );
                    }),

                // Generate button. Reads current name/packagename from the
                // form state (Get) so it works on the create page before any
                // record exists, and writes the rendered template into the
                // editor (Set) without touching the database. The user then
                // saves the form to persist it.
                Actions::make([
                    Action::make('generate_privacy_policy')
                        ->label('Generate privacy policy')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Replace policy with template?')
                        ->modalDescription('This overwrites the current policy text with a freshly generated template. Save the record afterwards to persist it.')
                        ->action(function ($get, $set): void {
                            $stub = new Apkads([
                                'name' => $get('name') ?? '',
                                'packagename' => $get('packagename') ?? '',
                            ]);
                            $set('privacy_policy', PrivacyPolicyTemplate::render($stub));
                            $set('privacy_policy_generated_at', now());
                        }),
                ]),

                RichEditor::make('privacy_policy')
                    ->label('Privacy policy content')
                    ->columnSpanFull(),
            ]);
    }
}
