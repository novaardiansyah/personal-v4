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
        @php $isSelected = $selectedDate === $dayData['dateStr']; @endphp
        <button
          type="button"
          wire:click="selectDate('{{ $dayData['dateStr'] }}')"
          @class([
            'group flex flex-col rounded-lg border p-1.5 text-left transition sm:p-2',
            'border-gray-200 bg-white hover:border-primary-400 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-500 dark:hover:bg-primary-950/30' => $dayData['isCurrentMonth'] && ! $isSelected,
            'border-gray-100 bg-gray-50 hover:border-primary-300 dark:border-gray-800 dark:bg-gray-950' => ! $dayData['isCurrentMonth'] && ! $isSelected,
            'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-950/40' => $isSelected,
            'ring-2 ring-primary-500 dark:ring-primary-400' => $dayData['isToday'] && ! $isSelected,
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
                  class="truncate rounded px-1.5 py-0.5 text-[10px] font-medium text-white sm:text-xs"
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
        </button>
      @endforeach
    </div>
  </div>

  @if ($selectedDate)
    <div
      class="mt-2 rounded-lg border border-primary-200 bg-primary-50/50 p-4 dark:border-primary-800 dark:bg-primary-950/20 sm:mt-4"
      wire:key="day-panel-{{ $selectedDate }}">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 sm:text-lg">
          {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
        </h3>
        <x-filament::icon-button
          wire:click="clearSelection"
          icon="heroicon-o-x-mark"
          color="gray"
          size="sm"
          :tooltip="__('Close')"
        />
      </div>

      @if (empty($selectedDayEvents) && empty($selectedDayTodos))
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ __('No events or todos for this day.') }}
        </p>
      @else
        <div class="space-y-3">
          @if (!empty($selectedDayEvents))
                <div>
                  <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                    {{ __('Events') }} ({{ count($selectedDayEvents) }})
                  </h4>
                  <ul class="space-y-1.5">
                    @foreach ($selectedDayEvents as $event)
                      <li>
                        <a
                          href="{{ \App\Filament\Resources\CalendarEvents\CalendarEventResource::getUrl('view', ['record' => $event->id]) }}"
                          class="flex items-start gap-2 rounded-md border border-gray-200 bg-white p-2 transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-600 dark:hover:bg-primary-950/30"
                        >
                          <span
                            class="mt-1 h-2 w-2 shrink-0 rounded-full"
                            style="background-color: {{ $event->color ?? '#3B82F6' }}"
                          ></span>
                          <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">
                              {{ $event->title ?? $event->name }}
                            </div>
                            @if (!empty($event->start_at))
                              <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $event->start_at->format('H:i') }}
                                @if (!empty($event->end_at))
                                  – {{ $event->end_at->format('H:i') }}
                                @endif
                              </div>
                            @endif
                          </div>
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif

              @if (!empty($selectedDayTodos))
                <div>
                  <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                    {{ __('Todos') }} ({{ count($selectedDayTodos) }})
                  </h4>
                  <ul class="space-y-1.5">
                    @foreach ($selectedDayTodos as $todo)
                      <li>
                        <a
                          href="{{ \App\Filament\Resources\CalendarTodos\CalendarTodoResource::getUrl('view', ['record' => $todo->id]) }}"
                          class="flex items-start gap-2 rounded-md border border-gray-200 bg-white p-2 transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-600 dark:hover:bg-primary-950/30"
                        >
                          <x-filament::icon
                            :icon="$todo->completed_at ? 'heroicon-o-check-circle' : 'heroicon-o-minus'"
                            :class="$todo->completed_at ? 'text-success-500 mt-0.5 h-4 w-4 shrink-0' : 'text-gray-400 mt-0.5 h-4 w-4 shrink-0'"
                          />
                          <div class="min-w-0 flex-1">
                            <div @class([
                              'truncate text-sm',
                              'text-gray-900 dark:text-gray-100' => ! $todo->completed_at,
                              'text-gray-400 line-through dark:text-gray-500' => $todo->completed_at,
                            ])>
                              {{ $todo->title }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                              {{ ucfirst($todo->priority->value) }}
                            </div>
                          </div>
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif
            </div>
          @endif

          <div class="mt-4 flex gap-2 border-t border-primary-200 pt-3 dark:border-primary-800">
            <x-filament::button
              tag="a"
              href="{{ \App\Filament\Resources\CalendarEvents\CalendarEventResource::getUrl('create') . '?' . http_build_query(['start_at' => $selectedDate]) }} "
              icon="heroicon-o-plus"
              size="xs"
            >
              {{ __('Add Event') }}
            </x-filament::button>
            <x-filament::button
              tag="a"
              href="{{ \App\Filament\Resources\CalendarTodos\CalendarTodoResource::getUrl('create') . '?' . http_build_query(['due_at' => $selectedDate]) }}"
              icon="heroicon-o-plus-circle"
              size="xs"
              color="gray"
            >
              {{ __('Add Todo') }}
            </x-filament::button>
          </div>
    </div>
  @endif
</div>
