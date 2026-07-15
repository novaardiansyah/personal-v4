<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-6">
  <div class="mb-4 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 sm:text-2xl">
      {{ $currentMonth->translatedFormat('F Y') }}
    </h2>
    <div class="flex flex-wrap gap-2">
      <x-filament::button
        wire:click="previousMonth"
        color="gray"
        size="sm"
        icon="heroicon-o-chevron-left"
        icon-position="before"
      >
        {{ __('Prev') }}
      </x-filament::button>
      <x-filament::button
        wire:click="today"
        color="gray"
        size="sm"
      >
        {{ __('Today') }}
      </x-filament::button>
      <x-filament::button
        wire:click="nextMonth"
        color="gray"
        size="sm"
        icon="heroicon-o-chevron-right"
        icon-position="after"
      >
        {{ __('Next') }}
      </x-filament::button>
    </div>
  </div>

  <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
    <div class="grid min-w-[640px] grid-cols-7 grid-rows-7 auto-rows-fr gap-1 sm:gap-2">
      @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
        <div class="rounded-md bg-gray-100 py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300 sm:text-sm">
          {{ __($day) }}
        </div>
      @endforeach

      @foreach ($daysInMonth as $dayData)
        @php $visibleEvents = array_slice($dayData['events'], 0, 3); @endphp
        <div @class([
          'group flex flex-col rounded-lg border p-1.5 transition sm:p-2',
          'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' => $dayData['isCurrentMonth'],
          'border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-950' => ! $dayData['isCurrentMonth'],
          'ring-2 ring-primary-500 dark:ring-primary-400' => $dayData['isToday'],
        ])>
          <div class="flex items-start justify-between">
            <span @class([
              'inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs sm:h-7 sm:w-7 sm:text-sm',
              'bg-primary-600 font-semibold text-white' => $dayData['isToday'],
              'text-gray-400 dark:text-gray-600' => ! $dayData['isCurrentMonth'],
              'text-gray-900 dark:text-gray-100' => $dayData['isCurrentMonth'] && ! $dayData['isToday'],
            ])>
              {{ $dayData['day'] }}
            </span>
          </div>

          @if ($dayData['eventCount'] > 0)
            <div class="mt-1 flex flex-1 flex-col gap-1 overflow-hidden">
              @foreach ($visibleEvents as $event)
                <div
                  class="truncate rounded px-1.5 py-0.5 text-[10px] font-medium text-white sm:text-xs cursor-pointer hover:opacity-80 transition"
                  style="background-color: {{ $event->color ?? '#3B82F6' }}"
                  title="{{ $event->title ?? $event->name }}"
                >
                  {{ Str::limit($event->title ?? $event->name, 14, '...') }}
                </div>
              @endforeach
              @if ($dayData['eventCount'] > count($visibleEvents))
                <div class="text-[10px] font-medium text-gray-500 dark:text-gray-400 sm:text-xs">
                  +{{ $dayData['eventCount'] - count($visibleEvents) }} {{ __('more') }}
                </div>
              @endif
            </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</div>
