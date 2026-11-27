<x-filament::section
  heading="History"
  icon="heroicon-o-chat-bubble-left-ellipsis"
>
  <div
    x-data="{ showSearch: false }"
    style="display: flex; flex-direction: column; gap: 10px;"
  >
    <div style="display: flex; align-items: center; gap: 8px; width: 100%;">
      <div style="flex: 1 1 0%; min-width: 0;">
        <x-filament::button
          wire:click="createNewSession"
          icon="heroicon-o-plus"
          size="md"
          color="primary"
          style="width: 100%;"
        >
          New Chat
        </x-filament::button>
      </div>

      <x-filament::button
        @click="showSearch = !showSearch"
        icon="heroicon-o-magnifying-glass"
        color="primary"
        size="md"
        tooltip="Search chats"
      />
    </div>

    <div x-show="showSearch" style="width: 100%;">
      <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
        <x-filament::input
          type="text"
          wire:model.live="searchQuery"
          placeholder="Search conversations..."
        />
      </x-filament::input.wrapper>
    </div>

    <div style="display: flex; flex-direction: column; gap: 10px; max-height: 460px; overflow-y: auto; padding-right: 4px;">
      @forelse($this->groupedSessions as $groupLabel => $groupItems)
        <div style="display: flex; flex-direction: column; gap: 6px;">
          <div style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.6; padding: 2px 4px;">
            {{ $groupLabel }}
          </div>

          @foreach($groupItems as $session)
            @if($editingSessionId === $session['id'])
              <div
                wire:key="session-edit-{{ $session['id'] }}"
                style="display: flex; align-items: center; gap: 6px; padding: 6px 8px; border-radius: 8px; border: 1px solid #f59e0b;"
              >
                <div style="flex: 1 1 0%; min-width: 0;">
                  <x-filament::input.wrapper size="sm">
                    <x-filament::input
                      type="text"
                      wire:model="editingTitle"
                      @keydown.enter.prevent="$wire.saveSessionTitle()"
                      @keydown.escape.prevent="$wire.cancelEditSession()"
                    />
                  </x-filament::input.wrapper>
                </div>
                <x-filament::icon-button
                  wire:click="saveSessionTitle"
                  icon="heroicon-o-check"
                  color="success"
                  size="xs"
                  tooltip="Save title"
                />
                <x-filament::icon-button
                  wire:click="cancelEditSession"
                  icon="heroicon-o-x-mark"
                  color="gray"
                  size="xs"
                  tooltip="Cancel"
                />
              </div>
            @else
              <div
                wire:key="session-{{ $session['id'] }}"
                wire:click="selectSession('{{ $session['id'] }}')"
                style="display: flex; align-items: center; justify-content: space-between; gap: 6px; padding: 9px 10px; border-radius: 8px; cursor: pointer; border: 1px solid {{ $activeSessionId === $session['id'] ? '#f59e0b' : 'rgba(128,128,128,0.2)' }}; background-color: {{ $activeSessionId === $session['id'] ? 'rgba(245, 158, 11, 0.1)' : 'transparent' }};"
              >
                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden; white-space: nowrap; flex: 1 1 0%; min-width: 0;">
                  <x-filament::icon
                    :icon="!empty($session['is_pinned']) ? 'heroicon-s-bookmark' : 'heroicon-o-chat-bubble-left'"
                    style="width: 16px; height: 16px; flex-shrink: 0; opacity: 0.7;"
                  />
                  <span style="overflow: hidden; text-overflow: ellipsis; font-size: 0.875rem;">{{ $session['title'] }}</span>
                </div>

                <div style="display: flex; align-items: center; gap: 2px; flex-shrink: 0;">
                  <x-filament::icon-button
                    wire:click.stop="togglePinSession('{{ $session['id'] }}')"
                    icon="{{ !empty($session['is_pinned']) ? 'heroicon-s-bookmark' : 'heroicon-o-bookmark' }}"
                    color="{{ !empty($session['is_pinned']) ? 'warning' : 'gray' }}"
                    size="xs"
                    tooltip="{{ !empty($session['is_pinned']) ? 'Unpin' : 'Pin' }}"
                  />
                  <x-filament::icon-button
                    wire:click.stop="editSession('{{ $session['id'] }}')"
                    icon="heroicon-o-pencil-square"
                    color="primary"
                    size="xs"
                    tooltip="Rename chat"
                  />
                  <x-filament::icon-button
                    wire:click.stop="deleteSession('{{ $session['id'] }}')"
                    icon="heroicon-o-trash"
                    color="danger"
                    size="xs"
                    tooltip="Delete chat"
                  />
                </div>
              </div>
            @endif
          @endforeach
        </div>
      @empty
        <div style="text-align: center; padding: 24px 0; font-size: 0.875rem; opacity: 0.6;">
          No conversation history.
        </div>
      @endforelse
    </div>

    @if(count($sessions) > 0)
      <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(128,128,128,0.2); font-size: 0.75rem;">
        <span style="opacity: 0.6;">Total: {{ count($sessions) }}</span>
        <x-filament::button
          wire:click="clearAllSessions"
          wire:confirm="Clear all conversations?"
          color="danger"
          size="xs"
          variant="link"
        >
          Clear all
        </x-filament::button>
      </div>
    @endif
  </div>
</x-filament::section>
