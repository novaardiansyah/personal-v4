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

    <div style="display: flex; flex-direction: column; gap: 8px; max-height: 420px; overflow-y: auto; padding-right: 4px;">
      @forelse($this->filteredSessions as $session)
        @if($editingSessionId === $session['id'])
          <div
            wire:key="session-edit-{{ $session['id'] }}"
            style="display: flex; align-items: center; gap: 6px; padding: 6px 8px; border-radius: 8px;"
            class="border border-primary-500 bg-primary-50 dark:bg-primary-950/40"
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
            style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
            @class([
              'group border',
              'border-primary-500 bg-primary-50 dark:bg-primary-950/40 text-primary-600 font-medium' => $activeSessionId === $session['id'],
              'border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' => $activeSessionId !== $session['id'],
            ])
          >
            <div style="display: flex; align-items: center; gap: 8px; overflow: hidden; white-space: nowrap; flex: 1 1 0%; min-width: 0;">
              <x-filament::icon icon="heroicon-o-chat-bubble-left" class="h-4 w-4 shrink-0 text-gray-400" />
              <span style="overflow: hidden; text-overflow: ellipsis;" class="text-sm">{{ $session['title'] }}</span>
            </div>

            <div style="display: flex; align-items: center; gap: 6px;" class="opacity-0 group-hover:opacity-100 transition-opacity">
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
      @empty
        <div style="text-align: center; padding: 24px 0;" class="text-sm text-gray-400">
          No conversation history.
        </div>
      @endforelse
    </div>

    @if(count($sessions) > 0)
      <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(128,128,128,0.2);" class="text-xs">
        <span class="text-gray-400">Total: {{ count($sessions) }}</span>
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
