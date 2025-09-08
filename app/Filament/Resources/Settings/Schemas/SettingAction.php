<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SettingAction
{
  // ! Change Value
  public static function formChangeValue(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->label('Name')
          ->disabled(),

        TextInput::make('options')
          ->label('Value options')
          ->hidden(),

        Toggle::make('has_options')
          ->label('Has options')
          ->live()
          ->hidden(),

        Textarea::make('value')
          ->label('Nilai')
          ->required()
          ->rows(3)
          ->maxLength(255)
          ->visible(fn(Get $get) => !$get('has_options')),

        Select::make('value_option')
          ->label('Choose value')
          ->required()
          ->native(false)
          ->searchable()
          ->options(function (Get $get) {
            $options = $get('options') ?? [];
            return collect($options)->mapWithKeys(function ($option) {
              return [$option => $option];
            });
          })
          ->visible(fn(Get $get) => $get('has_options')),
      ])
      ->columns(1);
  }

  public static function fillFormChangeValue(Setting $record): array
  {
    return [
      'name'         => $record->name,
      'value'        => $record->value,
      'has_options'  => $record->has_options,
      'options'      => $record->options ? explode(',', $record->options) : [],
      'value_option' => $record->value,
    ];
  }

  public static function actionChangeValue(Action $action, Setting $record, array $data): void
  {
    $value = $data['value'] ?? $data['value_option'];
    $record->update(['value' => $value]);

    $action->success();

    Notification::make() 
      ->success()
      ->title('Sucessfully Changed')
      ->body('Setting value has been successfully changed.')
      ->send();
  }
  // ! End Change Value
}