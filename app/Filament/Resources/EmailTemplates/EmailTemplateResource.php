<?php

namespace App\Filament\Resources\EmailTemplates;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\EmailTemplates\Pages\CreateEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Filament\Resources\EmailTemplates\Pages\ViewEmailTemplate;
use App\Filament\Resources\EmailTemplates\Schemas\EmailTemplateForm;
use App\Filament\Resources\EmailTemplates\Schemas\EmailTemplateInfolist;
use App\Filament\Resources\EmailTemplates\Tables\EmailTemplatesTable;
use App\Models\EmailTemplate;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmailTemplateResource extends Resource
{
  protected static ?string $model = EmailTemplate::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;
  protected static ?string $recordTitleAttribute = 'subject';
  protected static string|UnitEnum|null $navigationGroup = 'Emails';
  protected static ?int $navigationSort = 30;

  public static function form(Schema $schema): Schema
  {
    return EmailTemplateForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return EmailTemplateInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return EmailTemplatesTable::configure($table);
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
      'index' => ListEmailTemplates::route('/'),
      'create' => CreateEmailTemplate::route('/create'),
      'view' => ViewEmailTemplate::route('/{record}'),
      'edit' => EditEmailTemplate::route('/{record}/edit'),
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
