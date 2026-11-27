<x-filament-panels::page>
  <div style="display: flex; flex-wrap: wrap; gap: 20px; width: 100%; align-items: flex-start; padding-bottom: 32px;">
    @if($isSidebarOpen)
      <div style="width: 320px; max-width: 100%; flex-shrink: 0; box-sizing: border-box;">
        @include('filament.pages.ai-assistant.history-sidebar')
      </div>
    @endif

    <div style="flex: 1 1 0%; min-width: 280px; box-sizing: border-box;">
      @include('filament.pages.ai-assistant.chat-window')
    </div>
  </div>
</x-filament-panels::page>
