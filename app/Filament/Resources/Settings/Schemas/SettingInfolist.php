<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingInfolist
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make([
					TextEntry::make('name'),
					TextEntry::make('key')
						->badge()
						->copyable(),
					TextEntry::make('value')
						->badge(),
					IconEntry::make('has_options')
						->boolean(),
					TextEntry::make('description')
						->columnSpanFull(),
				])
					->description('General information')
					->collapsible()
					->columns(2),

				Section::make([
					TextEntry::make('deleted_at')
						->dateTime(),
					TextEntry::make('created_at')
						->dateTime(),
					TextEntry::make('updated_at')
						->dateTime()
						->sinceTooltip(),
				])
					->description('Timestamps')
					->collapsible()
					->columns(3),
			])
			->columns(1);
	}
}
