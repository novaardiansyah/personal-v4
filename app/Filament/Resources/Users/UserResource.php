<?php

namespace App\Filament\Resources\Users;

use BackedEnum;
use UnitEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;

class UserResource extends Resource
{
  protected static ?string $model = User::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
  protected static string | UnitEnum | null $navigationGroup = 'Settings';
  protected static ?int $navigationSort = 10;
  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->required(),
        TextInput::make('email')
          ->label('Email address')
          ->email()
          ->required(),
        DateTimePicker::make('email_verified_at')
          ->native(false)
          ->default(now()),
        TextInput::make('password')
          ->password()
          ->revealable()
          ->required(fn (string $operation): bool => $operation === 'create'),
        FileUpload::make('avatar_url')
          ->label('Profile picture')
          ->disk('public')
          ->directory('images/avatar')
          ->image()
          ->imageEditor()
          ->enableDownload()
          ->enableOpen()
          ->columnSpanFull(),
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('name'),
        TextEntry::make('email')
          ->label('Email address'),
        TextEntry::make('email_verified_at')
          ->dateTime(),
        // IconEntry::make('has_email_authentication')
        //   ->boolean(),
        TextEntry::make('created_at')
          ->dateTime(),
        TextEntry::make('updated_at')
          ->dateTime(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('code')
          ->label('User ID')
          ->copyable()
          ->toggleable()
          ->searchable(),
        ImageColumn::make('avatar_url')
          ->label('Profile picture')
          ->disk('public')
          ->circular()
          ->size(30)
          ->toggleable(),
        TextColumn::make('name')
          ->searchable(),
        TextColumn::make('email')
          ->label('Email address')
          ->searchable(),
        TextColumn::make('email_verified_at')
          ->dateTime()
          ->sortable(),
        // IconColumn::make('has_email_authentication')
        //   ->boolean(),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->defaultSort('updated_at', 'desc')
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),

          EditAction::make()
            ->mutateFormDataUsing(function (array $data, User $record): array {
              if (!$record->code) {
                $data['code'] = getCode('user');
              }

              if (!$data['password']) {
                unset($data['password']);
              }

              return $data;
            }),

          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          ForceDeleteBulkAction::make(),
          RestoreBulkAction::make(),
        ]),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => ManageUsers::route('/'),
    ];
  }
}
