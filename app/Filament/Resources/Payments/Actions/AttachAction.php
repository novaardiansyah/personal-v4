<?php

namespace App\Filament\Resources\Payments\Actions;

use App\Models\Item;
use App\Models\PaymentItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class AttachAction
{
	public static function make(): Action
	{
		return Action::make('custom_attach')
			->label('Attach')
			->modalWidth(Width::ThreeExtraLarge)
			->form(fn(Schema $schema): Schema => self::schema($schema))
			->action(fn(Action $action, RelationManager $livewire, array $data) => self::action($action, $livewire, $data));
	}

	private static function schema(Schema $schema): Schema
	{
		return $schema
			->components([
				Select::make('recordId')
					->label('Product / Service')
					->native(false)
					->options(function () {
						return Item::latest('updated_at')->pluck('name', 'id');
					})
					->preload()
					->required()
					->searchable()
					->live(onBlur: true)
					->afterStateUpdated(function (?string $state, Set $set, Get $get) {
						if (!$state) return;

						$item = Item::where('id', $state)->first();

						if (!$item) return;

						$set('amount', $item->amount);
						$get('quantity') && $set('total', $item->amount * $get('quantity'));
					}),

				TextInput::make('amount')
					->required()
					->numeric()
					->minValue(0)
					->live(onBlur: true)
					->afterStateUpdated(function ($state, $set, $get): void {
						$get('quantity') && $set('total', $state * $get('quantity'));
					})
					->hint(fn(?string $state) => toIndonesianCurrency(((float) $state ?? 0))),

				TextInput::make('quantity')
					->label('Qty')
					->required()
					->numeric()
					->default(1)
					->minValue(0)
					->live(onBlur: true)
					->afterStateUpdated(function ($state, $set, $get): void {
						$get('amount') && $set('total', $state * $get('amount'));
					})
					->hint(fn(?string $state) => number_format(((float) $state ?? 0), 0, ',', '.')),

				TextInput::make('total')
					->label('Total')
					->required()
					->numeric()
					->minValue(0)
					->live(onBlur: true)
					->readOnly()
					->hint(fn(?string $state) => toIndonesianCurrency(((float) $state ?? 0))),
			])
			->columns(2);
	}

	public static function action(Action $action, RelationManager $livewire, array $data): void
	{
		$owner = $livewire->getOwnerRecord();
		$recordId = $data['recordId'];

		$exist = PaymentItem::where('item_id', $recordId)
			->where('payment_id', $owner->id)->first();

		if ($exist) {
			$action->failure();

			Notification::make()
				->danger()
				->title('Process Failed')
				->body('Item already exists in this payment')
				->send();

			$action->halt();
		}

		$record = PaymentItem::create([
			'item_code'  => getCode('payment_item'),
			'payment_id' => $owner->id,
			'item_id'    => $recordId,
			'price'      => $data['amount'],
			'quantity'   => $data['quantity'],
			'total'      => $data['total'],
		]);

		self::set_owner_price($record);
		$action->getLivewire()->dispatch('refreshForm');

		Notification::make()
			->success()
			->title('Process Success')
			->body('Item attached successfully')
			->send();

		$action->success();
	}

	private static function set_owner_price(PaymentItem $record)
	{
		$owner = $record->payment;
		$item = $record->item;

		$item->update([
			'amount'     => $record->price,
			'updated_at' => now()
		]);

		$expense = $owner->amount + (int) $record->total;
		$note    = trim(($owner->name ?? '') . ', ' . "{$item->name} (x{$record->quantity})", ', ');

		$owner->update([
			'amount' => $expense,
			'name'   => $note
		]);
	}
}
