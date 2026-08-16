<?php

namespace App\Filament\Resources\Files\Tables;

use App\Models\File;
use Filament\Actions\Action;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FilesTable
{
	public static function configure(Table $table): Table
	{
		return $table
			->recordTitleAttribute('file_name')
			->columns([
				TextColumn::make('index')
					->label('#')
					->rowIndex(),
				TextColumn::make('uid')
					->label('File ID')
					->searchable()
					->badge()
					->copyable()
					->toggleable(),
				TextColumn::make('fileDownload.code')
					->label('File Download ID')
					->searchable()
					->badge()
					->copyable()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('user.name')
					->label('User')
					->searchable()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('file_name')
					->label('File')
					->tooltip(fn(File $record): string => $record->file_alias ? $record->file_alias : '')
					->searchable()
					->toggleable(),
				TextColumn::make('file_alias')
					->label('Display Name')
					->searchable()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('type.name')
					->label('Type')
					->searchable()
					->toggleable(),
				TextColumn::make('file_size')
					->label('File Size')
					->formatStateUsing(fn(string $state): string => sizeFormat(floatval($state ?? 0)))
					->toggleable(),
				TextColumn::make('subject_id')
					->label('Subject')
					->formatStateUsing(function ($state, Model $record) {
						if (!$state)
							return;
						return Str::of($record->subject_type)->afterLast('\\')->headline() . ' # ' . $state;
					})
					->toggleable(isToggledHiddenByDefault: true),
				IconColumn::make('has_been_deleted')
					->label('File Deleted')
					->boolean()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('scheduled_deletion_time')
					->label('Expiry Date')
					->since()
					->sortable()
					->toggleable(),
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
			->filters([
				TrashedFilter::make()
					->native(false),
			])
			->defaultSort('updated_at', 'desc')
			->recordAction(null)
			->recordActions([
				ActionGroup::make([
					ViewAction::make()
						->modalHeading('View file details')
						->slideOver(),

					EditAction::make(),

					Action::make('download')
						->label('Download')
						->icon('heroicon-s-arrow-down-tray')
						->color('success')
						->url(fn(File $record): ?string => $record->download_url)
						->openUrlInNewTab()
						->visible(fn(File $record): bool => !$record->has_been_deleted && !empty($record->download_url)),

					DeleteAction::make(),
					RestoreAction::make(),
					ForceDeleteAction::make(),
				])
			])
			->toolbarActions([
				BulkActionGroup::make([
					DeleteBulkAction::make(),
					RestoreBulkAction::make(),
					ForceDeleteBulkAction::make(),
				]),
			]);
	}
}
