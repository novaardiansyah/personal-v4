<?php

namespace App\Filament\Resources\PaymentGoals;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\PaymentGoals\Actions\PaymentGoalAction;
use App\Filament\Resources\PaymentGoals\Pages\AllocateFundPaymentGoal;
use App\Filament\Resources\PaymentGoals\Pages\CreatePaymentGoal;
use App\Filament\Resources\PaymentGoals\Pages\EditPaymentGoal;
use App\Filament\Resources\PaymentGoals\Pages\ManagePaymentGoals;
use App\Models\PaymentGoal;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class PaymentGoalResource extends Resource
{
	protected static ?string $model = PaymentGoal::class;

	protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

	protected static string|UnitEnum|null $navigationGroup = 'Payments';

	protected static ?int $navigationSort = 40;

	protected static ?string $recordTitleAttribute = 'name';

	public static function form(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make()
					->description('Goal Details')
					->collapsible()
					->columns(2)
					->columnSpan(2)
					->schema([
						DatePicker::make('start_date')
							->label('Start Date')
							->required()
							->native(false)
							->closeOnDateSelection()
							->displayFormat('M d, Y')
							->default(Carbon::now()->firstOfMonth()),

						DatePicker::make('target_date')
							->label('Target Date')
							->required()
							->native(false)
							->closeOnDateSelection()
							->displayFormat('M d, Y')
							->default(Carbon::now()->endOfMonth())
							->after('start_date'),

						TextInput::make('target_amount')
							->label('Target Amount')
							->required()
							->numeric()
							->prefix('Rp')
							->placeholder('0')
							->live(onBlur: true)
							->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

						TextInput::make('amount')
							->label('Current Amount')
							->required()
							->numeric()
							->prefix('Rp')
							->default(0)
							->placeholder('0')
							->live(onBlur: true)
							->hint(function (Get $get, ?string $state) {
								$target   = (float) $get('target_amount');
								$amount   = (float) $state;
								$progress = $target > 0 ? round(($amount / $target) * 100, 2) : 0;

								return toIndonesianCurrency($state ?? 0) . ' (' . $progress . '%)';
							}),
					]),

				Section::make()
					->description('Goal Information')
					->collapsible()
					->schema([
						TextInput::make('name')
							->label('Goal Name')
							->required()
							->placeholder('Enter your goal name')
							->columnSpanFull(),

						Textarea::make('description')
							->label('Description')
							->rows(3)
							->placeholder('Describe your goal in detail')
							->columnSpanFull(),
					]),
			])
			->columns(3);
	}

	public static function infolist(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make()
					->description('Goal Overview')
					->collapsible()
					->columns(3)
					->schema([
						TextEntry::make('code')
							->label('Goal ID')
							->badge()
							->copyable(),

						TextEntry::make('name')
							->label('Goal Name')
							->columnSpan(2),

						TextEntry::make('amount')
							->label('Current Amount')
							->formatStateUsing(function (?string $state, PaymentGoal $record): string {
								return toIndonesianCurrency($state ?? 0) . ' (' . $record->latest_progress_percent . '%)';
							}),

						TextEntry::make('target_amount')
							->label('Target Amount')
							->formatStateUsing(fn(?string $state): string => toIndonesianCurrency($state ?? 0)),

						TextEntry::make('status.name')
							->label('Status')
							->badge()
							->color(fn(PaymentGoal $record): string => $record->status->getBadgeColors()),

						TextEntry::make('description')
							->label('Description')
							->columnSpanFull(),
					]),

				Section::make()
					->description('Timestamp')
					->collapsible()
					->columns(3)
					->schema([
						TextEntry::make('start_date')
							->label('Start Date')
							->date('M d, Y')
							->sinceTooltip(),

						TextEntry::make('target_date')
							->label('Target Date')
							->date('M d, Y')
							->sinceTooltip()
							->columnSpan(2),

						TextEntry::make('created_at')
							->dateTime('M d, Y H:i')
							->sinceTooltip(),

						TextEntry::make('updated_at')
							->dateTime('M d, Y H:i')
							->sinceTooltip(),

						TextEntry::make('deleted_at')
							->dateTime('M d, Y H:i')
							->sinceTooltip(),
					]),
			])->columns(1);
	}

	public static function table(Table $table): Table
	{
		return $table
			->recordTitleAttribute('name')
			->columns([
				TextColumn::make('index')
					->rowIndex()
					->label('#'),
				TextColumn::make('code')
					->label('Goal ID')
					->searchable()
					->badge()
					->copyable()
					->sortable()
					->toggleable(),
				TextColumn::make('status.name')
					->searchable()
					->toggleable()
					->badge()
					->color(fn(PaymentGoal $record): string => $record->status->getBadgeColors()),
				TextColumn::make('name')
					->searchable()
					->toggleable(),
				TextColumn::make('description')
					->searchable()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('amount')
					->formatStateUsing(function (?string $state, PaymentGoal $record): string {
						return toIndonesianCurrency($state ?? 0) . ' (' . $record->latest_progress_percent . '%)';
					})
					->sortable()
					->toggleable(),
				TextColumn::make('target_amount')
					->formatStateUsing(fn(?string $state): string => toIndonesianCurrency($state ?? 0))
					->sortable()
					->toggleable(),
				TextColumn::make('start_date')
					->date()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('target_date')
					->date()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
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
					->sortable()
					->sinceTooltip()
					->toggleable(),
			])
			->defaultSort('updated_at', 'desc')
			->filters([
				TrashedFilter::make()
					->native(false),
			])
			->headerActions([
				PaymentGoalAction::printExcel(),
				PaymentGoalAction::printPdf(),
			])
			->recordActions([
				ActionGroup::make([
					ViewAction::make()
						->modalWidth(Width::ThreeExtraLarge)
						->modalHeading('Payment Goal Details')
						->slideOver(),

					EditAction::make(),

					Action::make('allocate_fund')
						->label('Allocate Fund')
						->icon('heroicon-o-banknotes')
						->color('success')
						->visible(fn(PaymentGoal $record) => $record->progress_percent < 100)
						->url(fn(PaymentGoal $record): string => static::getUrl('allocate-fund', ['record' => $record])),

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
			'index'         => ManagePaymentGoals::route('/'),
			'create'        => CreatePaymentGoal::route('/create'),
			'edit'          => EditPaymentGoal::route('/{record}/edit'),
			'allocate-fund' => AllocateFundPaymentGoal::route('/{record}/allocate-fund'),
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
