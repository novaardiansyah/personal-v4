<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\RelationManagers\ItemsRelationManager;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Resources\Payments\Schemas\PaymentInfolist;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
  protected static ?string $model = Payment::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
  protected static string | UnitEnum | null $navigationGroup = 'Payments';
  protected static ?int $navigationSort = 9;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return PaymentForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return PaymentInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return PaymentsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      ItemsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListPayments::route('/'),
      'create' => CreatePayment::route('/create'),
      'view' => ViewPayment::route('/{record}'),
      'edit' => EditPayment::route('/{record}/edit'),
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
