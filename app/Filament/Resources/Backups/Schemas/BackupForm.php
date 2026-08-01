<?php

namespace App\Filament\Resources\Backups\Schemas;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupForm
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make()
					->description('Backup Details')
					->collapsible()
					->columnSpan(['sm' => 3, 'md' => 2])
					->schema([
						Grid::make(['sm' => 1, 'xs' => 1])
							->columnSpanFull()
							->schema([
								TextInput::make('uid')
									->label('UID')
									->default(fn() => getCode('BACKUP'))
									->disabledOn('edit')
									->required(),
								TextInput::make('file_name')
									->label('File Name')
									->placeholder('backup-20260729.zip'),
								TextInput::make('file_path')
									->label('File Path')
									->placeholder('/path/to/backup.zip'),
								TextInput::make('cloud_file_path')
									->label('Cloud File Path')
									->placeholder('/cloud/path/to/backup.zip'),
								Grid::make(['sm' => 2, 'xs' => 1])
									->schema([
										Select::make('type')
											->label('Type')
											->options(BackupType::class)
											->native(false)
											->preload(),
										Select::make('status')
											->label('Status')
											->options(BackupStatus::class)
											->default(BackupStatus::Pending)
											->required()
											->native(false)
											->preload(),
									]),
								Grid::make(['sm' => 2, 'xs' => 1])
									->schema([
										TextInput::make('file_size')
											->label('File Size (bytes)')
											->numeric()
											->default(0),
										TextInput::make('duration')
											->label('Duration (seconds)')
											->numeric()
											->default(0),
									]),
								Grid::make(['sm' => 2, 'xs' => 1])
									->schema([
										TextInput::make('checksum')
											->label('Checksum'),
										TextInput::make('server_name')
											->label('Server Name'),
									]),
								Textarea::make('message')
									->label('Message')
									->rows(3)
									->placeholder('Backup details or error messages...'),
							]),
					]),

				Section::make()
					->description('Execution Timestamps')
					->collapsible()
					->columnSpan(['sm' => 3, 'md' => 1])
					->columns(1)
					->schema([
						DateTimePicker::make('started_at')
							->label('Started At')
							->native(false),
						DateTimePicker::make('completed_at')
							->label('Completed At')
							->native(false),
					]),
			])
			->columns(3);
	}
}
