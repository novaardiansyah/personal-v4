<x-filament-panels::page>
  <div class="space-y-6">
    <x-filament::section description="Transaction summary" collapsible>
      <livewire:payments.payment-details-summary-table :ids="$this->ids" />
    </x-filament::section>

		<x-filament::section style="margin-top: 16px" collapsible description="Transaction details">
      {{ $this->table }}
    </x-filament::section>
  </div>
</x-filament-panels::page>
