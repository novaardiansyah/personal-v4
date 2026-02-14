<?php

/*
 * Project Name: personal-v4
 * File: CreateItemAction.php
 * Created Date: Saturday February 14th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Actions;

use App\Models\Item;
use App\Models\ItemType;
use App\Models\PaymentItem;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class CreateItemAction
{
	public static function make(): CreateAction
	{
		return CreateAction::make()
			->label('New item')
			->modalHeading('Create Transaction Item')
			->modalWidth(Width::ThreeExtraLarge)
			->form(fn(Schema $schema): Schema => self::schema($schema))
			->action(fn(Action $action, RelationManager $livewire, array $data) => self::action($action, $livewire, $data));
	}

	private static function schema(Schema $schema): Schema
	{
		return $schema
			->components([
				TextInput::make('name')
					->required()
					->maxLength(255),

				Select::make('type_id')
					->relationship('type', 'name')
					->default(ItemType::PRODUCT)
					->native(false)
					->preload()
					->required(),

				Grid::make([
					'sm' => 3,
					'xs' => 1
				])
					->schema([
						TextInput::make('amount')
							->required()
							->numeric()
							->minValue(0)
							->live(onBlur: true)
							->afterStateUpdated(function ($state, $set, $get): void {
								$get('quantity') && $set('total', $state * $get('quantity'));
							})
							->hint(fn(?string $state) => toIndonesianCurrency((float)$state ?? 0)),

						TextInput::make('quantity')
							->required()
							->numeric()
							->default(1)
							->minValue(0)
							->live(onBlur: true)
							->afterStateUpdated(function ($state, $set, $get): void {
								$get('amount') && $set('total', $state * $get('amount'));
							})
							->hint(fn(?string $state) => number_format((float) $state ?? 0, 0, ',', '.')),

						TextInput::make('total')
							->label('Total')
							->numeric()
							->minValue(0)
							->live(onBlur: true)
							->readOnly()
							->hint(fn(?string $state) => toIndonesianCurrency((float) $state ?? 0)),
					])
					->columnSpanFull()
			])
			->columns(2);
	}

	private static function action(Action $action, RelationManager $livewire, array $data): void
	{
		$owner = $livewire->getOwnerRecord();

		try {
			DB::beginTransaction();

			$item = Item::updateOrCreate(
				['name' => $data['name']],
				[
					'type_id' => $data['type_id'],
					'amount'  => $data['amount'],
				]
			);

			$exist = PaymentItem::where('item_id', $item->id)
				->where('payment_id', $owner->id)->first();

			if ($exist) {
				throw new \Exception('Item already exists in this payment');
			}

			$paymentItem = PaymentItem::create([
				'payment_id' => $owner->id,
				'item_id'    => $item->id,
				'price'      => $data['amount'],
				'quantity'   => $data['quantity'],
				'total'      => $data['total'],
			]);

			PaymentAction::set_owner_price($paymentItem);

			DB::commit();

			$action->getLivewire()->dispatch('refreshForm');

			Notification::make()
				->success()
				->title('Process Success')
				->body('Item created successfully')
				->send();

			$action->success();
		} catch (\Exception $e) {
			DB::rollBack();

			$action->failure();

			Notification::make()
				->danger()
				->title('Process Failed')
				->body($e->getMessage())
				->send();
			
			$action->halt();
		}
	}
}
