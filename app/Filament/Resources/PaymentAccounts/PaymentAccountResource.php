<?php

namespace App\Filament\Resources\PaymentAccounts;

use App\Models\Setting;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use UnitEnum;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;

use App\Filament\Resources\PaymentAccounts\Pages\ManagePaymentAccounts;
use App\Models\PaymentAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use function Filament\Support\format_money;

class PaymentAccountResource extends Resource
{
  protected static ?string $model = PaymentAccount::class;

  protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCreditCard;
  protected static string | UnitEnum | null $navigationGroup = 'Payments';
  protected static ?int $navigationSort = 10;
  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->required(),
        FileUpload::make('logo')
          ->disk('public')
          ->directory('images/payment_account')
          ->image()
          ->imageEditor()
          ->enableOpen()
          ->enableDownload(),
      ])
      ->columns(1);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('name'),
        TextEntry::make('deposit')
          ->numeric(),
        ImageEntry::make('logo')
          ->checkFileExistence(false)
          ->circular()
          ->size(70),
        Grid::make(3)
          ->columnSpanFull()
          ->schema([
            TextEntry::make('created_at')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->sinceTooltip()
              ->dateTime(),
            TextEntry::make('deleted_at')
              ->dateTime(),
          ])
      ])
      ->columns(3);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('name')
          ->searchable(),
        TextColumn::make('deposit')
          ->formatStateUsing(fn ($state) => toIndonesianCurrency((float) $state ?? 0, showCurrency: Setting::showPaymentCurrency()))
          ->sortable(),
        ImageColumn::make('logo')
          ->checkFileExistence(false)
          ->circular(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->defaultSort('updated_at', 'desc')
      ->filters([
        TrashedFilter::make(),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          
          EditAction::make()
            ->modalWidth(Width::ExtraLarge),

          Action::make('audit')
            ->label('Audit')
            ->color('danger')
            ->icon('heroicon-o-scale')
            ->modalHeading(fn (PaymentAccount $record) => 'Audit ' . $record->name)
            ->modalWidth(Width::Medium)
            ->form(fn (Schema $form) => ManagePaymentAccounts::formAudit($form))
            ->fillForm(fn (PaymentAccount $record): array => ManagePaymentAccounts::fillFormAudit($record))
            ->action(fn (Action $action, PaymentAccount $record, array $data) => ManagePaymentAccounts::actionAudit($action, $record, $data)),
          
          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ])
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
      'index' => ManagePaymentAccounts::route('/'),
    ];
  }

  public static function getRecordRouteBindingEloquentQuery(): Builder
  {
    return parent::getRecordRouteBindingEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }
}
